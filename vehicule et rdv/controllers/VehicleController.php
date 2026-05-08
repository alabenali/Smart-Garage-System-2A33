<?php
// ============================================
// Contrôleur de Véhicule
// ============================================

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/RdvService.php';

class VehicleController
{
    private PDO $db;
    private RdvService $rdvService;
    private array $rdvColumnCache = [];
    private ?bool $hasVehicleClientColumnCache = null;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->rdvService = new RdvService($this->db);
    }

    // -------------------------------------------------------
    // Validation côté PHP (nettoyage + vérification vide + numérique)
    // -------------------------------------------------------
    private function validateInput($data)
    {
        $errors = [];

        // Nettoyer toutes les entrées
        $marque = htmlspecialchars(strip_tags(trim($data['marque'] ?? '')));
        $modele = htmlspecialchars(strip_tags(trim($data['modele'] ?? '')));
        $immatriculation = htmlspecialchars(strip_tags(trim($data['immatriculation'] ?? '')));
        $couleur = htmlspecialchars(strip_tags(trim($data['couleur'] ?? '')));
        $annee = trim($data['annee'] ?? '');
        $kilometrage = trim($data['kilometrage'] ?? '');
        $carburant = htmlspecialchars(strip_tags(trim($data['carburant'] ?? '')));
        $idClient = isset($data['id_client']) && (int) $data['id_client'] > 0
            ? (int) $data['id_client']
            : (isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null);

        // Vérifier les valeurs vides
        if (empty($marque)) $errors[] = "La marque est obligatoire.";
        if (empty($modele)) $errors[] = "Le modèle est obligatoire.";
        if (empty($immatriculation)) $errors[] = "L'immatriculation est obligatoire.";
        if (empty($couleur)) $errors[] = "La couleur est obligatoire.";
        if (empty($annee)) $errors[] = "L'année est obligatoire.";
        if (empty($kilometrage) && $kilometrage !== '0') $errors[] = "Le kilométrage est obligatoire.";
        if (empty($carburant)) $errors[] = "Le type de carburant est obligatoire.";

        // Valider les champs numériques
        if (!empty($annee)) {
            if (!is_numeric($annee)) {
                $errors[] = "L'année doit être un nombre.";
            } else {
                $annee = (int) $annee;
                $currentYear = (int) date('Y');
                if ($annee < 1990 || $annee > $currentYear) {
                    $errors[] = "L'année doit être entre 1990 et {$currentYear}.";
                }
            }
        }

        if ($kilometrage !== '' && !is_numeric($kilometrage)) {
            $errors[] = "Le kilométrage doit être un nombre.";
        } elseif ($kilometrage !== '' && (int) $kilometrage < 0) {
            $errors[] = "Le kilométrage doit être positif.";
        }

        // Valider le format de l'immatriculation (TU: 123TU4567, RS: RS1234 à RS123456)
        $normalizedPlate = strtoupper(preg_replace('/[\s\-.]+/', '', $immatriculation));
        if (!empty($immatriculation)
            && !preg_match('/^\d{1,3}TU\d{1,4}$/', $normalizedPlate)
            && !preg_match('/^\d{1,3}RS\d{1,4}$/', $normalizedPlate)
            && !preg_match('/^RS\d{4,6}$/', $normalizedPlate)
        ) {
            $errors[] = "Le format de l'immatriculation est invalide (ex: 123TU4567 ou RS12345).";
        }

        if ($idClient !== null) {
            try {
                if (!$this->rdvService->findClientById($idClient)) {
                    $errors[] = "Client introuvable.";
                }
            } catch (Throwable $e) {
                $errors[] = "Validation client indisponible.";
            }
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'marque' => $marque,
                'modele' => $modele,
                'immatriculation' => $immatriculation,
                'couleur' => $couleur,
                'annee' => (int) $annee,
                'kilometrage' => (int) $kilometrage,
                'carburant' => $carburant,
                'id_client' => $idClient,
            ],
        ];
    }

    // -------------------------------------------------------
    // Ajouter un véhicule (Front Office)
    // -------------------------------------------------------
    public function addVehicle()
    {
        $this->requireClientSession();
        $errors = [];
        $success = '';
        $old = [];
        $brandSuggestions = $this->getBrandSuggestions();
        $next = (string) ($_GET['next'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];
            $old = $_POST;

            if (empty($errors)) {
                $d = $validation['sanitized'];
                $createdId = $this->create($d);

                if ($createdId > 0) {
                    if ($next === 'rdv') {
                        header('Location: index.php?action=frontCalendar&id_vehicule=' . $createdId . '&vehicle_added=1');
                        exit;
                    }

                    if ($next === 'client') {
                        $_SESSION['success'] = 'Véhicule ajouté avec succès.';
                        header('Location: /integration/client/controllers/UserController.php?action=showDashboard');
                        exit;
                    }

                    header('Location: index.php?action=showVehicles&vehicle_added=1');
                    exit;
                } else {
                    $errors[] = "Erreur lors de l'ajout du véhicule.";
                }
            }
        }

        require __DIR__ . '/../views/front/vehicle_add.php';
    }

    // -------------------------------------------------------
    // Afficher tous les véhicules (Liste Front Office)
    // -------------------------------------------------------
    public function showVehicles()
    {
        $this->requireClientSession();

        $vehicles = $this->listVehicles('', (int) $_SESSION['user_id']);
        $vehicleHealthById = $this->buildVehicleHealthOverview($vehicles);
        require __DIR__ . '/../views/front/vehicle_list.php';
    }

    public function vehicleHealth(): void
    {
        $this->requireClientSession();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $vehicle = $this->findVehicleById($id);

        if (!$vehicle || !$this->clientOwnsVehicle($vehicle, (int) $_SESSION['user_id'])) {
            header('Location: index.php?action=showVehicles&error=Vehicule introuvable');
            exit;
        }

        $history = $this->getVehicleInterventionHistory($id);
        $historyStats = $this->buildVehicleHistoryStats($history);
        $health = $this->calculateVehicleHealth($vehicle, $history);

        require __DIR__ . '/../views/front/vehicle_health.php';
    }
    // -------------------------------------------------------
    // Mettre à jour le véhicule (Back Office)
    // -------------------------------------------------------
    public function updateVehicle()
    {
        $errors = [];
        $success = '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $vehicle = $this->findVehicleById($id);
        $brandSuggestions = $this->getBrandSuggestions();
        $clients = $this->listClientsForSelect();

        if (!$vehicle) {
            header('Location: index.php?action=manageVehicles&error=Véhicule introuvable');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];

            if (empty($errors)) {
                $d = $validation['sanitized'];
                if ($this->updateVehicleRecord($id, $d)) {
                    $success = "Véhicule mis à jour avec succès !";
                    $vehicle = $this->findVehicleById($id);
                } else {
                    $errors[] = "Erreur lors de la mise à jour.";
                }
            } else {
                $vehicle = array_merge($vehicle, $_POST);
            }
        }

        require __DIR__ . '/../views/back/vehicle_edit.php';
    }

    public function addVehicleBack(): void
    {
        $errors = [];
        $success = '';
        $brandSuggestions = $this->getBrandSuggestions();
        $clients = $this->listClientsForSelect();
        $vehicle = [
            'id_client' => isset($_GET['id_client']) ? (int) $_GET['id_client'] : null,
            'marque' => '',
            'modele' => '',
            'immatriculation' => '',
            'couleur' => '',
            'annee' => '',
            'kilometrage' => '',
            'carburant' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];
            $vehicle = array_merge($vehicle, $_POST);

            if (empty($errors)) {
                $createdId = $this->create($validation['sanitized']);
                if ($createdId > 0) {
                    header('Location: index.php?action=vehicleDetail&id=' . $createdId . '&success=Vehicule ajoute avec succes');
                    exit;
                }
                $errors[] = "Erreur lors de l'ajout du vehicule.";
            }
        }

        require __DIR__ . '/../views/back/vehicle_add.php';
    }

    // -------------------------------------------------------
    // Supprimer le véhicule (Back Office)
    // -------------------------------------------------------
    public function deleteVehicle()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id > 0 && $this->deleteVehicleRecord($id)) {
            header('Location: index.php?action=manageVehicles&success=Véhicule supprimé avec succès');
        } else {
            header('Location: index.php?action=manageVehicles&error=Erreur lors de la suppression');
        }
        exit;
    }

    // -------------------------------------------------------
    // Back Office – liste de gestion des véhicules
    // -------------------------------------------------------
    public function manageVehicles()
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $vehicles = $this->listVehicles($search);
        $clientById = $this->buildClientMapFromRows($vehicles);
        $totalVehicles = $this->countVehicles();
        $success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
        $error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
        require __DIR__ . '/../views/back/vehicle_list.php';
    }

    // -------------------------------------------------------
    // Back Office - fiche vehicule + historique interventions
    // -------------------------------------------------------
    public function vehicleDetail(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $vehicle = $this->findVehicleById($id);

        if (!$vehicle) {
            header('Location: index.php?action=manageVehicles&error=Véhicule introuvable');
            exit;
        }

        $history = $this->getVehicleInterventionHistory($id);
        $historyStats = $this->buildVehicleHistoryStats($history);
        $ownerClient = !empty($vehicle['id_client']) ? $this->rdvService->findClientById((int) $vehicle['id_client']) : null;

        require __DIR__ . '/../views/back/vehicle_detail.php';
    }

    // -------------------------------------------------------
    // Tableau de bord (Back Office)
    // -------------------------------------------------------
    public function dashboard()
    {
        $vehicles = $this->listVehicles();
        $totalVehicles = count($vehicles);
        $holidays = $this->getTunisianHolidays((int) date('Y'));

        // ========== Statistiques Véhicules ==========
        $totalKm = 0;
        $fuelStats = [];
        $brandStats = [];
        foreach ($vehicles as $v) {
            $totalKm += $v['kilometrage'];
            $fuel = $v['carburant'];
            $brand = $v['marque'];
            $fuelStats[$fuel] = ($fuelStats[$fuel] ?? 0) + 1;
            $brandStats[$brand] = ($brandStats[$brand] ?? 0) + 1;
        }
        $avgKm = $totalVehicles > 0 ? round($totalKm / $totalVehicles) : 0;

        // ========== Statistiques Rendez-vous ==========
        $today = new DateTimeImmutable();
        $weekStart = $today->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $today->modify('sunday this week')->setTime(23, 59, 59);

        $rdvStats = $this->getQuickStats(
            $today->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            $today->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            $weekStart->format('Y-m-d H:i:s'),
            $weekEnd->format('Y-m-d H:i:s')
        );

        $totalRdvStmt = $this->db->query("SELECT COUNT(*) FROM rendezvous_digital");
        $totalRdv = (int) $totalRdvStmt->fetchColumn();

        $statuts = ['En attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'];
        $rdvParStatut = [];
        foreach ($statuts as $statut) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM rendezvous_digital WHERE statut = :statut");
            $stmt->execute([':statut' => $statut]);
            $rdvParStatut[$statut] = (int) $stmt->fetchColumn();
        }

        $relationStats = $this->getAdminRelationStats();
        $topActiveClients = $relationStats['top_active_clients'];
        $problematicVehicles = $relationStats['problematic_vehicles'];
        $avgUrgence = $relationStats['avg_urgence'];
        $avgVehiclesPerClient = $relationStats['avg_vehicles_per_client'];
        $avgRdvPerClient = $relationStats['avg_rdv_per_client'];

        require __DIR__ . '/../views/back/dashboard.php';
    }

    private function getTunisianHolidays(int $year): array
    {
        static $cache = [];

        if (isset($cache[$year])) {
            return $cache[$year];
        }

        $url = 'https://date.nager.at/api/v3/PublicHolidays/' . $year . '/TN';
        $raw = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $response = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $code >= 200 && $code < 300) {
                $raw = $response;
            }
        }

        if ($raw === '') {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 5,
                ],
            ]);
            $response = @file_get_contents($url, false, $context);
            if ($response !== false) {
                $raw = $response;
            }
        }

        if ($raw === '') {
            $cache[$year] = [];
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $cache[$year] = [];
            return [];
        }

        $holidays = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || empty($item['date'])) {
                continue;
            }
            $holidays[(string) $item['date']] = isset($item['localName']) && $item['localName'] !== ''
                ? (string) $item['localName']
                : 'Jour férié';
        }

        $cache[$year] = $holidays;
        return $holidays;
    }

    private function listVehicles(string $search = '', ?int $idClient = null): array
    {
        $search = trim($search);
        $filterByClient = $idClient !== null && $this->hasVehicleClientColumn();

        if ($search === '') {
            if ($filterByClient) {
                $stmt = $this->db->prepare('SELECT * FROM vehicle WHERE id_client = :id_client ORDER BY date_ajout ASC, id ASC');
                $stmt->execute([':id_client' => $idClient]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $stmt = $this->db->query('SELECT * FROM vehicle ORDER BY date_ajout ASC, id ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $normalizedPlateSearch = strtoupper(preg_replace('/[\s\-.]+/', '', $search));
        $sql = 'SELECT * FROM vehicle
                WHERE ' . ($filterByClient ? 'id_client = :id_client AND ' : '') . '(CAST(id AS CHAR) LIKE :search_id
                   OR marque LIKE :search_marque
                   OR modele LIKE :search_modele
                   OR immatriculation LIKE :search_immatriculation
                   OR REPLACE(REPLACE(REPLACE(UPPER(immatriculation), " ", ""), "-", ""), ".", "") LIKE :plate_search
                   OR couleur LIKE :search_couleur
                   OR CAST(annee AS CHAR) LIKE :search_annee
                   OR CAST(kilometrage AS CHAR) LIKE :search_kilometrage
                   OR carburant LIKE :search_carburant)
                ORDER BY date_ajout ASC, id ASC';

        $searchValue = '%' . $search . '%';
        $stmt = $this->db->prepare($sql);
        $params = [
            ':search_id' => $searchValue,
            ':search_marque' => $searchValue,
            ':search_modele' => $searchValue,
            ':search_immatriculation' => $searchValue,
            ':plate_search' => '%' . $normalizedPlateSearch . '%',
            ':search_couleur' => $searchValue,
            ':search_annee' => $searchValue,
            ':search_kilometrage' => $searchValue,
            ':search_carburant' => $searchValue,
        ];

        if ($filterByClient) {
            $params[':id_client'] = $idClient;
        }

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findVehicleById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function listClientsForSelect(): array
    {
        try {
            $config = (array) require __DIR__ . '/../config/client_database.php';
            $dsn = 'mysql:host=' . ($config['host'] ?? 'localhost') . ';dbname=' . ($config['dbname'] ?? 'garage1') . ';charset=' . ($config['charset'] ?? 'utf8mb4');
            $clientDb = new PDO($dsn, (string) ($config['username'] ?? 'root'), (string) ($config['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $table = preg_match('/^[A-Za-z0-9_]+$/', (string) ($config['table'] ?? 'user')) ? (string) ($config['table'] ?? 'user') : 'user';
            return $clientDb->query("SELECT id, nom, prenom, email, telephone, statut FROM {$table} WHERE post = 'client' ORDER BY nom ASC, prenom ASC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function buildClientMapFromRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $idClient = isset($row['id_client']) ? (int) $row['id_client'] : 0;
            if ($idClient <= 0 || isset($map[$idClient])) {
                continue;
            }

            try {
                $client = $this->rdvService->findClientById($idClient);
                if ($client) {
                    $map[$idClient] = $client;
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return $map;
    }

    private function getAdminRelationStats(): array
    {
        $clientCount = 0;
        try {
            $clientCount = count($this->listClientsForSelect());
        } catch (Throwable $e) {
            $clientCount = 0;
        }

        $totalVehicles = $this->countVehicles();
        $totalRdv = (int) $this->db->query('SELECT COUNT(*) FROM rendezvous_digital')->fetchColumn();
        $avgUrgence = 0.0;
        try {
            $avgUrgence = $this->hasRendezvousColumn('urgence_score')
                ? round((float) $this->db->query('SELECT AVG(COALESCE(urgence_score, 0)) FROM rendezvous_digital')->fetchColumn(), 1)
                : 0.0;
        } catch (Throwable $e) {
            $avgUrgence = 0.0;
        }

        $topRows = $this->hasRendezvousColumn('id_client')
            ? $this->db->query('SELECT id_client, COUNT(*) AS rdv_total FROM rendezvous_digital WHERE id_client IS NOT NULL GROUP BY id_client ORDER BY rdv_total DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC)
            : [];
        $topActiveClients = [];
        foreach ($topRows as $row) {
            $clientId = (int) ($row['id_client'] ?? 0);
            $client = $clientId > 0 ? $this->rdvService->findClientById($clientId) : null;
            $topActiveClients[] = [
                'id' => $clientId,
                'name' => $client ? trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) : 'Client #' . $clientId,
                'email' => $client['email'] ?? '-',
                'rdv_total' => (int) ($row['rdv_total'] ?? 0),
            ];
        }

        $vehicleClientSelect = $this->hasVehicleClientColumn() ? 'v.id_client' : 'NULL AS id_client';
        $urgenceExpr = $this->hasRendezvousColumn('urgence_score') ? 'COALESCE(r.urgence_score, 0)' : '0';
        $problematicVehicles = $this->db->query(
            'SELECT v.id, v.marque, v.modele, v.immatriculation, ' . $vehicleClientSelect . ',
                    COUNT(r.id_rdv) AS rdv_total,
                    AVG(' . $urgenceExpr . ') AS avg_urgence,
                    SUM(CASE WHEN ' . $urgenceExpr . ' >= 7 THEN 1 ELSE 0 END) AS urgent_total
             FROM vehicle v
             LEFT JOIN rendezvous_digital r ON r.id_vehicle = v.id
             GROUP BY v.id, v.marque, v.modele, v.immatriculation' . ($this->hasVehicleClientColumn() ? ', v.id_client' : '') . '
             HAVING rdv_total > 0
             ORDER BY urgent_total DESC, avg_urgence DESC, rdv_total DESC
             LIMIT 5'
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'top_active_clients' => $topActiveClients,
            'problematic_vehicles' => $problematicVehicles,
            'avg_urgence' => $avgUrgence,
            'avg_vehicles_per_client' => $clientCount > 0 ? round($totalVehicles / $clientCount, 1) : 0,
            'avg_rdv_per_client' => $clientCount > 0 ? round($totalRdv / $clientCount, 1) : 0,
        ];
    }

    private function getVehicleInterventionHistory(int $vehicleId): array
    {
        $select = [
            'r.id_rdv',
            'r.id_creneau',
            'r.type_intervention',
            'r.description_panne',
            $this->hasRendezvousColumn('circonstances_panne') ? 'r.circonstances_panne' : 'NULL AS circonstances_panne',
            $this->hasRendezvousColumn('temoins_panne') ? 'r.temoins_panne' : "'[]' AS temoins_panne",
            $this->hasRendezvousColumn('photos_json') ? 'r.photos_json' : "'[]' AS photos_json",
            $this->hasRendezvousColumn('urgence_score') ? 'r.urgence_score' : '0 AS urgence_score',
            $this->hasRendezvousColumn('urgence_details') ? 'r.urgence_details' : "'{}' AS urgence_details",
            'r.remise_eco_appliquee',
            'r.statut',
            'r.notes',
            'r.date_creation',
            'r.date_modification',
            'c.date_heure',
            'c.est_heure_creuse',
        ];

        $sql = 'SELECT ' . implode(', ', $select) . '
                FROM rendezvous_digital r
                LEFT JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                WHERE r.id_vehicle = :id_vehicle
                ORDER BY COALESCE(c.date_heure, r.date_creation) DESC, r.id_rdv DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_vehicle' => $vehicleId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buildVehicleHistoryStats(array $history): array
    {
        $stats = [
            'total' => count($history),
            'active' => 0,
            'done' => 0,
            'canceled' => 0,
            'urgent' => 0,
            'last_date' => null,
        ];

        foreach ($history as $row) {
            $statusKey = $this->normalizeKey((string) ($row['statut'] ?? ''));
            if (in_array($statusKey, ['en attente', 'confirme', 'en cours'], true)) {
                $stats['active']++;
            } elseif ($statusKey === 'termine') {
                $stats['done']++;
            } elseif ($statusKey === 'annule') {
                $stats['canceled']++;
            }

            if ((int) ($row['urgence_score'] ?? 0) >= 7) {
                $stats['urgent']++;
            }

            if ($stats['last_date'] === null) {
                $date = $row['date_heure'] ?? $row['date_creation'] ?? null;
                if (!empty($date)) {
                    $stats['last_date'] = (string) $date;
                }
            }
        }

        return $stats;
    }

    private function buildVehicleHealthOverview(array $vehicles): array
    {
        $overview = [];
        foreach ($vehicles as $vehicle) {
            $id = (int) ($vehicle['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $history = $this->getVehicleInterventionHistory($id);
            $overview[$id] = $this->calculateVehicleHealth($vehicle, $history);
        }

        return $overview;
    }

    private function calculateVehicleHealth(array $vehicle, array $history): array
    {
        $year = (int) ($vehicle['annee'] ?? date('Y'));
        $km = max(0, (int) ($vehicle['kilometrage'] ?? 0));
        $age = max(0, (int) date('Y') - $year);
        $urgentTotal = 0;
        $activeTotal = 0;
        $doneTotal = 0;
        $lastDate = null;

        foreach ($history as $row) {
            $statusKey = $this->normalizeKey((string) ($row['statut'] ?? ''));
            if ((int) ($row['urgence_score'] ?? 0) >= 7) {
                $urgentTotal++;
            }
            if (in_array($statusKey, ['en attente', 'confirme', 'en cours'], true)) {
                $activeTotal++;
            }
            if ($statusKey === 'termine') {
                $doneTotal++;
            }
            if ($lastDate === null && !empty($row['date_heure'])) {
                $lastDate = (string) $row['date_heure'];
            }
        }

        $kmPenalty = min(35, (int) floor($km / 12000));
        $agePenalty = min(25, $age * 2);
        $urgentPenalty = min(28, $urgentTotal * 7);
        $activePenalty = min(12, $activeTotal * 4);
        $score = max(0, min(100, 100 - $kmPenalty - $agePenalty - $urgentPenalty - $activePenalty));

        if ($score >= 85) {
            $label = 'Excellent';
            $class = 'health-good';
            $message = 'Votre voiture est en tres bon etat selon son historique.';
        } elseif ($score >= 70) {
            $label = 'Bon';
            $class = 'health-ok';
            $message = 'Etat global rassurant, continuez l entretien regulier.';
        } elseif ($score >= 50) {
            $label = 'A surveiller';
            $class = 'health-watch';
            $message = 'Quelques signaux meritent une verification prochainement.';
        } else {
            $label = 'Critique';
            $class = 'health-risk';
            $message = 'Un controle garage est recommande rapidement.';
        }

        $nextOilKm = 10000 - ($km % 10000);
        if ($nextOilKm === 10000 && $km > 0) {
            $nextOilKm = 0;
        }

        $recommendations = [];
        if ($nextOilKm <= 1000) {
            $recommendations[] = 'Vidange a prevoir maintenant ou tres prochainement.';
        } else {
            $recommendations[] = 'Prochaine vidange estimee dans environ ' . number_format($nextOilKm, 0, ',', ' ') . ' km.';
        }
        if ($urgentTotal > 0) {
            $recommendations[] = $urgentTotal . ' intervention(s) urgente(s) dans l historique : controle conseille.';
        }
        if ($age >= 8) {
            $recommendations[] = 'Vehicule age de ' . $age . ' ans : surveillez pneus, freins, batterie et liquides.';
        }
        if ($activeTotal > 0) {
            $recommendations[] = $activeTotal . ' rendez-vous actif(s) en cours de suivi.';
        }
        if ($doneTotal === 0 && count($history) === 0) {
            $recommendations[] = 'Aucun historique atelier pour le moment : ajoutez vos RDV pour un suivi plus precis.';
        }

        return [
            'score' => $score,
            'label' => $label,
            'class' => $class,
            'message' => $message,
            'age' => $age,
            'kilometrage' => $km,
            'total_rdv' => count($history),
            'urgent_total' => $urgentTotal,
            'active_total' => $activeTotal,
            'done_total' => $doneTotal,
            'last_date' => $lastDate,
            'next_oil_km' => $nextOilKm,
            'recommendations' => $recommendations,
        ];
    }

    private function clientOwnsVehicle(array $vehicle, int $clientId): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        if (!$this->hasVehicleClientColumn()) {
            return true;
        }

        return isset($vehicle['id_client']) && (int) $vehicle['id_client'] === $clientId;
    }

    private function hasRendezvousColumn(string $column): bool
    {
        if (array_key_exists($column, $this->rdvColumnCache)) {
            return $this->rdvColumnCache[$column];
        }

        try {
            $stmt = $this->db->prepare('SHOW COLUMNS FROM rendezvous_digital LIKE :column_name');
            $stmt->execute([':column_name' => $column]);
            $this->rdvColumnCache[$column] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->rdvColumnCache[$column] = false;
        }

        return $this->rdvColumnCache[$column];
    }

    private function countVehicles(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM vehicle');
        return (int) $stmt->fetchColumn();
    }

    private function updateVehicleRecord(int $id, array $data): bool
    {
        $fields = [
            'marque = :marque',
            'modele = :modele',
            'immatriculation = :immatriculation',
            'couleur = :couleur',
            'annee = :annee',
            'kilometrage = :kilometrage',
            'carburant = :carburant',
        ];

        if ($this->hasVehicleClientColumn()) {
            $fields[] = 'id_client = :id_client';
        }

        $sql = 'UPDATE vehicle SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        $params = [
            ':marque' => $data['marque'],
            ':modele' => $data['modele'],
            ':immatriculation' => $this->normalizePlate($data['immatriculation']),
            ':couleur' => $data['couleur'],
            ':annee' => (int) $data['annee'],
            ':kilometrage' => (int) $data['kilometrage'],
            ':carburant' => $data['carburant'],
            ':id' => $id,
        ];

        if ($this->hasVehicleClientColumn()) {
            $params[':id_client'] = isset($data['id_client']) ? (int) $data['id_client'] : null;
        }

        return $stmt->execute($params);
    }

    private function deleteVehicleRecord(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM vehicle WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function normalizePlate(string $plate): string
    {
        $plate = strtoupper(trim($plate));
        $plate = preg_replace('/\s+/', '', $plate);
        $plate = str_replace(['-', '.'], '', $plate);

        if (preg_match('/^(\d{1,3})TU(\d{1,4})$/', $plate, $tuMatches) === 1) {
            return $tuMatches[1] . 'TU' . $tuMatches[2];
        }

        if (preg_match('/^(\d{1,3})RS(\d{1,4})$/', $plate, $legacyRsMatches) === 1) {
            return $legacyRsMatches[1] . 'RS' . $legacyRsMatches[2];
        }

        if (preg_match('/^RS(\d{4,6})$/', $plate, $rsMatches) === 1) {
            return 'RS' . $rsMatches[1];
        }

        return $plate;
    }

    private function normalizeKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        $translit = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($translit !== false) {
            $value = $translit;
        }

        $value = preg_replace('/[^a-z0-9\s-]+/', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function findByImmatriculation(string $immatriculation): ?array
    {
        $normalized = $this->normalizePlate($immatriculation);

        $sql = 'SELECT * FROM vehicle WHERE UPPER(REPLACE(immatriculation, "  ", " ")) = :immatriculation LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':immatriculation' => $normalized]);

        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vehicle ?: null;
    }

    private function create(array $data): int
    {
        $columns = ['marque', 'modele', 'immatriculation', 'couleur', 'annee', 'kilometrage', 'carburant', 'date_ajout'];
        $placeholders = [':marque', ':modele', ':immatriculation', ':couleur', ':annee', ':kilometrage', ':carburant', 'NOW()'];

        if ($this->hasVehicleClientColumn()) {
            array_splice($columns, 1, 0, 'id_client');
            array_splice($placeholders, 1, 0, ':id_client');
        }

        $sql = 'INSERT INTO vehicle (' . implode(', ', $columns) . ')
                VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->db->prepare($sql);
        $params = [
            ':marque' => $data['marque'],
            ':modele' => $data['modele'],
            ':immatriculation' => $this->normalizePlate($data['immatriculation']),
            ':couleur' => $data['couleur'] ?? 'N/A',
            ':annee' => (int) $data['annee'],
            ':kilometrage' => (int) $data['kilometrage'],
            ':carburant' => $data['carburant'],
        ];

        if ($this->hasVehicleClientColumn()) {
            $params[':id_client'] = isset($data['id_client']) ? (int) $data['id_client'] : null;
        }

        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    private function findOrCreate(array $data): int
    {
        $existing = $this->findByImmatriculation($data['immatriculation']);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $this->create($data);
    }

    private function getQuickStats(string $dayStart, string $dayEnd, string $weekStart, string $weekEnd): array
    {
        $stats = [];

        $stmtDay = $this->db->prepare('SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau WHERE c.date_heure BETWEEN :start_day AND :end_day');
        $stmtDay->execute([
            ':start_day' => $dayStart,
            ':end_day' => $dayEnd,
        ]);
        $stats['rdv_jour'] = (int) $stmtDay->fetchColumn();

        $stmtWeek = $this->db->prepare('SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau WHERE c.date_heure BETWEEN :start_week AND :end_week');
        $stmtWeek->execute([
            ':start_week' => $weekStart,
            ':end_week' => $weekEnd,
        ]);
        $stats['rdv_semaine'] = (int) $stmtWeek->fetchColumn();

        $stmtPending = $this->db->query("SELECT COUNT(*) FROM rendezvous_digital WHERE statut = 'En attente'");
        $stats['rdv_attente'] = (int) $stmtPending->fetchColumn();

        $stmtFill = $this->db->prepare("SELECT
                COALESCE(SUM(c.capacite_max), 0) AS total_capacity,
                COALESCE(SUM(active_counts.nb_rdv), 0) AS total_rdv
            FROM creneau_atelier c
            LEFT JOIN (
                SELECT id_creneau, COUNT(*) AS nb_rdv
                FROM rendezvous_digital
                WHERE statut IN ('En attente', 'Confirmé', 'En cours')
                GROUP BY id_creneau
            ) active_counts ON active_counts.id_creneau = c.id_creneau
            WHERE c.date_heure BETWEEN :start_week AND :end_week");
        $stmtFill->execute([
            ':start_week' => $weekStart,
            ':end_week' => $weekEnd,
        ]);
        $fillData = $stmtFill->fetch(PDO::FETCH_ASSOC);

        $capacity = (int) ($fillData['total_capacity'] ?? 0);
        $totalRdv = (int) ($fillData['total_rdv'] ?? 0);
        $stats['taux_remplissage'] = $capacity > 0 ? round(($totalRdv / $capacity) * 100, 2) : 0;

        return $stats;
    }

    private function getBrandSuggestions(): array
    {
        $defaultBrands = [
            'Alfa Romeo', 'Audi', 'BMW', 'BYD', 'Chery', 'Chevrolet', 'Citroen', 'Cupra',
            'Dacia', 'DFSK', 'Fiat', 'Ford', 'Geely', 'Great Wall', 'Honda', 'Hyundai',
            'Isuzu', 'Jaguar', 'Jeep', 'Kia', 'Land Rover', 'Lexus', 'Mahindra', 'Mazda',
            'Mercedes-Benz', 'MG', 'Mini', 'Mitsubishi', 'Nissan', 'Opel', 'Peugeot',
            'Porsche', 'Renault', 'Seat', 'Skoda', 'SsangYong', 'Suzuki', 'Tesla',
            'Toyota', 'Volkswagen', 'Volvo'
        ];

        $brands = [];
        foreach ($defaultBrands as $brand) {
            $brands[strtolower($brand)] = $brand;
        }

        $stmt = $this->db->query('SELECT DISTINCT marque FROM vehicle WHERE marque IS NOT NULL AND TRIM(marque) <> "" ORDER BY marque ASC');
        $existingBrands = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($existingBrands as $brand) {
            $brand = trim((string) $brand);
            if ($brand === '') {
                continue;
            }

            $brands[strtolower($brand)] = $brand;
        }

        natcasesort($brands);

        return array_values($brands);
    }

    private function hasVehicleClientColumn(): bool
    {
        if ($this->hasVehicleClientColumnCache !== null) {
            return $this->hasVehicleClientColumnCache;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM vehicle LIKE 'id_client'");
            $this->hasVehicleClientColumnCache = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->hasVehicleClientColumnCache = false;
        }

        return $this->hasVehicleClientColumnCache;
    }

    private function requireClientSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: /integration/client/controllers/UserController.php?action=showLogin');
            exit;
        }
    }
}
