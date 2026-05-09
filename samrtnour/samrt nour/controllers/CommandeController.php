<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/ValidationHelper.php';
require_once __DIR__ . '/TwilioNotifier.php';
require_once __DIR__ . '/../models/Panier.php';
require_once __DIR__ . '/GarantieController.php';
require_once __DIR__ . '/../services/IntegrationBridge.php';

class CommandeController
{
    private $conn;
    private $notifier;
    private $integration;
    private $lastKonnectError = '';

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->notifier = new TwilioNotifier();
        $this->integration = new IntegrationBridge();

        $this->ensurePaymentColumns();
        $this->integration->ensureCommandesIntegrationSchema($this->conn);
        $this->integration->backfillCommandes($this->conn);
        $this->integration->createIntegrationViews($this->conn);
    }

    public function orderPiece()
    {
        $errors = [];
        $success = '';
        $old = [
            'payment_method' => 'cash',
        ];
        $clientContext = $this->integration->getCurrentClientContext();
        $old = array_merge($old, [
            'nom_client' => $clientContext['nom_client'] ?? '',
            'prenom_client' => $clientContext['prenom_client'] ?? '',
            'telephone' => $clientContext['telephone'] ?? '',
        ]);
        $pieces = $this->getAvailablePieces();

        if (isset($_GET['id_piece']) && ctype_digit((string) $_GET['id_piece'])) {
            $old['id_piece'] = (int) $_GET['id_piece'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = ValidationHelper::validateCommande($_POST);
            $errors = $validation['errors'];
            $old = array_merge($old, $_POST);

            if (empty($errors)) {
                $data = $validation['sanitized'];
                $piece = $this->getPieceById($data['id_piece']);

                if (!$piece) {
                    $errors[] = 'La piece selectionnee n\'existe pas.';
                } elseif ($data['quantite'] > (int) $piece['quantite_stock']) {
                    $errors[] = 'Stock insuffisant. Seulement ' . (int) $piece['quantite_stock'] . ' unite(s) disponible(s).';
                }

                if (empty($errors)) {
                    $paymentMethod = $this->resolvePaymentMethod($_POST);
                    $montantTotal = (float) $piece['prix_unitaire'] * (int) $data['quantite'];

                    $orderData = [
                        'id_piece' => (int) $data['id_piece'],
                        'nom_client' => $data['nom_client'],
                        'prenom_client' => $data['prenom_client'],
                        'telephone' => $data['telephone'],
                        'quantite' => (int) $data['quantite'],
                        'montant_total' => $montantTotal,
                        'statut' => $paymentMethod === 'konnect' ? 'Paiement initie' : 'En attente',
                        'payment_method' => $paymentMethod === 'konnect' ? 'Konnect' : 'Paiement a la livraison',
                        'payment_status' => $paymentMethod === 'konnect' ? 'En attente' : 'Non paye',
                    ];
                    $orderData = $this->integration->enrichOrderData($orderData);

                    if ($paymentMethod === 'konnect') {
                        $paymentLaunch = $this->createKonnectPayment($orderData, $piece);

                        if ($paymentLaunch !== null) {
                            $_SESSION['konnect_pending_orders'][$paymentLaunch['local_ref']] = [
                                'payment_ref' => $paymentLaunch['payment_ref'],
                                'order' => $orderData,
                                'piece_nom' => $piece['nom'],
                            ];

                            header('Location: ' . $paymentLaunch['pay_url'], true, 303);
                            exit;
                        }

                        $errors[] = $this->lastKonnectError !== ''
                            ? $this->lastKonnectError
                            : 'Impossible de lancer le paiement Konnect. Verifiez la configuration API.';
                    } else {
                        $created = $this->createCommande($orderData);

                        if ($created) {
                            $notificationSent = $this->sendOrderNotification([
                                'nom_client' => $data['nom_client'],
                                'prenom_client' => $data['prenom_client'],
                                'telephone' => $data['telephone'],
                                'quantite' => (int) $data['quantite'],
                                'montant_total' => $montantTotal,
                                'piece_nom' => $piece['nom'],
                            ]);

                            $success = 'Commande passee avec succes. Montant total : ' . number_format($montantTotal, 2, ',', ' ') . ' DT';
                            if ($notificationSent) {
                                $success .= ' SMS SmartGarage envoye.';
                            }

                            $old = ['payment_method' => 'cash'];
                            $pieces = $this->getAvailablePieces();
                        } else {
                            $errors[] = 'Erreur lors de la commande. Verifiez le stock disponible puis reessayez.';
                        }
                    }
                }
            }
        }

        require __DIR__ . '/../views/front/piece_order.php';
    }

    public function konnectSuccess()
    {
        $error = '';
        $success = '';
        $commande = null;
        $localRef = isset($_GET['local_ref']) ? trim((string) $_GET['local_ref']) : '';

        if ($localRef === '' || !isset($_SESSION['konnect_pending_orders'][$localRef])) {
            $error = 'Reference de paiement Konnect introuvable.';
            require __DIR__ . '/../views/front/payment_success.php';
            return;
        }

        $pendingPayment = $_SESSION['konnect_pending_orders'][$localRef];
        $paymentDetails = $this->getKonnectPaymentDetails($pendingPayment['payment_ref']);

        if ($paymentDetails === null || !isset($paymentDetails['payment'])) {
            $error = $this->lastKonnectError !== ''
                ? $this->lastKonnectError
                : 'Impossible de verifier le paiement Konnect.';
            require __DIR__ . '/../views/front/payment_success.php';
            return;
        }

        if (strtolower((string) ($paymentDetails['payment']['status'] ?? '')) !== 'completed') {
            $error = 'Le paiement Konnect n\'a pas encore ete confirme.';
            require __DIR__ . '/../views/front/payment_success.php';
            return;
        }

        $existingCommande = $this->getCommandeByGatewayReference($pendingPayment['payment_ref']);
        if ($existingCommande) {
            $commande = $existingCommande;
            $success = 'Le paiement Konnect est confirme et la commande est deja enregistree.';
            require __DIR__ . '/../views/front/payment_success.php';
            return;
        }

        $created = $this->createCommande(array_merge($pendingPayment['order'], [
            'statut' => 'Payee',
            'payment_method' => 'Konnect',
            'payment_status' => 'Paye',
            'payment_gateway_reference' => $pendingPayment['payment_ref'],
        ]));

        if (!$created) {
            $error = 'Le paiement est confirme, mais la commande n\'a pas pu etre enregistree.';
            require __DIR__ . '/../views/front/payment_success.php';
            return;
        }

        $this->sendOrderNotification([
            'nom_client' => $pendingPayment['order']['nom_client'],
            'prenom_client' => $pendingPayment['order']['prenom_client'],
            'telephone' => $pendingPayment['order']['telephone'],
            'quantite' => (int) $pendingPayment['order']['quantite'],
            'montant_total' => (float) $pendingPayment['order']['montant_total'],
            'piece_nom' => isset($pendingPayment['piece_nom']) ? $pendingPayment['piece_nom'] : 'votre piece',
        ]);

        unset($_SESSION['konnect_pending_orders'][$localRef]);

        $commande = $this->getCommandeByGatewayReference($pendingPayment['payment_ref']);
        $success = 'Paiement Konnect confirme. Votre commande Smart Garage a ete enregistree avec succes.';

        require __DIR__ . '/../views/front/payment_success.php';
    }

    public function konnectCancel()
    {
        require __DIR__ . '/../views/front/payment_cancel.php';
    }

    public function orderHistory()
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
        if (!$this->isAdmin() && !empty($_SESSION['user_id'])) {
            $filters['id_client'] = (int) $_SESSION['user_id'];
        }

        $page = $this->getPageNumber();
        $result = $this->getPaginatedCommandes($page, 6, $filters);

        $commandes = $result['items'];
        $pagination = $result['pagination'];
        $paginationQuery = $filters;

        require __DIR__ . '/../views/front/order_history.php';
    }

    public function orderDetail()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $commande = $this->getCommandeById($id);

        if (!$commande || (!$this->isAdmin() && (int) ($commande['id_client'] ?? 0) !== (int) ($_SESSION['user_id'] ?? 0))) {
            header('Location: index.php?action=orderHistory&error=' . rawurlencode('Commande introuvable'));
            exit;
        }

        $garanties = $this->getGarantiesForCommande($id);
        $items = $this->getCommandeItems($commande);

        require __DIR__ . '/../views/front/order_detail.php';
    }

    public function requestPiece()
    {
        $errors = [];
        $success = '';
        $old = [
            'nom_client' => '',
            'prenom_client' => '',
            'telephone' => '',
            'nom_piece' => '',
            'marque' => '',
            'description' => '',
            'quantite' => '1',
        ];

        if (isset($_GET['q'])) {
            $old['nom_piece'] = trim((string) $_GET['q']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = ValidationHelper::validateDemandePiece($_POST);
            $errors = $validation['errors'];
            $old = array_merge($old, $_POST);

            if (empty($errors)) {
                if ($this->storeDemandePiece($validation['sanitized'])) {
                    $success = 'Votre demande a ete envoyee avec succes. Nous vous contacterons bientot.';
                    $old = [
                        'nom_client' => '',
                        'prenom_client' => '',
                        'telephone' => '',
                        'nom_piece' => '',
                        'marque' => '',
                        'description' => '',
                        'quantite' => '1',
                    ];
                } else {
                    $errors[] = 'Impossible d\'enregistrer votre demande pour le moment. Veuillez reessayer.';
                }
            }
        }

        require __DIR__ . '/../views/front/piece_request.php';
    }

    public function manageCommandes()
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'statut' => trim((string) ($_GET['statut'] ?? '')),
        ];

        $page = $this->getPageNumber();
        $result = $this->getPaginatedCommandes($page, 8, $filters);

        $commandes = $result['items'];
        $pagination = $result['pagination'];
        $paginationQuery = $filters;
        $statusOptions = $this->getCommandeStatusOptions();
        $success = isset($_GET['success']) ? htmlspecialchars((string) $_GET['success']) : '';
        $error = isset($_GET['error']) ? htmlspecialchars((string) $_GET['error']) : '';

        require __DIR__ . '/../views/back/commande_list.php';
    }

    public function deleteCommande()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id > 0 && $this->deleteCommandeById($id)) {
            header('Location: index.php?action=manageCommandes&success=' . rawurlencode('Commande supprimee avec succes'));
        } else {
            header('Location: index.php?action=manageCommandes&error=' . rawurlencode('Erreur lors de la suppression'));
        }
        exit;
    }

    public function updateCommandeStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToManageCommandes($_GET, 'error', 'Action non autorisee');
        }

        $id = isset($_POST['id_commande']) ? (int) $_POST['id_commande'] : 0;
        $statut = trim((string) ($_POST['statut'] ?? ''));
        $statusOptions = $this->getCommandeStatusOptions();

        if ($id <= 0 || !in_array($statut, $statusOptions, true)) {
            $this->redirectToManageCommandes($_POST, 'error', 'Statut invalide');
        }

        if (!$this->commandeExists($id)) {
            $this->redirectToManageCommandes($_POST, 'error', 'Commande introuvable');
        }

        if ($this->updateCommandeStatusById($id, $statut)) {
            $this->redirectToManageCommandes($_POST, 'success', 'Statut mis a jour avec succes');
        }

        $this->redirectToManageCommandes($_POST, 'error', 'Erreur lors de la mise a jour du statut');
    }

    private function resolvePaymentMethod(array $input)
    {
        $paymentMethod = isset($input['payment_method']) ? trim((string) $input['payment_method']) : 'cash';
        return $paymentMethod === 'konnect' ? 'konnect' : 'cash';
    }

    private function createKonnectPayment(array $orderData, array $piece)
    {
        $apiKey = trim((string) $this->env('KONNECT_API_KEY', ''));
        $walletId = trim((string) $this->env('KONNECT_WALLET_ID', ''));
        $baseUrl = rtrim((string) $this->env('KONNECT_API_BASE_URL', 'https://api.sandbox.konnect.network/api/v2'), '/');
        $this->lastKonnectError = '';

        if ($apiKey === '' || $walletId === '') {
            $this->lastKonnectError = 'Configuration Konnect incomplete. Ajoutez KONNECT_API_KEY et KONNECT_WALLET_ID dans le fichier .env.';
            return null;
        }

        $localRef = uniqid('kn_', true);
        $paymentPayload = [
            'receiverWalletId' => $walletId,
            'token' => strtoupper((string) $this->env('KONNECT_CURRENCY', 'TND')),
            'amount' => (int) round(((float) $orderData['montant_total']) * 1000),
            'type' => 'immediate',
            'description' => 'Smart Garage - ' . $piece['nom'],
            'acceptedPaymentMethods' => ['wallet', 'bank_card', 'e-DINAR'],
            'lifespan' => (int) $this->env('KONNECT_LIFESPAN_MINUTES', '10'),
            'checkoutForm' => true,
            'addPaymentFeesToAmount' => false,
            'firstName' => $orderData['prenom_client'],
            'lastName' => $orderData['nom_client'],
            'phoneNumber' => preg_replace('/\D+/', '', $orderData['telephone']),
            'email' => $this->buildCustomerEmail($orderData),
            'orderId' => $localRef,
            'successUrl' => $this->buildAbsoluteUrl('index.php?action=konnectSuccess&local_ref=' . rawurlencode($localRef)),
            'failUrl' => $this->buildAbsoluteUrl('index.php?action=konnectCancel'),
            'theme' => 'light',
        ];

        $response = $this->callKonnectApi('POST', $baseUrl . '/payments/init-payment', $paymentPayload, $apiKey);
        if ($response === null || empty($response['payUrl']) || empty($response['paymentRef'])) {
            if ($this->lastKonnectError === '') {
                $this->lastKonnectError = 'La reponse Konnect est invalide. Aucun lien de paiement n\'a ete retourne.';
            }
            return null;
        }

        return [
            'local_ref' => $localRef,
            'pay_url' => $response['payUrl'],
            'payment_ref' => $response['paymentRef'],
        ];
    }

    private function getKonnectPaymentDetails($paymentRef)
    {
        $apiKey = trim((string) $this->env('KONNECT_API_KEY', ''));
        $baseUrl = rtrim((string) $this->env('KONNECT_API_BASE_URL', 'https://api.sandbox.konnect.network/api/v2'), '/');
        $this->lastKonnectError = '';
        if ($apiKey === '' || $paymentRef === '') {
            $this->lastKonnectError = 'Impossible de verifier Konnect sans cle API ou reference de paiement.';
            return null;
        }

        return $this->callKonnectApi('GET', $baseUrl . '/payments/' . rawurlencode($paymentRef), null, $apiKey);
    }

    private function callKonnectApi($method, $url, $payload, $apiKey)
    {
        $this->lastKonnectError = '';
        $headers = [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }

            $rawResponse = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($rawResponse === false) {
                $this->lastKonnectError = 'Connexion Konnect impossible: ' . $curlError;
                return null;
            }

            if ($statusCode < 200 || $statusCode >= 300) {
                $decodedError = json_decode($rawResponse, true);
                $message = is_array($decodedError)
                    ? (string) ($decodedError['message'] ?? $decodedError['error'] ?? '')
                    : '';
                $this->lastKonnectError = 'Konnect a retourne une erreur HTTP ' . $statusCode . ($message !== '' ? ' : ' . $message : '.');
                return null;
            }

            $decoded = json_decode($rawResponse, true);
            return is_array($decoded) ? $decoded : null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $payload !== null ? json_encode($payload) : '',
                'ignore_errors' => true,
                'timeout' => 20,
            ],
        ]);

        $rawResponse = @file_get_contents($url, false, $context);
        if ($rawResponse === false) {
            $statusLine = isset($http_response_header[0]) ? $http_response_header[0] : '';
            $this->lastKonnectError = $statusLine !== ''
                ? 'Connexion Konnect impossible: ' . $statusLine
                : 'Connexion Konnect impossible.';
            return null;
        }

        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $statusCode = (int) $matches[1];
            if ($statusCode < 200 || $statusCode >= 300) {
                $decodedError = json_decode($rawResponse, true);
                $message = is_array($decodedError)
                    ? (string) ($decodedError['message'] ?? $decodedError['error'] ?? '')
                    : '';
                $this->lastKonnectError = 'Konnect a retourne une erreur HTTP ' . $statusCode . ($message !== '' ? ' : ' . $message : '.');
                return null;
            }
        }

        $decoded = json_decode($rawResponse, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function buildCustomerEmail(array $orderData)
    {
        $firstName = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '.', $orderData['prenom_client'])));
        $lastName = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '.', $orderData['nom_client'])));
        $email = trim($firstName . '.' . $lastName, '.');

        if ($email === '') {
            $email = 'client.smartgarage';
        }

        return $email . '@smartgarage.local';
    }

    private function buildAbsoluteUrl($relativePath)
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

        return $scheme . '://' . $host . ($basePath !== '' ? $basePath . '/' : '/') . ltrim($relativePath, '/');
    }

    private function env($key, $default = '')
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    private function storeDemandePiece(array $data)
    {
        $filePath = __DIR__ . '/../database/demandes_piece.json';
        $payload = [];

        if (is_file($filePath) && is_readable($filePath)) {
            $raw = file_get_contents($filePath);
            if ($raw !== false && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }

        $payload[] = [
            'id' => uniqid('dem_', true),
            'date_demande' => date('Y-m-d H:i:s'),
            'nom_client' => $data['nom_client'],
            'prenom_client' => $data['prenom_client'],
            'telephone' => $data['telephone'],
            'nom_piece' => $data['nom_piece'],
            'marque' => $data['marque'],
            'description' => $data['description'],
            'quantite' => $data['quantite'],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        return file_put_contents($filePath, $json, LOCK_EX) !== false;
    }

    private function getAvailablePieces()
    {
        $stmt = $this->conn->query('SELECT * FROM pieces ORDER BY date_ajout DESC, id_piece DESC');
        return $stmt->fetchAll();
    }

    private function getPieceById($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM pieces WHERE id_piece = :id_piece');
        $stmt->execute([':id_piece' => (int) $id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    private function createCommande(array $data)
    {
        try {
            $this->conn->beginTransaction();

            $lockStmt = $this->conn->prepare(
                'SELECT id_piece, prix_unitaire, quantite_stock
                 FROM pieces
                 WHERE id_piece = :id_piece
                 FOR UPDATE'
            );
            $lockStmt->execute([':id_piece' => (int) $data['id_piece']]);
            $piece = $lockStmt->fetch();

            if (!$piece) {
                $this->conn->rollBack();
                return false;
            }

            $quantiteDemandee = (int) $data['quantite'];
            $stockActuel = (int) $piece['quantite_stock'];

            if ($quantiteDemandee < 1 || $quantiteDemandee > $stockActuel) {
                $this->conn->rollBack();
                return false;
            }

            $montantTotal = isset($data['montant_total'])
                ? (float) $data['montant_total']
                : ((float) $piece['prix_unitaire'] * $quantiteDemandee);

            $insertStmt = $this->conn->prepare(
                'INSERT INTO commandes (id_piece, id_client, nom_client, prenom_client, telephone, email_client, id_vehicle, id_rdv, quantite, montant_total, statut, payment_method, payment_status, payment_gateway_reference, source)
                 VALUES (:id_piece, :id_client, :nom_client, :prenom_client, :telephone, :email_client, :id_vehicle, :id_rdv, :quantite, :montant_total, :statut, :payment_method, :payment_status, :payment_gateway_reference, :source)'
            );
            $insertStmt->execute([
                ':id_piece' => (int) $data['id_piece'],
                ':id_client' => !empty($data['id_client']) ? (int) $data['id_client'] : null,
                ':nom_client' => $data['nom_client'],
                ':prenom_client' => $data['prenom_client'],
                ':telephone' => $data['telephone'],
                ':email_client' => $data['email_client'] ?? null,
                ':id_vehicle' => !empty($data['id_vehicle']) ? (int) $data['id_vehicle'] : null,
                ':id_rdv' => !empty($data['id_rdv']) ? (int) $data['id_rdv'] : null,
                ':quantite' => $quantiteDemandee,
                ':montant_total' => $montantTotal,
                ':statut' => isset($data['statut']) ? $data['statut'] : 'En attente',
                ':payment_method' => isset($data['payment_method']) ? $data['payment_method'] : 'Paiement a la livraison',
                ':payment_status' => isset($data['payment_status']) ? $data['payment_status'] : 'Non paye',
                ':payment_gateway_reference' => isset($data['payment_gateway_reference']) ? $data['payment_gateway_reference'] : null,
                ':source' => isset($data['source']) ? $data['source'] : 'direct',
            ]);

            $updateStockStmt = $this->conn->prepare(
                'UPDATE pieces
                 SET quantite_stock = quantite_stock - :quantite
                 WHERE id_piece = :id_piece'
            );
            $updateStockStmt->execute([
                ':quantite' => $quantiteDemandee,
                ':id_piece' => (int) $data['id_piece'],
            ]);

            $this->conn->commit();

            // Créer la garantie automatiquement pour la commande directe
            try {
                $idCommande = (int) $this->conn->lastInsertId();
                $garantieCtrl = new GarantieController();
                $garantieCtrl->createAfterCommande(
                    $idCommande,
                    [['id_piece' => (int) $data['id_piece']]],
                    !empty($data['id_client']) ? (int) $data['id_client'] : 0
                );
            } catch (Throwable $garEx) {
                error_log('Erreur création garantie directe : ' . $garEx->getMessage());
            }

            // ── Vérification alerte Telegram ──
            require_once __DIR__ . '/../services/StockAlertNotifier.php';
            try {
                $stockNotifier = new StockAlertNotifier($this->conn);
                $stockNotifier->notifyPieceIfNeeded((int) $data['id_piece']);
            } catch (Throwable $t) {
                error_log("Erreur Telegram dans createCommande : " . $t->getMessage());
            }

            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    private function getPaginatedCommandes($page, $perPage, array $filters)
    {
        [$whereSql, $params] = $this->buildCommandeFilters($filters);

        $countSql = 'SELECT COUNT(*)
                     FROM commandes c
                     INNER JOIN pieces p ON p.id_piece = c.id_piece'
            . $whereSql;
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int) $countStmt->fetchColumn();

        $pagination = $this->buildPagination($page, $perPage, $totalItems);

        $sql = 'SELECT
                    c.id_commande,
                    c.id_piece,
                    c.id_client,
                    c.email_client,
                    c.id_vehicle,
                    c.id_rdv,
                    c.nom_client,
                    c.prenom_client,
                    c.telephone,
                    c.quantite,
                    c.montant_total,
                    c.statut,
                    c.payment_method,
                    c.payment_status,
                    c.payment_gateway_reference,
                    c.date_commande,
                    wg.garantie_count,
                    wg.garantie_active_count,
                    wg.garantie_next_expiration,
                    wg.garantie_min_days,
                    p.nom AS piece_nom,
                    p.reference AS piece_reference,
                    p.prix_unitaire AS piece_prix_unitaire,
                    p.image AS piece_image
                FROM commandes c
                INNER JOIN pieces p ON p.id_piece = c.id_piece
                LEFT JOIN (
                    SELECT id_commande,
                           COUNT(*) AS garantie_count,
                           SUM(CASE WHEN statut = "active" AND date_expiration >= CURDATE() THEN 1 ELSE 0 END) AS garantie_active_count,
                           MIN(CASE WHEN statut = "active" AND date_expiration >= CURDATE() THEN date_expiration ELSE NULL END) AS garantie_next_expiration,
                           MIN(CASE WHEN statut = "active" THEN DATEDIFF(date_expiration, CURDATE()) ELSE NULL END) AS garantie_min_days
                    FROM garanties
                    GROUP BY id_commande
                ) wg ON wg.id_commande = c.id_commande'
            . $whereSql
            . ' ORDER BY c.date_commande DESC, c.id_commande DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'pagination' => $pagination,
        ];
    }

    private function buildCommandeFilters(array $filters)
    {
        $conditions = [];
        $params = [];

        if ($filters['q'] !== '') {
            $conditions[] = '(c.nom_client LIKE :q OR c.prenom_client LIKE :q OR c.telephone LIKE :q OR p.nom LIKE :q OR p.reference LIKE :q)';
            $params[':q'] = '%' . $filters['q'] . '%';
        }

        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $conditions[] = 'c.statut = :statut';
            $params[':statut'] = $filters['statut'];
        }

        if (isset($filters['id_client']) && (int) $filters['id_client'] > 0) {
            $conditions[] = 'c.id_client = :id_client';
            $params[':id_client'] = (int) $filters['id_client'];
        }

        $whereSql = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$whereSql, $params];
    }

    private function sendOrderNotification(array $orderData)
    {
        return $this->notifier->sendOrderConfirmation($orderData);
    }

    private function deleteCommandeById($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM commandes WHERE id_commande = :id_commande');
        return $stmt->execute([':id_commande' => (int) $id]);
    }

    private function getCommandeStatusOptions()
    {
        return [
            'En attente',
            'Paiement initie',
            'Paiement',
            'Confirmee',
            'Payee',
            'Livree',
            'Annulee',
        ];
    }

    private function commandeExists($id)
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM commandes WHERE id_commande = :id_commande');
        $stmt->execute([':id_commande' => (int) $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function updateCommandeStatusById($id, $statut)
    {
        $stmt = $this->conn->prepare('UPDATE commandes SET statut = :statut WHERE id_commande = :id_commande');
        return $stmt->execute([
            ':statut' => $statut,
            ':id_commande' => (int) $id,
        ]);
    }

    private function redirectToManageCommandes(array $input, $type, $message)
    {
        $query = [
            'action' => 'manageCommandes',
            $type => $message,
        ];

        $q = trim((string) ($input['return_q'] ?? ''));
        if ($q !== '') {
            $query['q'] = $q;
        }

        $statut = trim((string) ($input['return_statut'] ?? ''));
        if ($statut !== '') {
            $query['statut'] = $statut;
        }

        $page = isset($input['return_page']) ? (int) $input['return_page'] : 1;
        if ($page > 1) {
            $query['page'] = $page;
        }

        header('Location: index.php?' . http_build_query($query));
        exit;
    }

    public function viewCommande()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $commande = $this->getCommandeById($id);

        if (!$commande) {
            header('Location: index.php?action=manageCommandes&error=' . rawurlencode('Commande introuvable'));
            exit;
        }

        require __DIR__ . '/../views/back/commande_view.php';
    }

    private function getCommandeById($id)
    {
        $stmt = $this->conn->prepare(
            'SELECT
                c.*,
                v.vehicule_marque,
                v.vehicule_modele,
                v.vehicule_immatriculation,
                v.rdv_type_intervention,
                v.rdv_statut,
                p.nom AS piece_nom,
                p.reference AS piece_reference,
                p.prix_unitaire AS piece_prix_unitaire,
                p.image AS piece_image
             FROM commandes c
             INNER JOIN pieces p ON p.id_piece = c.id_piece
             LEFT JOIN vue_commandes_integrees v ON v.id_commande = c.id_commande
             WHERE c.id_commande = :id_commande
             LIMIT 1'
        );
        $stmt->execute([':id_commande' => (int) $id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    private function getGarantiesForCommande(int $idCommande): array
    {
        try {
            $stmt = $this->conn->prepare(
                'SELECT *
                 FROM vue_garanties
                 WHERE id_commande = :id_commande
                 ORDER BY date_expiration ASC, id_garantie ASC'
            );
            $stmt->execute([':id_commande' => $idCommande]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function getCommandeItems(array $commande): array
    {
        try {
            $stmt = $this->conn->prepare(
                'SELECT ci.*, p.nom, p.marque, p.reference, p.image
                 FROM commande_items ci
                 INNER JOIN pieces p ON p.id_piece = ci.id_piece
                 WHERE ci.id_commande = :id_commande
                 ORDER BY ci.id_item ASC'
            );
            $stmt->execute([':id_commande' => (int) $commande['id_commande']]);
            $items = $stmt->fetchAll();
            if (!empty($items)) {
                return $items;
            }
        } catch (Throwable $e) {
        }

        return [[
            'id_piece' => (int) ($commande['id_piece'] ?? 0),
            'nom' => (string) ($commande['piece_nom'] ?? ''),
            'marque' => '',
            'reference' => (string) ($commande['piece_reference'] ?? ''),
            'image' => (string) ($commande['piece_image'] ?? ''),
            'quantite' => (int) ($commande['quantite'] ?? 0),
            'prix_unitaire' => (float) ($commande['piece_prix_unitaire'] ?? 0),
            'sous_total' => (float) ($commande['montant_total'] ?? 0),
        ]];
    }

    private function getCommandeByGatewayReference($reference)
    {
        $stmt = $this->conn->prepare(
            'SELECT
                c.*,
                p.nom AS piece_nom,
                p.reference AS piece_reference,
                p.prix_unitaire AS piece_prix_unitaire,
                p.image AS piece_image
             FROM commandes c
             INNER JOIN pieces p ON p.id_piece = c.id_piece
             WHERE c.payment_gateway_reference = :payment_gateway_reference
             LIMIT 1'
        );
        $stmt->execute([':payment_gateway_reference' => $reference]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    private function buildPagination($page, $perPage, $totalItems)
    {
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $page), $totalPages);

        return [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'offset' => ($currentPage - 1) * $perPage,
            'from' => $totalItems === 0 ? 0 : (($currentPage - 1) * $perPage) + 1,
            'to' => min($totalItems, $currentPage * $perPage),
        ];
    }

    private function getPageNumber()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        return $page > 0 ? $page : 1;
    }

    private function ensurePaymentColumns()
    {
        $columns = [
            'payment_method' => "ALTER TABLE commandes ADD COLUMN payment_method VARCHAR(100) NOT NULL DEFAULT 'Paiement a la livraison' AFTER statut",
            'payment_status' => "ALTER TABLE commandes ADD COLUMN payment_status VARCHAR(50) NOT NULL DEFAULT 'Non paye' AFTER payment_method",
            'payment_gateway_reference' => "ALTER TABLE commandes ADD COLUMN payment_gateway_reference VARCHAR(255) NULL AFTER payment_status",
        ];

        foreach ($columns as $columnName => $sql) {
            try {
                $stmt = $this->conn->query("SHOW COLUMNS FROM commandes LIKE '" . $columnName . "'");
                if (!$stmt->fetch()) {
                    $this->conn->exec($sql);
                }
            } catch (Throwable $e) {
                // Ignore schema update issues to keep the app available.
            }
        }
    }

    private function isAdmin(): bool
    {
        return isset($_SESSION['admin_id']) || (($_SESSION['role'] ?? '') === 'admin');
    }

    // ──────────────────────────────────────────
    // Création de commande depuis le module Intervention
    // Permet aux collègues de créer une commande multi-pièces via API
    // ──────────────────────────────────────────
    public function fromIntervention(array $pieces, int $id_client)
    {
        try {
            $this->conn->beginTransaction();

            // Récupérer les infos client
            $client = $this->integration->getClientById($id_client);

            if (!$client) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Client introuvable.'];
            }

            // Calculer le total
            $montantHT = 0;
            $totalQte = 0;
            $firstPieceId = null;
            $resolvedPieces = [];

            foreach ($pieces as $p) {
                $stmtP = $this->conn->prepare('SELECT * FROM pieces WHERE id_piece = :i FOR UPDATE');
                $stmtP->execute([':i' => (int)$p['id_piece']]);
                $piece = $stmtP->fetch();

                if (!$piece) {
                    $this->conn->rollBack();
                    return ['success' => false, 'message' => 'Pièce ID ' . $p['id_piece'] . ' introuvable.'];
                }

                $qte = (int)($p['quantite'] ?? 1);
                if ($qte > (int)$piece['quantite_stock']) {
                    $this->conn->rollBack();
                    return ['success' => false, 'message' => 'Stock insuffisant pour ' . $piece['nom'] . '.'];
                }

                $montantHT += (float)$piece['prix_unitaire'] * $qte;
                $totalQte += $qte;
                if ($firstPieceId === null) $firstPieceId = (int)$piece['id_piece'];
                $resolvedPieces[] = ['piece' => $piece, 'quantite' => $qte];
            }

            $tva = round($montantHT * 0.19, 2);
            $ttc = round($montantHT + $tva, 2);

            // INSERT commande
            $ins = $this->conn->prepare(
                'INSERT INTO commandes (id_piece, id_client, nom_client, prenom_client, telephone, email_client, quantite, montant_total, statut, payment_method, payment_status, montant_ht, tva, frais_livraison, montant_ttc, source)
                 VALUES (:ip, :cid, :nc, :pc, :tel, :email, :q, :mt, :st, :pm, :ps, :mht, :tva, :fl, :ttc, :src)'
            );
            $ins->execute([
                ':ip' => $firstPieceId, ':cid' => $id_client, ':nc' => $client['nom'] ?? '', ':pc' => $client['prenom'] ?? '',
                ':tel' => $client['telephone'] ?? '', ':email' => $client['email'] ?? null, ':q' => $totalQte, ':mt' => $ttc,
                ':st' => 'En attente', ':pm' => 'Intervention', ':ps' => 'Non paye',
                ':mht' => $montantHT, ':tva' => $tva, ':fl' => 0.00, ':ttc' => $ttc, ':src' => 'intervention',
            ]);
            $idCmd = (int)$this->conn->lastInsertId();

            // INSERT items + UPDATE stock
            $itemsForGarantie = [];
            foreach ($resolvedPieces as $rp) {
                $this->conn->prepare('INSERT INTO commande_items (id_commande, id_piece, quantite, prix_unitaire) VALUES (:c, :i, :q, :p)')
                    ->execute([':c' => $idCmd, ':i' => (int)$rp['piece']['id_piece'], ':q' => $rp['quantite'], ':p' => (float)$rp['piece']['prix_unitaire']]);
                $this->conn->prepare('UPDATE pieces SET quantite_stock = quantite_stock - :q WHERE id_piece = :i')
                    ->execute([':q' => $rp['quantite'], ':i' => (int)$rp['piece']['id_piece']]);
                
                $itemsForGarantie[] = ['id_piece' => (int)$rp['piece']['id_piece']];
            }

            $this->conn->commit();

            // Créer les garanties automatiquement pour la commande depuis l'intervention
            try {
                $garantieCtrl = new GarantieController();
                $garantieCtrl->createAfterCommande($idCmd, $itemsForGarantie, $id_client);
            } catch (Throwable $garEx) {
                error_log('Erreur création garantie fromIntervention : ' . $garEx->getMessage());
            }

            require_once __DIR__ . '/../services/StockAlertNotifier.php';
            try {
                $stockNotifier = new StockAlertNotifier($this->conn);
                $stockNotifier->notifyPiecesIfNeeded(array_column($itemsForGarantie, 'id_piece'));
            } catch (Throwable $t) {
                error_log("Erreur Telegram dans fromIntervention : " . $t->getMessage());
            }

            return ['success' => true, 'id_commande' => $idCmd, 'montant_ttc' => $ttc];

        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }
}
