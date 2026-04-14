<?php

require_once __DIR__ . '/../config/Database.php';

class CalendrierController
{
    private PDO $db;

    private array $allowedStatuses = ['En attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'];
    private array $allowedInterventions = ['Vidange', 'Révision', 'Freinage', 'Climatisation', 'Carrosserie', 'Autre'];

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function frontCalendar(): void
    {
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $selectedDate = isset($_GET['selected_date']) ? $_GET['selected_date'] : date('Y-m-d');

        if ($month < 1 || $month > 12) {
            $month = (int) date('m');
        }

        if ($year < (int) date('Y') - 1 || $year > (int) date('Y') + 2) {
            $year = (int) date('Y');
        }

        $this->ensureMonthSlots($month, $year);
        $holidays = $this->getTunisianHolidays($year);

        if (!$this->isValidDate($selectedDate)) {
            $selectedDate = date('Y-m-d');
        }

        $monthAvailability = $this->getMonthAvailability($month, $year);
        $daySlots = $this->getDaySlots($selectedDate);

        $errors = $_SESSION['rdv_errors'] ?? [];
        $old = $_SESSION['rdv_old'] ?? [];
        unset($_SESSION['rdv_errors'], $_SESSION['rdv_old']);

        require __DIR__ . '/../views/front/calendrier.php';
    }

    public function frontCreate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        $validation = $this->validateRdvRequest($_POST, false);
        if (!empty($validation['errors'])) {
            $_SESSION['rdv_errors'] = $validation['errors'];
            $_SESSION['rdv_old'] = $_POST;
            $month = date('m');
            $year = date('Y');
            $selectedDate = !empty($_POST['selected_date']) ? $_POST['selected_date'] : date('Y-m-d');
            header("Location: index.php?action=frontCalendar&month={$month}&year={$year}&selected_date={$selectedDate}");
            exit;
        }

        $payload = $validation['payload'];

        $slot = $this->findCreneauById((int) $payload['id_creneau']);
        if (!$slot) {
            $_SESSION['rdv_errors'] = ['Créneau introuvable.'];
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        $slotDate = date('Y-m-d', strtotime($slot['date_heure']));
        $slotYear = (int) date('Y', strtotime($slot['date_heure']));
        $slotHolidays = $this->getTunisianHolidays($slotYear);
        if (isset($slotHolidays[$slotDate])) {
            $_SESSION['rdv_errors'] = ['Ce jour est férié, veuillez choisir une autre date.'];
            header('Location: index.php?action=frontCalendar&month=' . date('m', strtotime($slot['date_heure'])) . '&year=' . $slotYear . '&selected_date=' . $slotDate);
            exit;
        }

        if (strtotime($slot['date_heure']) < time()) {
            $_SESSION['rdv_errors'] = ['Ce créneau est déjà passé.'];
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        $activeCount = $this->countActiveByCreneau((int) $slot['id_creneau']);
        if ($activeCount >= (int) $slot['capacite_max']) {
            $_SESSION['rdv_errors'] = ['Ce créneau est complet.'];
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        $vehicleId = $this->findOrCreateVehicle($payload['vehicle']);

        $rdvId = $this->createRendezvous([
            'id_creneau' => (int) $slot['id_creneau'],
            'nom_client' => $payload['nom_client'],
            'prenom_client' => $payload['prenom_client'],
            'telephone_client' => $payload['telephone_client'],
            'email_client' => $payload['email_client'],
            'id_vehicle' => $vehicleId,
            'type_intervention' => $payload['type_intervention'],
            'description_panne' => $payload['description_panne'],
            'remise_eco_appliquee' => ((int) $slot['est_heure_creuse'] === 1) ? 15.00 : 0.00,
            'statut' => 'En attente',
            'notes' => null,
        ]);

        header('Location: index.php?action=frontConfirmation&id=' . $rdvId);
        exit;
    }

    public function frontConfirmation(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $rdv = $this->findDetailedById($id);

        if (!$rdv) {
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        require __DIR__ . '/../views/front/confirmation.php';
    }

    public function apiMonthAvailability(): void
    {
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

        $this->ensureMonthSlots($month, $year);
        $data = $this->getMonthAvailability($month, $year);

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function apiDaySlots(): void
    {
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        if (!$this->isValidDate($date)) {
            $this->jsonResponse(['success' => false, 'message' => 'Date invalide'], 422);
            return;
        }

        $this->ensureDaySlots($date);
        $slots = $this->getDaySlots($date);

        $this->jsonResponse(['success' => true, 'data' => $slots]);
    }

    public function backCalendar(): void
    {
        $weekDate = isset($_GET['week_date']) && $this->isValidDate($_GET['week_date'])
            ? $_GET['week_date']
            : date('Y-m-d');

        $selected = new DateTimeImmutable($weekDate);
        $weekStart = $selected->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = $weekStart->modify('+5 days')->setTime(23, 59, 59);

        $this->ensureWeekSlots($weekStart, $weekEnd);

        $gridRows = $this->getWeekGridCounts(
            $weekStart->format('Y-m-d H:i:s'),
            $weekEnd->format('Y-m-d H:i:s')
        );

        $today = new DateTimeImmutable();
        $stats = $this->getQuickStats(
            $today->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            $today->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            $weekStart->format('Y-m-d H:i:s'),
            $weekEnd->format('Y-m-d H:i:s')
        );

        $weekDays = [];
        for ($i = 0; $i < 6; $i++) {
            $weekDays[] = $weekStart->modify('+' . $i . ' day');
        }

        $holidays = [];
        foreach ($weekDays as $day) {
            $holidays = $holidays + $this->getTunisianHolidays((int) $day->format('Y'));
        }

        $grid = [];
        foreach ($gridRows as $row) {
            $dayKey = date('Y-m-d', strtotime($row['date_heure']));
            $hourKey = date('H:i', strtotime($row['date_heure']));
            if (!isset($grid[$hourKey])) {
                $grid[$hourKey] = [];
            }
            $grid[$hourKey][$dayKey] = $row;
        }

        $hours = [];
        for ($h = 8; $h <= 17; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        $manualErrors = $_SESSION['manual_rdv_errors'] ?? [];
        $manualOld = $_SESSION['manual_rdv_old'] ?? [];
        unset($_SESSION['manual_rdv_errors'], $_SESSION['manual_rdv_old']);

        require __DIR__ . '/../views/back/calendrier_admin.php';
    }

    public function backSlotDetails(): void
    {
        $idCreneau = isset($_GET['id_creneau']) ? (int) $_GET['id_creneau'] : 0;
        $slot = $this->findCreneauById($idCreneau);
        if (!$slot) {
            $this->jsonResponse(['success' => false, 'message' => 'Créneau introuvable'], 404);
            return;
        }

        $rdvs = $this->getByCreneau($idCreneau);

        ob_start();
        require __DIR__ . '/../views/back/rdv_detail_modal.php';
        $html = ob_get_clean();

        $this->jsonResponse([
            'success' => true,
            'html' => $html,
            'slot' => $slot,
        ]);
    }

    public function backUpdateStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }

        $idRdv = isset($_POST['id_rdv']) ? (int) $_POST['id_rdv'] : 0;
        $status = isset($_POST['statut']) ? trim($_POST['statut']) : '';

        if ($idRdv <= 0 || !in_array($status, $this->allowedStatuses, true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Paramètres invalides'], 422);
            return;
        }

        $ok = $this->updateStatus($idRdv, $status);
        $this->jsonResponse(['success' => $ok]);
    }

    public function backBlockSlot(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $idCreneau = isset($_POST['id_creneau']) ? (int) $_POST['id_creneau'] : 0;
        $capacity = isset($_POST['capacite_max']) ? (int) $_POST['capacite_max'] : 0;

        if ($idCreneau > 0 && $capacity >= 0) {
            $this->updateCapacity($idCreneau, $capacity);
        }

        header('Location: index.php?action=backCalendar');
        exit;
    }

    public function backCreateManual(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $validation = $this->validateRdvRequest($_POST, true);
        if (!empty($validation['errors'])) {
            $_SESSION['manual_rdv_errors'] = $validation['errors'];
            $_SESSION['manual_rdv_old'] = $_POST;
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $payload = $validation['payload'];

        $idCreneau = 0;
        if (!empty($payload['id_creneau'])) {
            $idCreneau = (int) $payload['id_creneau'];
        } elseif (!empty($payload['date_heure_manual'])) {
            $idCreneau = $this->findOrCreateByDateTime($payload['date_heure_manual']);
        }

        $slot = $this->findCreneauById($idCreneau);
        if (!$slot) {
            $_SESSION['manual_rdv_errors'] = ['Impossible de créer ce créneau.'];
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $slotDate = date('Y-m-d', strtotime($slot['date_heure']));
        $slotYear = (int) date('Y', strtotime($slot['date_heure']));
        $slotHolidays = $this->getTunisianHolidays($slotYear);
        if (isset($slotHolidays[$slotDate])) {
            $_SESSION['manual_rdv_errors'] = ['Ce jour est férié, veuillez choisir une autre date.'];
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $activeCount = $this->countActiveByCreneau((int) $slot['id_creneau']);
        if ((int) $slot['capacite_max'] === 0 || $activeCount >= (int) $slot['capacite_max']) {
            $_SESSION['manual_rdv_errors'] = ['Créneau bloqué ou complet.'];
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $vehicleId = $this->findOrCreateVehicle($payload['vehicle']);

        $this->createRendezvous([
            'id_creneau' => (int) $slot['id_creneau'],
            'nom_client' => $payload['nom_client'],
            'prenom_client' => $payload['prenom_client'],
            'telephone_client' => $payload['telephone_client'],
            'email_client' => $payload['email_client'],
            'id_vehicle' => $vehicleId,
            'type_intervention' => $payload['type_intervention'],
            'description_panne' => $payload['description_panne'],
            'remise_eco_appliquee' => ((int) $slot['est_heure_creuse'] === 1) ? 15.00 : 0.00,
            'statut' => 'Confirmé',
            'notes' => null,
        ]);

        header('Location: index.php?action=backCalendar');
        exit;
    }

    public function backList(): void
    {
        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
            'date' => isset($_GET['date']) ? trim($_GET['date']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $total = $this->countFiltered($filters);
        $rdvs = $this->getFiltered($filters, $perPage, $offset);
        $totalPages = (int) ceil($total / $perPage);

        require __DIR__ . '/../views/back/rdv_liste.php';
    }

    public function backExportCsv(): void
    {
        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
            'date' => isset($_GET['date']) ? trim($_GET['date']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        $rows = $this->getFilteredForExport($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=rdv_export_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date/Heure', 'Nom', 'Prénom', 'Téléphone', 'Email', 'Immatriculation', 'Marque', 'Modèle', 'Intervention', 'Statut', 'Remise %'], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['date_heure'],
                $row['nom_client'],
                $row['prenom_client'],
                $row['telephone_client'],
                $row['email_client'],
                $row['immatriculation'],
                $row['marque'],
                $row['modele'],
                $row['type_intervention'],
                $row['statut'],
                $row['remise_eco_appliquee'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    private function validateRdvRequest(array $input, bool $manualMode): array
    {
        $errors = [];

        $nomClient = $this->sanitize($input['nom_client'] ?? '');
        $prenomClient = $this->sanitize($input['prenom_client'] ?? '');
        $telephoneClient = preg_replace('/\D+/', '', $input['telephone_client'] ?? '');
        $emailClient = trim($input['email_client'] ?? '');
        $typeIntervention = $this->sanitize($input['type_intervention'] ?? '');
        $descriptionPanne = $this->sanitize($input['description_panne'] ?? '');
        $immatriculation = $this->sanitize($input['immatriculation'] ?? '');

        if ($nomClient === '') {
            $errors[] = 'Le nom du client est obligatoire.';
        }
        if ($prenomClient === '') {
            $errors[] = 'Le prénom du client est obligatoire.';
        }
        if (!preg_match('/^\d{8}$/', $telephoneClient)) {
            $errors[] = 'Le téléphone doit contenir exactement 8 chiffres (format tunisien).';
        }
        if ($emailClient === '') {
            $errors[] = 'L\'email du client est obligatoire.';
        } elseif (!filter_var($emailClient, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if (!in_array($typeIntervention, $this->allowedInterventions, true)) {
            $errors[] = 'Type d\'intervention invalide.';
        }

        $idCreneau = isset($input['id_creneau']) ? (int) $input['id_creneau'] : 0;
        $dateHeureManual = trim($input['date_heure_manual'] ?? '');

        if ($manualMode) {
            if ($idCreneau <= 0 && $dateHeureManual === '') {
                $errors[] = 'Sélectionnez un créneau ou renseignez une date/heure manuelle.';
            }
            if ($dateHeureManual !== '' && !$this->isValidDateTime($dateHeureManual)) {
                $errors[] = 'Date/heure manuelle invalide.';
            }
        } else {
            if ($idCreneau <= 0) {
                $errors[] = 'Veuillez sélectionner un créneau.';
            }
        }

        if ($immatriculation === '') {
            $errors[] = 'L\'immatriculation est obligatoire.';
        }

        $existingVehicle = null;
        if ($immatriculation !== '') {
            $existingVehicle = $this->findVehicleByImmatriculation($immatriculation);
        }

        $vehicle = [
            'immatriculation' => $immatriculation,
        ];

        if ($existingVehicle) {
            $vehicle += [
                'marque' => $existingVehicle['marque'],
                'modele' => $existingVehicle['modele'],
                'annee' => $existingVehicle['annee'],
                'kilometrage' => $existingVehicle['kilometrage'],
                'carburant' => $existingVehicle['carburant'],
                'couleur' => $existingVehicle['couleur'] ?? 'N/A',
            ];
        } else {
            $marque = $this->sanitize($input['marque'] ?? '');
            $modele = $this->sanitize($input['modele'] ?? '');
            $annee = (int) ($input['annee'] ?? 0);
            $kilometrage = (int) ($input['kilometrage'] ?? 0);
            $carburant = $this->sanitize($input['carburant'] ?? '');

            if ($marque === '' || $modele === '' || $annee <= 0 || $kilometrage < 0 || $carburant === '') {
                $errors[] = 'Véhicule inconnu: marque, modèle, année, kilométrage et carburant sont requis.';
            }

            $vehicle += [
                'marque' => $marque,
                'modele' => $modele,
                'annee' => $annee,
                'kilometrage' => $kilometrage,
                'carburant' => $carburant,
                'couleur' => $this->sanitize($input['couleur'] ?? 'N/A'),
            ];
        }

        return [
            'errors' => $errors,
            'payload' => [
                'id_creneau' => $idCreneau,
                'date_heure_manual' => $dateHeureManual,
                'nom_client' => $nomClient,
                'prenom_client' => $prenomClient,
                'telephone_client' => $telephoneClient,
                'email_client' => $emailClient,
                'type_intervention' => $typeIntervention,
                'description_panne' => $descriptionPanne,
                'vehicle' => $vehicle,
            ],
        ];
    }

    private function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
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

            $dateKey = (string) $item['date'];
            $holidayName = isset($item['localName']) && $item['localName'] !== ''
                ? (string) $item['localName']
                : 'Jour férié';

            $holidays[$dateKey] = $holidayName;
        }

        $cache[$year] = $holidays;
        return $holidays;
    }

    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function isValidDateTime(string $dateTime): bool
    {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $dateTime);
        return $d && $d->format('Y-m-d\TH:i') === $dateTime;
    }

    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function normalizePlate(string $plate): string
    {
        $plate = strtoupper(trim($plate));
        $plate = preg_replace('/\s+/', ' ', $plate);
        return $plate;
    }

    private function findVehicleByImmatriculation(string $immatriculation): ?array
    {
        $normalized = $this->normalizePlate($immatriculation);

        $sql = 'SELECT * FROM vehicle WHERE UPPER(REPLACE(immatriculation, "  ", " ")) = :immatriculation LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':immatriculation' => $normalized]);

        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vehicle ?: null;
    }

    private function createVehicle(array $data): int
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

    private function findOrCreateVehicle(array $data): int
    {
        $existing = $this->findVehicleByImmatriculation($data['immatriculation']);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $this->createVehicle($data);
    }

    private function ensureMonthSlots(int $month, int $year): void
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $end = $start->modify('last day of this month 23:59:59');
        $this->ensureRangeSlots($start, $end);
    }

    private function ensureWeekSlots(DateTimeImmutable $weekStart, DateTimeImmutable $weekEnd): void
    {
        $this->ensureRangeSlots($weekStart, $weekEnd);
    }

    private function ensureRangeSlots(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        $current = $start->setTime(0, 0, 0);
        $last = $end->setTime(0, 0, 0);

        while ($current <= $last) {
            if ((int) $current->format('N') !== 7) {
                $currentDate = $current->format('Y-m-d');
                $holidays = $this->getTunisianHolidays((int) $current->format('Y'));
                if (!isset($holidays[$currentDate])) {
                    $this->ensureDaySlots($currentDate);
                }
            }
            $current = $current->modify('+1 day');
        }
    }

    private function ensureDaySlots(string $date): void
    {
        $dateObj = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if ($dateObj instanceof DateTimeImmutable) {
            $holidays = $this->getTunisianHolidays((int) $dateObj->format('Y'));
            if (isset($holidays[$dateObj->format('Y-m-d')])) {
                return;
            }
        }

        $checkStmt = $this->db->prepare('SELECT id_creneau FROM creneau_atelier WHERE date_heure = :date_heure LIMIT 1');
        $insertStmt = $this->db->prepare('INSERT INTO creneau_atelier (date_heure, est_heure_creuse, capacite_max) VALUES (:date_heure, :est_heure_creuse, :capacite_max)');

        for ($hour = 8; $hour <= 17; $hour++) {
            $dateTime = sprintf('%s %02d:00:00', $date, $hour);
            $checkStmt->execute([':date_heure' => $dateTime]);

            if (!$checkStmt->fetch()) {
                $isOffPeak = ($hour >= 13 && $hour < 15) ? 1 : 0;
                $insertStmt->execute([
                    ':date_heure' => $dateTime,
                    ':est_heure_creuse' => $isOffPeak,
                    ':capacite_max' => 3,
                ]);
            }
        }
    }

    private function getMonthAvailability(int $month, int $year): array
    {
        $sql = "SELECT
                  c.id_creneau,
                  c.date_heure,
                  c.est_heure_creuse,
                  c.capacite_max,
                  COUNT(r.id_rdv) AS nb_rdv_pris,
                  (c.capacite_max - COUNT(r.id_rdv)) AS places_restantes
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r
                  ON r.id_creneau = c.id_creneau
                  AND r.statut IN ('En attente', 'Confirmé', 'En cours')
                WHERE c.date_heure >= NOW()
                  AND MONTH(c.date_heure) = :mois
                  AND YEAR(c.date_heure) = :annee
                GROUP BY c.id_creneau
                ORDER BY c.date_heure ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':mois' => $month,
            ':annee' => $year,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDaySlots(string $date): array
    {
        $sql = "SELECT
                  c.id_creneau,
                  c.date_heure,
                  c.est_heure_creuse,
                  c.capacite_max,
                  COUNT(r.id_rdv) AS nb_rdv_pris,
                  (c.capacite_max - COUNT(r.id_rdv)) AS places_restantes
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r
                  ON r.id_creneau = c.id_creneau
                  AND r.statut IN ('En attente', 'Confirmé', 'En cours')
                WHERE DATE(c.date_heure) = :date
                  AND c.date_heure >= NOW()
                GROUP BY c.id_creneau
                ORDER BY c.date_heure ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findCreneauById(int $idCreneau): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM creneau_atelier WHERE id_creneau = :id_creneau LIMIT 1');
        $stmt->execute([':id_creneau' => $idCreneau]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function findOrCreateByDateTime(string $dateTime): int
    {
        $select = $this->db->prepare('SELECT id_creneau FROM creneau_atelier WHERE date_heure = :date_heure LIMIT 1');
        $select->execute([':date_heure' => $dateTime]);
        $found = $select->fetch(PDO::FETCH_ASSOC);
        if ($found) {
            return (int) $found['id_creneau'];
        }

        $hour = (int) date('H', strtotime($dateTime));
        $isOffPeak = ($hour >= 13 && $hour < 15) ? 1 : 0;

        $insert = $this->db->prepare('INSERT INTO creneau_atelier (date_heure, est_heure_creuse, capacite_max) VALUES (:date_heure, :est_heure_creuse, :capacite_max)');
        $insert->execute([
            ':date_heure' => $dateTime,
            ':est_heure_creuse' => $isOffPeak,
            ':capacite_max' => 3,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function getWeekGridCounts(string $weekStart, string $weekEnd): array
    {
        $sql = "SELECT
                    c.id_creneau,
                    c.date_heure,
                    c.capacite_max,
                    COUNT(r.id_rdv) AS nb_actifs
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r
                    ON r.id_creneau = c.id_creneau
                    AND r.statut IN ('En attente', 'Confirmé', 'En cours')
                WHERE c.date_heure BETWEEN :week_start AND :week_end
                GROUP BY c.id_creneau
                ORDER BY c.date_heure ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':week_start' => $weekStart,
            ':week_end' => $weekEnd,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateCapacity(int $idCreneau, int $capacity): bool
    {
        $stmt = $this->db->prepare('UPDATE creneau_atelier SET capacite_max = :capacite_max WHERE id_creneau = :id_creneau');
        return $stmt->execute([
            ':capacite_max' => $capacity,
            ':id_creneau' => $idCreneau,
        ]);
    }

    private function countActiveByCreneau(int $idCreneau): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rendezvous_digital WHERE id_creneau = :id_creneau AND statut IN ('En attente', 'Confirmé', 'En cours')");
        $stmt->execute([':id_creneau' => $idCreneau]);
        return (int) $stmt->fetchColumn();
    }

    private function createRendezvous(array $data): int
    {
        $sql = "INSERT INTO rendezvous_digital (
                    id_creneau,
                    nom_client,
                    prenom_client,
                    telephone_client,
                    email_client,
                    id_vehicle,
                    type_intervention,
                    description_panne,
                    remise_eco_appliquee,
                    statut,
                    notes,
                    date_creation,
                    date_modification
                ) VALUES (
                    :id_creneau,
                    :nom_client,
                    :prenom_client,
                    :telephone_client,
                    :email_client,
                    :id_vehicle,
                    :type_intervention,
                    :description_panne,
                    :remise_eco_appliquee,
                    :statut,
                    :notes,
                    NOW(),
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_creneau' => (int) $data['id_creneau'],
            ':nom_client' => $data['nom_client'],
            ':prenom_client' => $data['prenom_client'],
            ':telephone_client' => $data['telephone_client'],
            ':email_client' => $data['email_client'] !== '' ? $data['email_client'] : null,
            ':id_vehicle' => isset($data['id_vehicle']) ? (int) $data['id_vehicle'] : null,
            ':type_intervention' => $data['type_intervention'],
            ':description_panne' => $data['description_panne'] !== '' ? $data['description_panne'] : null,
            ':remise_eco_appliquee' => $data['remise_eco_appliquee'],
            ':statut' => $data['statut'] ?? 'En attente',
            ':notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function findDetailedById(int $idRdv): ?array
    {
        $sql = "SELECT
                    r.*, c.date_heure, c.est_heure_creuse, c.capacite_max,
                    v.immatriculation, v.marque, v.modele, v.annee, v.kilometrage, v.carburant
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE r.id_rdv = :id_rdv
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_rdv' => $idRdv]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function getByCreneau(int $idCreneau): array
    {
        $sql = "SELECT
                    r.id_rdv,
                    r.nom_client,
                    r.prenom_client,
                    r.telephone_client,
                    r.email_client,
                    r.type_intervention,
                    r.description_panne,
                    r.statut,
                    r.remise_eco_appliquee,
                    r.notes,
                    r.date_creation,
                    v.immatriculation,
                    v.marque,
                    v.modele
                FROM rendezvous_digital r
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE r.id_creneau = :id_creneau
                ORDER BY r.date_creation ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_creneau' => $idCreneau]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function updateStatus(int $idRdv, string $status): bool
    {
        $sql = 'UPDATE rendezvous_digital SET statut = :statut, date_modification = NOW() WHERE id_rdv = :id_rdv';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':statut' => $status,
            ':id_rdv' => $idRdv,
        ]);
    }

    private function getWeekDetailed(string $weekStart, string $weekEnd): array
    {
        $sql = "SELECT
                  c.id_creneau,
                  c.date_heure,
                  c.est_heure_creuse,
                  c.capacite_max,
                  r.id_rdv,
                  r.nom_client,
                  r.prenom_client,
                  r.telephone_client,
                  r.email_client,
                  r.type_intervention,
                  r.description_panne,
                  r.statut,
                  r.remise_eco_appliquee,
                  r.notes,
                  v.marque,
                  v.modele,
                  v.immatriculation,
                  v.annee,
                  v.kilometrage
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r ON r.id_creneau = c.id_creneau
                  AND r.statut != 'Annulé'
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE c.date_heure BETWEEN :debut_semaine AND :fin_semaine
                ORDER BY c.date_heure ASC, r.date_creation ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':debut_semaine' => $weekStart,
            ':fin_semaine' => $weekEnd,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getFiltered(array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT(r.nom_client, " ", r.prenom_client) LIKE :search OR r.telephone_client LIKE :search OR v.immatriculation LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT
                    r.id_rdv,
                    c.date_heure,
                    r.nom_client,
                    r.prenom_client,
                    r.telephone_client,
                    r.type_intervention,
                    r.statut,
                    v.immatriculation,
                    v.marque,
                    v.modele
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.date_heure DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function countFiltered(array $filters): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT(r.nom_client, " ", r.prenom_client) LIKE :search OR r.telephone_client LIKE :search OR v.immatriculation LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = 'SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau LEFT JOIN vehicle v ON v.id = r.id_vehicle';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function getFilteredForExport(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT(r.nom_client, " ", r.prenom_client) LIKE :search OR r.telephone_client LIKE :search OR v.immatriculation LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT
                    c.date_heure,
                    r.nom_client,
                    r.prenom_client,
                    r.telephone_client,
                    r.email_client,
                    v.immatriculation,
                    v.marque,
                    v.modele,
                    r.type_intervention,
                    r.statut,
                    r.remise_eco_appliquee
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.date_heure DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
