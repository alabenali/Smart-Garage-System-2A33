<?php
// ============================================
// Contrôleur Panier – API JSON + pages checkout/confirmation
// ============================================

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Panier.php';
require_once __DIR__ . '/TwilioNotifier.php';
require_once __DIR__ . '/GarantieController.php';
require_once __DIR__ . '/../services/IntegrationBridge.php';

class PanierController
{
    private $conn;
    private $panierModel;
    private $notifier;
    private $integration;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->panierModel = new Panier();
        $this->notifier = new TwilioNotifier();
        $this->integration = new IntegrationBridge();
        $this->integration->ensureCommandesIntegrationSchema($this->conn);
        $this->integration->backfillCommandes($this->conn);
        $this->integration->createIntegrationViews($this->conn);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['session_cart_id'])) {
            $_SESSION['session_cart_id'] = session_id() . '_' . time();
        }
    }

    // ── Identifiant panier courant ──
    private function getCurrentPanier()
    {
        $sessionId = $_SESSION['session_cart_id'];
        $clientId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        return $this->panierModel->getOrCreate($sessionId, $clientId);
    }

    // ── Réponse JSON standard ──
    private function jsonResponse($success, $message, $data = [], $extraFields = [])
    {
        header('Content-Type: application/json; charset=utf-8');
        $panier = $this->getCurrentPanier();
        $summary = $this->panierModel->getCartSummary($panier['id_panier']);

        $response = array_merge([
            'success'    => $success,
            'message'    => $message,
            'data'       => $data,
            'cart_count' => $summary['nb_articles'],
            'cart_total' => number_format($summary['total_ttc'], 2, '.', '') . ' DT',
        ], $extraFields);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── POST : Ajouter au panier ──
    public function addToCart()
    {
        $id_piece = isset($_POST['id_piece']) ? (int)$_POST['id_piece'] : 0;
        $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 1;

        if ($id_piece <= 0) {
            $this->jsonResponse(false, 'Identifiant de pièce invalide.');
        }

        try {
            $panier = $this->getCurrentPanier();
            $result = $this->panierModel->addItem($panier['id_panier'], $id_piece, $quantite);
            $this->jsonResponse($result['success'], $result['message'], [
                'item_count' => $result['item_count'],
                'total'      => $result['total'],
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Erreur serveur : ' . $e->getMessage());
        }
    }

    // ── POST : Retirer du panier ──
    public function removeFromCart()
    {
        $id_piece = isset($_POST['id_piece']) ? (int)$_POST['id_piece'] : 0;
        if ($id_piece <= 0) {
            $this->jsonResponse(false, 'Identifiant de pièce invalide.');
        }

        try {
            $panier = $this->getCurrentPanier();
            $result = $this->panierModel->removeItem($panier['id_panier'], $id_piece);
            $this->jsonResponse($result['success'], $result['message']);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Erreur serveur : ' . $e->getMessage());
        }
    }

    // ── POST : Modifier la quantité ──
    public function updateQty()
    {
        $id_piece = isset($_POST['id_piece']) ? (int)$_POST['id_piece'] : 0;
        $quantite = isset($_POST['quantite']) ? (int)$_POST['quantite'] : 0;

        if ($id_piece <= 0) {
            $this->jsonResponse(false, 'Identifiant de pièce invalide.');
        }

        try {
            $panier = $this->getCurrentPanier();
            $result = $this->panierModel->updateQuantity($panier['id_panier'], $id_piece, $quantite);
            $this->jsonResponse($result['success'], $result['message']);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Erreur serveur : ' . $e->getMessage());
        }
    }

    // ── GET : Contenu complet du panier ──
    public function getCart()
    {
        try {
            $panier = $this->getCurrentPanier();
            $summary = $this->panierModel->getCartSummary($panier['id_panier']);
            $this->jsonResponse(true, 'Panier chargé.', $summary);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Erreur serveur : ' . $e->getMessage());
        }
    }

    // ── POST : Vider le panier ──
    public function clearCart()
    {
        try {
            $panier = $this->getCurrentPanier();
            $result = $this->panierModel->clear($panier['id_panier']);
            $this->jsonResponse($result['success'], $result['message']);
        } catch (Throwable $e) {
            $this->jsonResponse(false, 'Erreur serveur : ' . $e->getMessage());
        }
    }

    // ── GET : Page checkout ──
    public function checkout()
    {
        $panier = $this->getCurrentPanier();
        $summary = $this->panierModel->getCartSummary($panier['id_panier']);

        if (empty($summary['items'])) {
            header('Location: index.php?action=showCatalogue');
            exit;
        }

        $pageTitle = 'Finaliser la commande';
        $action = 'checkout';
        $cartSummary = $summary;
        $clientContext = $this->integration->getCurrentClientContext();
        $old = [
            'nom_client' => trim((string) (($clientContext['prenom_client'] ?? '') . ' ' . ($clientContext['nom_client'] ?? ''))),
            'prenom_client' => '',
            'telephone' => $clientContext['telephone'] ?? '',
            'email' => $clientContext['email_client'] ?? '',
        ];

        require __DIR__ . '/../views/front/checkout.php';
    }

    // ── POST : Confirmer la commande ──
    public function confirmOrder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=checkout');
            exit;
        }

        $panier = $this->getCurrentPanier();

        // Données client
        $client_data = [
            'nom_client'    => trim($_POST['nom_client'] ?? ''),
            'prenom_client' => trim($_POST['prenom_client'] ?? ''),
            'telephone'     => trim($_POST['telephone'] ?? ''),
            'email'         => trim($_POST['email'] ?? ''),
            'adresse'       => trim($_POST['adresse'] ?? ''),
            'note'          => trim($_POST['note'] ?? ''),
        ];
        $client_data = $this->integration->enrichOrderData($client_data);
        if (($client_data['nom_client'] ?? '') === '' && !empty($client_data['prenom_client'])) {
            $client_data['nom_client'] = $client_data['prenom_client'];
        }

        $payment_method = trim($_POST['payment_method'] ?? 'cash');

        // Validation basique
        $errors = [];
        if ($client_data['nom_client'] === '') $errors[] = 'Le nom est obligatoire.';
        if ($client_data['telephone'] === '') $errors[] = 'Le téléphone est obligatoire.';
        if (!preg_match('/^\d{8}$/', preg_replace('/\D/', '', $client_data['telephone']))) {
            $errors[] = 'Numéro de téléphone invalide (8 chiffres attendus).';
        }

        if (!empty($errors)) {
            $summary = $this->panierModel->getCartSummary($panier['id_panier']);
            $pageTitle = 'Finaliser la commande';
            $action = 'checkout';
            $cartSummary = $summary;
            $old = $client_data;
            $old['payment_method'] = $payment_method;
            require __DIR__ . '/../views/front/checkout.php';
            return;
        }

        try {
            $result = $this->panierModel->convertToCommande(
                $panier['id_panier'],
                $client_data,
                $payment_method
            );

            if (!$result['success']) {
                $summary = $this->panierModel->getCartSummary($panier['id_panier']);
                $pageTitle = 'Finaliser la commande';
                $action = 'checkout';
                $cartSummary = $summary;
                $errors = [$result['message']];
                $old = array_merge($client_data, ['payment_method' => $payment_method]);
                require __DIR__ . '/../views/front/checkout.php';
                return;
            }

            // Envoyer SMS Twilio
            $this->notifier->sendOrderConfirmation([
                'nom_client'    => $client_data['nom_client'],
                'prenom_client' => $client_data['prenom_client'],
                'telephone'     => $client_data['telephone'],
                'quantite'      => $result['nb_articles'],
                'montant_total' => $result['montant_ttc'],
                'piece_nom'     => $result['nb_articles'] . ' pièce(s)',
            ]);

            // Créer les garanties automatiquement pour chaque pièce commandée
            try {
                $garantieCtrl = new GarantieController();
                $summary = $this->panierModel->getCartSummary($panier['id_panier']);
                $itemsForGarantie = [];
                foreach ($summary['items'] as $it) {
                    $itemsForGarantie[] = ['id_piece' => (int)$it['id_piece']];
                }
                $garantieCtrl->createAfterCommande($result['id_commande'], $itemsForGarantie, !empty($client_data['id_client']) ? (int)$client_data['id_client'] : 0);
            } catch (Throwable $garEx) {
                // Ne pas bloquer la commande si les garanties échouent
                error_log('Erreur création garanties : ' . $garEx->getMessage());
            }

            // ── Vérification alerte Telegram pour les pièces du panier ──
            require_once __DIR__ . '/../services/StockAlertNotifier.php';
            try {
                $db = Database::getInstance()->getConnection();
                $stockNotifier = new StockAlertNotifier($db);
                $stockNotifier->notifyPiecesIfNeeded(array_column($summary['items'], 'id_piece'));
            } catch (Throwable $t) {
                error_log("Erreur Telegram dans confirmOrder : " . $t->getMessage());
            }

            // Forcer nouveau panier pour la prochaine visite
            $_SESSION['session_cart_id'] = session_id() . '_' . time();

            // Stocker les données en session pour la confirmation
            $_SESSION['last_order'] = [
                'id_commande'  => $result['id_commande'],
                'montant_ttc'  => $result['montant_ttc'],
                'nb_articles'  => $result['nb_articles'],
                'telephone'    => $client_data['telephone'],
                'client_data'  => $client_data,
            ];

            header('Location: index.php?action=orderConfirmation&id=' . $result['id_commande']);
            exit;

        } catch (Throwable $e) {
            $summary = $this->panierModel->getCartSummary($panier['id_panier']);
            $pageTitle = 'Finaliser la commande';
            $action = 'checkout';
            $cartSummary = $summary;
            $errors = ['Erreur lors de la commande : ' . $e->getMessage()];
            $old = array_merge($client_data, ['payment_method' => $payment_method]);
            require __DIR__ . '/../views/front/checkout.php';
        }
    }

    // ── GET : Page confirmation ──
    public function orderConfirmation()
    {
        $id_commande = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // Récupérer la commande avec ses items
        $commande = $this->getCommandeWithItems($id_commande);

        if (!$commande) {
            header('Location: index.php?action=showCatalogue');
            exit;
        }

        $lastOrder = $_SESSION['last_order'] ?? null;
        $pageTitle = 'Commande confirmée';
        $action = 'orderConfirmation';

        require __DIR__ . '/../views/front/confirmation.php';
    }

    // ── Récupérer une commande avec ses items détaillés ──
    private function getCommandeWithItems($id_commande)
    {
        $stmt = $this->conn->prepare(
            'SELECT c.* FROM commandes c WHERE c.id_commande = :id LIMIT 1'
        );
        $stmt->execute([':id' => (int)$id_commande]);
        $commande = $stmt->fetch();
        if (!$commande) return false;

        // Charger les items détaillés
        $stmtItems = $this->conn->prepare(
            'SELECT ci.*, p.nom, p.marque, p.reference, p.image
             FROM commande_items ci
             INNER JOIN pieces p ON p.id_piece = ci.id_piece
             WHERE ci.id_commande = :id ORDER BY ci.id_item ASC'
        );
        $stmtItems->execute([':id' => (int)$id_commande]);
        $commande['items'] = $stmtItems->fetchAll();

        // Fallback pour commandes directes (sans commande_items)
        if (empty($commande['items']) && !empty($commande['id_piece'])) {
            $stmtP = $this->conn->prepare('SELECT nom, marque, reference, image, prix_unitaire FROM pieces WHERE id_piece = :i');
            $stmtP->execute([':i' => (int)$commande['id_piece']]);
            $p = $stmtP->fetch();
            if ($p) {
                $commande['items'] = [[
                    'nom' => $p['nom'], 'marque' => $p['marque'], 'reference' => $p['reference'],
                    'image' => $p['image'], 'quantite' => $commande['quantite'],
                    'prix_unitaire' => $p['prix_unitaire'],
                    'sous_total' => (float)$p['prix_unitaire'] * (int)$commande['quantite'],
                ]];
            }
        }

        return $commande;
    }
}
