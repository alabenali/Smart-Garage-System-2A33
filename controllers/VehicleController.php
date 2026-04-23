<?php
// ============================================
// Contrôleur de Véhicule
// ============================================

require_once __DIR__ . '/../config/Database.php';

class VehicleController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
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
            ],
        ];
    }

    // -------------------------------------------------------
    // Ajouter un véhicule (Front Office)
    // -------------------------------------------------------
    public function addVehicle()
    {
        $errors = [];
        $success = '';
        $old = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];
            $old = $_POST;

            if (empty($errors)) {
                $d = $validation['sanitized'];
                $createdId = $this->create($d);

                if ($createdId > 0) {
                    $success = "Véhicule ajouté avec succès !";
                    $old = [];
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
        $vehicles = $this->listVehicles();
        require __DIR__ . '/../views/front/vehicle_list.php';
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
        $vehicles = $this->listVehicles();
        $success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
        $error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
        require __DIR__ . '/../views/back/vehicle_list.php';
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

    private function listVehicles(): array
    {
        $stmt = $this->db->query('SELECT * FROM vehicle ORDER BY date_ajout ASC, id ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findVehicleById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM vehicle WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function updateVehicleRecord(int $id, array $data): bool
    {
        $sql = 'UPDATE vehicle SET marque = :marque, modele = :modele, immatriculation = :immatriculation, couleur = :couleur, annee = :annee, kilometrage = :kilometrage, carburant = :carburant WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':marque' => $data['marque'],
            ':modele' => $data['modele'],
            ':immatriculation' => $this->normalizePlate($data['immatriculation']),
            ':couleur' => $data['couleur'],
            ':annee' => (int) $data['annee'],
            ':kilometrage' => (int) $data['kilometrage'],
            ':carburant' => $data['carburant'],
            ':id' => $id,
        ]);
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
        $sql = 'INSERT INTO vehicle (marque, modele, immatriculation, couleur, annee, kilometrage, carburant, date_ajout)
                VALUES (:marque, :modele, :immatriculation, :couleur, :annee, :kilometrage, :carburant, NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':marque' => $data['marque'],
            ':modele' => $data['modele'],
            ':immatriculation' => $this->normalizePlate($data['immatriculation']),
            ':couleur' => $data['couleur'] ?? 'N/A',
            ':annee' => (int) $data['annee'],
            ':kilometrage' => (int) $data['kilometrage'],
            ':carburant' => $data['carburant'],
        ]);

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
}
