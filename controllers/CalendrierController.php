<?php

require_once __DIR__ . '/../models/CreneauModel.php';
require_once __DIR__ . '/../models/RendezvousModel.php';
require_once __DIR__ . '/../models/VehicleModel.php';

class CalendrierController
{
    private CreneauModel $creneauModel;
    private RendezvousModel $rendezvousModel;
    private VehicleModel $vehicleModel;

    private array $allowedStatuses = ['En attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'];
    private array $allowedInterventions = ['Vidange', 'Révision', 'Freinage', 'Climatisation', 'Carrosserie', 'Autre'];

    public function __construct()
    {
        $this->creneauModel = new CreneauModel();
        $this->rendezvousModel = new RendezvousModel();
        $this->vehicleModel = new VehicleModel();
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

        $this->creneauModel->ensureMonthSlots($month, $year);

        if (!$this->isValidDate($selectedDate)) {
            $selectedDate = date('Y-m-d');
        }

        $monthAvailability = $this->creneauModel->getMonthAvailability($month, $year);
        $daySlots = $this->creneauModel->getDaySlots($selectedDate);

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

        $slot = $this->creneauModel->findById((int) $payload['id_creneau']);
        if (!$slot) {
            $_SESSION['rdv_errors'] = ['Créneau introuvable.'];
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        if (strtotime($slot['date_heure']) < time()) {
            $_SESSION['rdv_errors'] = ['Ce créneau est déjà passé.'];
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        $activeCount = $this->rendezvousModel->countActiveByCreneau((int) $slot['id_creneau']);
        if ($activeCount >= (int) $slot['capacite_max']) {
            $_SESSION['rdv_errors'] = ['Ce créneau est complet.'];
            header('Location: index.php?action=frontCalendar');
            exit;
        }

        $vehicleId = $this->vehicleModel->findOrCreate($payload['vehicle']);

        $rdvId = $this->rendezvousModel->create([
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
        $rdv = $this->rendezvousModel->findDetailedById($id);

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

        $this->creneauModel->ensureMonthSlots($month, $year);
        $data = $this->creneauModel->getMonthAvailability($month, $year);

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function apiDaySlots(): void
    {
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        if (!$this->isValidDate($date)) {
            $this->jsonResponse(['success' => false, 'message' => 'Date invalide'], 422);
            return;
        }

        $this->creneauModel->ensureDaySlots($date);
        $slots = $this->creneauModel->getDaySlots($date);

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

        $this->creneauModel->ensureWeekSlots($weekStart, $weekEnd);

        $gridRows = $this->creneauModel->getWeekGridCounts(
            $weekStart->format('Y-m-d H:i:s'),
            $weekEnd->format('Y-m-d H:i:s')
        );

        $today = new DateTimeImmutable();
        $stats = $this->rendezvousModel->getQuickStats(
            $today->setTime(0, 0, 0)->format('Y-m-d H:i:s'),
            $today->setTime(23, 59, 59)->format('Y-m-d H:i:s'),
            $weekStart->format('Y-m-d H:i:s'),
            $weekEnd->format('Y-m-d H:i:s')
        );

        $weekDays = [];
        for ($i = 0; $i < 6; $i++) {
            $weekDays[] = $weekStart->modify('+' . $i . ' day');
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
        $slot = $this->creneauModel->findById($idCreneau);
        if (!$slot) {
            $this->jsonResponse(['success' => false, 'message' => 'Créneau introuvable'], 404);
            return;
        }

        $rdvs = $this->rendezvousModel->getByCreneau($idCreneau);

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

        $ok = $this->rendezvousModel->updateStatus($idRdv, $status);
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
            $this->creneauModel->updateCapacity($idCreneau, $capacity);
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
            $idCreneau = $this->creneauModel->findOrCreateByDateTime($payload['date_heure_manual']);
        }

        $slot = $this->creneauModel->findById($idCreneau);
        if (!$slot) {
            $_SESSION['manual_rdv_errors'] = ['Impossible de créer ce créneau.'];
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $activeCount = $this->rendezvousModel->countActiveByCreneau((int) $slot['id_creneau']);
        if ((int) $slot['capacite_max'] === 0 || $activeCount >= (int) $slot['capacite_max']) {
            $_SESSION['manual_rdv_errors'] = ['Créneau bloqué ou complet.'];
            header('Location: index.php?action=backCalendar');
            exit;
        }

        $vehicleId = $this->vehicleModel->findOrCreate($payload['vehicle']);

        $this->rendezvousModel->create([
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

        $total = $this->rendezvousModel->countFiltered($filters);
        $rdvs = $this->rendezvousModel->getFiltered($filters, $perPage, $offset);
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

        $rows = $this->rendezvousModel->getFilteredForExport($filters);

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
        if ($emailClient !== '' && !filter_var($emailClient, FILTER_VALIDATE_EMAIL)) {
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
            $existingVehicle = $this->vehicleModel->findByImmatriculation($immatriculation);
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
}
