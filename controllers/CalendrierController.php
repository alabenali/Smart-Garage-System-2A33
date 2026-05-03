<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/UrgenceService.php';
require_once __DIR__ . '/../services/TelegramService.php';
require_once __DIR__ . '/../services/UrgenceBroadcaster.php';
require_once __DIR__ . '/../observers/RendezVousObserver.php';
require_once __DIR__ . '/../events/RendezVousUrgenceUpdated.php';
require_once __DIR__ . '/../listeners/RendezVousUrgenceListener.php';

class CalendrierController
{
    private PDO $db;
    private ?bool $hasPanneColumnsCache = null;
    private ?bool $hasUrgenceColumnsCache = null;

    private array $allowedStatuses = ['En attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'];
    private array $allowedInterventions = [
        'Vidange',
        'Révision',
        'Changement de pneu',
        'Pneumatiques',
        'Batterie',
        'Freinage',
        'Climatisation',
        'Carrosserie',
        'Diagnostic général',
        'Autre',
        'Moteur',
        'Boîte de vitesse',
        'Électrique-Batterie',
        'Suspension-Direction',
    ];
    private array $allowedCircumstances = ['En roulant', 'À l\'arrêt', 'Au démarrage', 'Panne intermittente'];

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

        $rdvId = $this->createRendezvous([
            'id_creneau' => (int) $slot['id_creneau'],
            'nom_client' => '',
            'prenom_client' => '',
            'telephone_client' => '',
            'email_client' => null,
            'id_vehicle' => null,
            'type_intervention' => $payload['type_intervention'],
            'description_panne' => $payload['description_panne'],
            'circonstances_panne' => $payload['circonstances_panne'],
            'temoins_panne' => $payload['temoins_panne'],
            'panne_data_json' => $payload['panne_data_json'],
            'photos_json' => '[]',
            'remise_eco_appliquee' => ((int) $slot['est_heure_creuse'] === 1) ? 15.00 : 0.00,
            'statut' => 'En attente',
            'notes' => null,
        ]);

        $savedPhotos = $this->savePannePhotos($rdvId, $_FILES['panne_photos'] ?? null);
        if (!empty($savedPhotos)) {
            $this->updateRendezvousPhotosJson($rdvId, $savedPhotos);
        }

        // Notification Telegram (non bloquante)
        $this->sendTelegramNotification($rdvId);

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

    public function apiRendezVous(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $scope = isset($_GET['scope']) ? trim((string) $_GET['scope']) : '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($method === 'GET') {
            if ($scope === 'urgents') {
                $this->apiRendezVousUrgents();
                return;
            }

            $this->apiRendezVousList();
            return;
        }

        if ($method === 'POST') {
            $this->apiRendezVousCreate();
            return;
        }

        if ($method === 'PUT') {
            if ($id <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID manquant'], 422);
                return;
            }

            $this->apiRendezVousUpdate($id);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    private function apiRendezVousList(): void
    {
        $filters = [
            'status' => isset($_GET['status']) ? trim((string) $_GET['status']) : '',
            'date' => isset($_GET['date']) ? trim((string) $_GET['date']) : '',
            'search' => isset($_GET['search']) ? trim((string) $_GET['search']) : '',
        ];

        $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

        $rows = $this->getFiltered($filters, $limit, $offset);
        $data = array_map(fn($row) => $this->formatRdvApiRow($row), $rows);

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    private function apiRendezVousUrgents(): void
    {
        if (!$this->hasUrgenceColumns()) {
            $this->jsonResponse(['success' => true, 'data' => []]);
            return;
        }

        $urgenceService = new UrgenceService();
        $minScore = isset($_GET['min']) ? (int) $_GET['min'] : $urgenceService->getUrgentMinScore();
        $minScore = max(0, $minScore);
        $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;
        $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;

        $hasPanneColumns = $this->hasPanneColumns();
        $urgenceSelect = 'r.urgence_score, r.urgence_details,';

        if ($hasPanneColumns) {
            $sql = "SELECT
                        r.id_rdv,
                        c.date_heure,
                        r.type_intervention,
                        r.description_panne,
                        r.circonstances_panne,
                        r.temoins_panne,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee
                    FROM rendezvous_digital r
                    INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                    WHERE r.urgence_score >= :min_score
                    ORDER BY r.urgence_score DESC, c.date_heure DESC
                    LIMIT :limit OFFSET :offset";
        } else {
            $sql = "SELECT
                        r.id_rdv,
                        c.date_heure,
                        r.type_intervention,
                        r.description_panne,
                        '' AS circonstances_panne,
                        '[]' AS temoins_panne,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee
                    FROM rendezvous_digital r
                    INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                    WHERE r.urgence_score >= :min_score
                    ORDER BY r.urgence_score DESC, c.date_heure DESC
                    LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_score', $minScore, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(fn($row) => $this->formatRdvApiRow($row), $rows);
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    private function apiRendezVousCreate(): void
    {
        $payload = $this->decodeJsonBody();

        $idCreneau = isset($payload['id_creneau']) ? (int) $payload['id_creneau'] : 0;
        $typeIntervention = $this->sanitize((string) ($payload['type_intervention'] ?? ''));
        $statut = trim((string) ($payload['statut'] ?? 'En attente'));

        if ($idCreneau <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'id_creneau requis'], 422);
            return;
        }

        if ($typeIntervention === '') {
            $this->jsonResponse(['success' => false, 'message' => 'type_intervention requis'], 422);
            return;
        }

        if (!in_array($statut, $this->allowedStatuses, true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Statut invalide'], 422);
            return;
        }

        $slot = $this->findCreneauById($idCreneau);
        if (!$slot) {
            $this->jsonResponse(['success' => false, 'message' => 'Creneau introuvable'], 404);
            return;
        }

        $slotDate = date('Y-m-d', strtotime($slot['date_heure']));
        $slotYear = (int) date('Y', strtotime($slot['date_heure']));
        $slotHolidays = $this->getTunisianHolidays($slotYear);
        if (isset($slotHolidays[$slotDate])) {
            $this->jsonResponse(['success' => false, 'message' => 'Jour ferie, choisir un autre creneau'], 422);
            return;
        }

        if (strtotime($slot['date_heure']) < time()) {
            $this->jsonResponse(['success' => false, 'message' => 'Creneau deja passe'], 422);
            return;
        }

        $activeCount = $this->countActiveByCreneau($idCreneau);
        if ($activeCount >= (int) $slot['capacite_max']) {
            $this->jsonResponse(['success' => false, 'message' => 'Creneau complet'], 422);
            return;
        }

        $temoins = $this->normalizeTemoinsInput($payload['temoins_panne'] ?? []);
        $panneJson = $this->buildPanneJson($payload, $typeIntervention, $temoins);

        $data = [
            'id_creneau' => $idCreneau,
            'nom_client' => $this->sanitize((string) ($payload['nom_client'] ?? '')),
            'prenom_client' => $this->sanitize((string) ($payload['prenom_client'] ?? '')),
            'telephone_client' => preg_replace('/\D+/', '', (string) ($payload['telephone_client'] ?? '')),
            'email_client' => trim((string) ($payload['email_client'] ?? '')),
            'id_vehicle' => isset($payload['id_vehicle']) ? (int) $payload['id_vehicle'] : null,
            'type_intervention' => $typeIntervention,
            'description_panne' => $this->sanitize((string) ($payload['description_panne'] ?? '')),
            'circonstances_panne' => $this->sanitize((string) ($payload['circonstances_panne'] ?? '')),
            'temoins_panne' => $temoins,
            'panne_data_json' => $panneJson,
            'photos_json' => json_encode([], JSON_UNESCAPED_UNICODE),
            'remise_eco_appliquee' => ((int) $slot['est_heure_creuse'] === 1) ? 15.00 : 0.00,
            'statut' => $statut,
            'notes' => isset($payload['notes']) ? $this->sanitize((string) $payload['notes']) : null,
        ];

        $rdvId = $this->createRendezvous($data);
        $created = $this->findDetailedById($rdvId);
        if (!$created) {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur creation RDV'], 500);
            return;
        }

        // Notification Telegram (non bloquante)
        $this->sendTelegramNotification($rdvId);

        $this->jsonResponse(['success' => true, 'data' => $this->formatRdvApiRow($created)]);
    }

    private function apiRendezVousUpdate(int $idRdv): void
    {
        $payload = $this->decodeJsonBody();
        $existing = $this->findDetailedById($idRdv);

        if (!$existing) {
            $this->jsonResponse(['success' => false, 'message' => 'RDV introuvable'], 404);
            return;
        }

        $idCreneau = isset($payload['id_creneau']) ? (int) $payload['id_creneau'] : (int) $existing['id_creneau'];
        if ($idCreneau <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'id_creneau requis'], 422);
            return;
        }

        $slot = $this->findCreneauById($idCreneau);
        if (!$slot) {
            $this->jsonResponse(['success' => false, 'message' => 'Creneau introuvable'], 404);
            return;
        }

        $statut = trim((string) ($payload['statut'] ?? ($existing['statut'] ?? 'En attente')));
        if (!in_array($statut, $this->allowedStatuses, true)) {
            $this->jsonResponse(['success' => false, 'message' => 'Statut invalide'], 422);
            return;
        }

        $temoins = $payload['temoins_panne'] ?? ($existing['temoins_panne'] ?? []);
        $temoins = $this->normalizeTemoinsInput($temoins);
        $typeIntervention = $this->sanitize((string) ($payload['type_intervention'] ?? ($existing['type_intervention'] ?? '')));
        if ($typeIntervention === '') {
            $this->jsonResponse(['success' => false, 'message' => 'type_intervention requis'], 422);
            return;
        }

        $description = $this->sanitize((string) ($payload['description_panne'] ?? ($existing['description_panne'] ?? '')));
        $circ = $this->sanitize((string) ($payload['circonstances_panne'] ?? ($existing['circonstances_panne'] ?? '')));

        $panneJson = $this->buildPanneJson($payload, $typeIntervention, $temoins, (string) ($existing['panne_data_json'] ?? ''));
        $photosJson = $payload['photos_json'] ?? ($existing['photos_json'] ?? '[]');
        if (is_array($photosJson)) {
            $photosJson = json_encode($photosJson, JSON_UNESCAPED_UNICODE);
        }

        $data = [
            'id_creneau' => $idCreneau,
            'nom_client' => $this->sanitize((string) ($payload['nom_client'] ?? ($existing['nom_client'] ?? ''))),
            'prenom_client' => $this->sanitize((string) ($payload['prenom_client'] ?? ($existing['prenom_client'] ?? ''))),
            'telephone_client' => preg_replace('/\D+/', '', (string) ($payload['telephone_client'] ?? ($existing['telephone_client'] ?? ''))),
            'email_client' => trim((string) ($payload['email_client'] ?? ($existing['email_client'] ?? ''))),
            'id_vehicle' => isset($payload['id_vehicle']) ? (int) $payload['id_vehicle'] : ($existing['id_vehicle'] ?? null),
            'type_intervention' => $typeIntervention,
            'description_panne' => $description,
            'circonstances_panne' => $circ,
            'temoins_panne' => $temoins,
            'panne_data_json' => $panneJson,
            'photos_json' => $photosJson,
            'remise_eco_appliquee' => ((int) $slot['est_heure_creuse'] === 1) ? 15.00 : 0.00,
            'statut' => $statut,
            'notes' => isset($payload['notes']) ? $this->sanitize((string) $payload['notes']) : ($existing['notes'] ?? null),
        ];

        $ok = $this->updateRendezvous($idRdv, $data);
        if (!$ok) {
            $this->jsonResponse(['success' => false, 'message' => 'Mise a jour impossible'], 500);
            return;
        }

        $updated = $this->findDetailedById($idRdv);
        $this->jsonResponse(['success' => true, 'data' => $this->formatRdvApiRow($updated ?: $data)]);
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

        $rdvId = $this->createRendezvous([
            'id_creneau' => (int) $slot['id_creneau'],
            'nom_client' => $payload['nom_client'],
            'prenom_client' => $payload['prenom_client'],
            'telephone_client' => $payload['telephone_client'],
            'email_client' => $payload['email_client'],
            'id_vehicle' => $vehicleId,
            'type_intervention' => $payload['type_intervention'],
            'description_panne' => $payload['description_panne'],
            'circonstances_panne' => $payload['circonstances_panne'] ?? '',
            'temoins_panne' => $payload['temoins_panne'] ?? [],
            'panne_data_json' => $payload['panne_data_json'] ?? json_encode([], JSON_UNESCAPED_UNICODE),
            'photos_json' => json_encode([], JSON_UNESCAPED_UNICODE),
            'remise_eco_appliquee' => ((int) $slot['est_heure_creuse'] === 1) ? 15.00 : 0.00,
            'statut' => 'Confirmé',
            'notes' => null,
        ]);

        // Notification Telegram (non bloquante)
        $this->sendTelegramNotification($rdvId);

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
        fputcsv($output, ['Date/Heure', 'Type de panne', 'Circonstances', 'Symptômes', 'Témoins', 'Statut', 'Remise %'], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['date_heure'],
                $row['type_intervention'],
                $row['circonstances_panne'] ?? '',
                $row['description_panne'] ?? '',
                $this->temoinsToLabel($row['temoins_panne'] ?? null),
                $row['statut'],
                $row['remise_eco_appliquee'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    public function backExportPdf(): void
    {
        $filters = [
            'status' => isset($_GET['status']) ? trim($_GET['status']) : '',
            'date' => isset($_GET['date']) ? trim($_GET['date']) : '',
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        ];

        $rows = $this->getFilteredForExport($filters);

        $lines = [];
        $lines[] = 'SMART GARAGE - Export RDV';
        $lines[] = 'Genere le: ' . date('d/m/Y H:i');
        $lines[] = 'Filtres - Statut: ' . ($filters['status'] !== '' ? $filters['status'] : 'Tous')
            . ' | Date: ' . ($filters['date'] !== '' ? $filters['date'] : 'Toutes')
            . ' | Recherche: ' . ($filters['search'] !== '' ? $filters['search'] : 'Aucune');
        $lines[] = str_repeat('-', 110);
        $lines[] = 'DATE/HEURE | TYPE PANNE | CIRCONSTANCES | TEMOINS | STATUT';
        $lines[] = str_repeat('-', 110);

        foreach ($rows as $row) {
            $dateHeure = date('d/m/Y H:i', strtotime((string) $row['date_heure']));
            $temoins = $this->temoinsToLabel($row['temoins_panne'] ?? null);

            $line = sprintf(
                '%s | %s | %s | %s | %s',
                $dateHeure,
                (string) ($row['type_intervention'] ?? ''),
                (string) ($row['circonstances_panne'] ?? '-'),
                $temoins,
                (string) ($row['statut'] ?? '')
            );

            $lines[] = $this->truncatePdfLine($line, 155);
        }

        if (empty($rows)) {
            $lines[] = 'Aucun rendez-vous pour ces filtres.';
        }

        $pdfBinary = $this->buildSimplePdf($lines);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=rdv_export_' . date('Ymd_His') . '.pdf');
        header('Content-Length: ' . strlen($pdfBinary));
        echo $pdfBinary;
        exit;
    }

    private function validateRdvRequest(array $input, bool $manualMode): array
    {
        $errors = [];

        $typeIntervention = $this->sanitize($input['type_intervention'] ?? '');
        $descriptionPanne = $this->sanitize($input['description_panne'] ?? '');
        $circonstancesPanne = $this->sanitize($input['circonstances_panne'] ?? '');
        $temoinsPanne = $input['temoins_panne'] ?? [];
        if (!is_array($temoinsPanne)) {
            $temoinsPanne = [];
        }
        $temoinsPanne = array_values(array_filter(array_map(fn($item) => $this->sanitize((string) $item), $temoinsPanne), static fn($item) => $item !== ''));
        $panneDataJsonRaw = trim((string) ($input['panne_data_json'] ?? ''));

        if (!in_array($typeIntervention, $this->allowedInterventions, true)) {
            $errors[] = 'Type d\'intervention invalide.';
        }
        if ($descriptionPanne === '') {
            $errors[] = 'La description des symptômes est obligatoire.';
        }
        if ($circonstancesPanne !== '' && !in_array($circonstancesPanne, $this->allowedCircumstances, true)) {
            $errors[] = 'Circonstances invalides.';
        }

        $idCreneau = isset($input['id_creneau']) ? (int) $input['id_creneau'] : 0;
        $dateHeureManual = trim($input['date_heure_manual'] ?? '');

        $nomClient = '';
        $prenomClient = '';
        $telephoneClient = '';
        $emailClient = '';
        $vehicle = null;

        if ($manualMode) {
            $nomClient = $this->sanitize($input['nom_client'] ?? '');
            $prenomClient = $this->sanitize($input['prenom_client'] ?? '');
            $telephoneClient = preg_replace('/\D+/', '', $input['telephone_client'] ?? '');
            $emailClient = trim($input['email_client'] ?? '');
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
            if ($idCreneau <= 0 && $dateHeureManual === '') {
                $errors[] = 'Sélectionnez un créneau ou renseignez une date/heure manuelle.';
            }
            if ($dateHeureManual !== '' && !$this->isValidDateTime($dateHeureManual)) {
                $errors[] = 'Date/heure manuelle invalide.';
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
        } else {
            if ($idCreneau <= 0) {
                $errors[] = 'Veuillez sélectionner un créneau.';
            }
        }

        $decodedPanneData = [];
        if ($panneDataJsonRaw !== '') {
            $decodedPanneData = json_decode($panneDataJsonRaw, true);
            if (!is_array($decodedPanneData)) {
                $decodedPanneData = [];
            }
        }

        $panneData = [
            'typePanne' => $typeIntervention,
            'circonstances' => $circonstancesPanne,
            'symptomes' => $descriptionPanne,
            'temoins' => $temoinsPanne,
            'photos' => is_array($decodedPanneData['photos'] ?? null) ? $decodedPanneData['photos'] : [],
        ];

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
                'circonstances_panne' => $circonstancesPanne,
                'temoins_panne' => $temoinsPanne,
                'panne_data_json' => json_encode($panneData, JSON_UNESCAPED_UNICODE),
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

    private function decodeJsonBody(): array
    {
        $rawBody = file_get_contents('php://input') ?: '';
        if (trim($rawBody) === '') {
            return is_array($_POST) ? $_POST : [];
        }

        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return [];
    }

    private function normalizeTemoinsInput($raw): array
    {
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        $items = array_values(array_filter(array_map(fn($item) => $this->sanitize((string) $item), $raw), static fn($item) => $item !== ''));
        return $items;
    }

    private function buildPanneJson(array $payload, string $typeIntervention, array $temoins, string $fallback = ''): string
    {
        if (isset($payload['panne_data_json'])) {
            if (is_array($payload['panne_data_json'])) {
                return json_encode($payload['panne_data_json'], JSON_UNESCAPED_UNICODE);
            }

            $raw = trim((string) $payload['panne_data_json']);
            if ($raw !== '') {
                return $raw;
            }
        }

        if ($fallback !== '') {
            return $fallback;
        }

        $data = [
            'typePanne' => $typeIntervention,
            'circonstances' => $this->sanitize((string) ($payload['circonstances_panne'] ?? '')),
            'symptomes' => $this->sanitize((string) ($payload['description_panne'] ?? '')),
            'temoins' => $temoins,
            'photos' => [],
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function formatRdvApiRow(array $row): array
    {
        $temoins = json_decode((string) ($row['temoins_panne'] ?? ''), true);
        $temoins = is_array($temoins) ? $temoins : [];

        $urgenceDetails = json_decode((string) ($row['urgence_details'] ?? ''), true);
        $urgenceDetails = is_array($urgenceDetails) ? $urgenceDetails : [];

        return [
            'id' => (int) ($row['id_rdv'] ?? 0),
            'date_heure' => $row['date_heure'] ?? null,
            'type_intervention' => $row['type_intervention'] ?? '',
            'description_panne' => $row['description_panne'] ?? '',
            'circonstances_panne' => $row['circonstances_panne'] ?? '',
            'temoins_panne' => $temoins,
            'statut' => $row['statut'] ?? '',
            'urgence_score' => (int) ($row['urgence_score'] ?? 0),
            'urgence_details' => $urgenceDetails,
            'remise_eco_appliquee' => isset($row['remise_eco_appliquee']) ? (float) $row['remise_eco_appliquee'] : 0.0,
        ];
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
        $urgence = $this->computeUrgencePayload($data);
        $hasUrgenceColumns = $this->hasUrgenceColumns();

        $columns = [
            'id_creneau',
            'nom_client',
            'prenom_client',
            'telephone_client',
            'email_client',
            'id_vehicle',
            'type_intervention',
            'description_panne',
            'circonstances_panne',
            'temoins_panne',
            'panne_data_json',
            'photos_json',
        ];

        if ($hasUrgenceColumns) {
            $columns[] = 'urgence_score';
            $columns[] = 'urgence_details';
        }

        $columns = array_merge($columns, [
            'remise_eco_appliquee',
            'statut',
            'notes',
            'date_creation',
            'date_modification',
        ]);

        $placeholders = array_map(static fn($col) => ':' . $col, $columns);

        $sql = "INSERT INTO rendezvous_digital (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";

        $params = [
            ':id_creneau' => (int) $data['id_creneau'],
            ':nom_client' => $data['nom_client'],
            ':prenom_client' => $data['prenom_client'],
            ':telephone_client' => $data['telephone_client'],
            ':email_client' => $data['email_client'] !== '' ? $data['email_client'] : null,
            ':id_vehicle' => isset($data['id_vehicle']) ? (int) $data['id_vehicle'] : null,
            ':type_intervention' => $data['type_intervention'],
            ':description_panne' => $data['description_panne'] !== '' ? $data['description_panne'] : null,
            ':circonstances_panne' => $data['circonstances_panne'] !== '' ? $data['circonstances_panne'] : null,
            ':temoins_panne' => isset($data['temoins_panne']) ? json_encode($data['temoins_panne'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE),
            ':panne_data_json' => $data['panne_data_json'] ?? json_encode([], JSON_UNESCAPED_UNICODE),
            ':photos_json' => $data['photos_json'] ?? json_encode([], JSON_UNESCAPED_UNICODE),
            ':remise_eco_appliquee' => $data['remise_eco_appliquee'],
            ':statut' => $data['statut'] ?? 'En attente',
            ':notes' => $data['notes'] ?? null,
            ':date_creation' => date('Y-m-d H:i:s'),
            ':date_modification' => date('Y-m-d H:i:s'),
        ];

        if ($hasUrgenceColumns) {
            $params[':urgence_score'] = (int) $urgence['score'];
            $params[':urgence_details'] = json_encode($urgence['details'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                return $this->createRendezvousLegacy($data);
            }
            throw $e;
        }

        $id = (int) $this->db->lastInsertId();
        if ($hasUrgenceColumns) {
            $this->maybeBroadcastUrgence($id, $urgence);
        }

        return $id;
    }

    private function createRendezvousLegacy(array $data): int
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

    private function updateRendezvous(int $idRdv, array $data): bool
    {
        $urgence = $this->computeUrgencePayload($data, $idRdv);
        $hasPanneColumns = $this->hasPanneColumns();
        $hasUrgenceColumns = $this->hasUrgenceColumns();

        $fields = [
            'id_creneau = :id_creneau',
            'nom_client = :nom_client',
            'prenom_client = :prenom_client',
            'telephone_client = :telephone_client',
            'email_client = :email_client',
            'id_vehicle = :id_vehicle',
            'type_intervention = :type_intervention',
            'description_panne = :description_panne',
        ];

        if ($hasPanneColumns) {
            $fields[] = 'circonstances_panne = :circonstances_panne';
            $fields[] = 'temoins_panne = :temoins_panne';
            $fields[] = 'panne_data_json = :panne_data_json';
            $fields[] = 'photos_json = :photos_json';
        }

        if ($hasUrgenceColumns) {
            $fields[] = 'urgence_score = :urgence_score';
            $fields[] = 'urgence_details = :urgence_details';
        }

        $fields[] = 'remise_eco_appliquee = :remise_eco_appliquee';
        $fields[] = 'statut = :statut';
        $fields[] = 'notes = :notes';
        $fields[] = 'date_modification = NOW()';

        $sql = 'UPDATE rendezvous_digital SET ' . implode(', ', $fields) . ' WHERE id_rdv = :id_rdv';

        $params = [
            ':id_rdv' => $idRdv,
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
        ];

        if ($hasPanneColumns) {
            $params[':circonstances_panne'] = $data['circonstances_panne'] !== '' ? $data['circonstances_panne'] : null;
            $params[':temoins_panne'] = isset($data['temoins_panne']) ? json_encode($data['temoins_panne'], JSON_UNESCAPED_UNICODE) : json_encode([], JSON_UNESCAPED_UNICODE);
            $params[':panne_data_json'] = $data['panne_data_json'] ?? json_encode([], JSON_UNESCAPED_UNICODE);
            $params[':photos_json'] = $data['photos_json'] ?? json_encode([], JSON_UNESCAPED_UNICODE);
        }

        if ($hasUrgenceColumns) {
            $params[':urgence_score'] = (int) $urgence['score'];
            $params[':urgence_details'] = json_encode($urgence['details'], JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok && $hasUrgenceColumns) {
            $this->maybeBroadcastUrgence($idRdv, $urgence);
        }

        return $ok;
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

    /**
     * Envoie une notification Telegram après création d'un RDV
     * Non bloquant : ne casse jamais le flux principal
     *
     * @param int $rdvId
     * @return void
     */
    private function sendTelegramNotification(int $rdvId): void
    {
        try {
            $rdvData = $this->findDetailedById($rdvId);
            if (!$rdvData) {
                return;
            }

            $telegram = new TelegramService();
            $telegram->notifyNewRdv($rdvData);
        } catch (\Throwable $e) {
            // Ne jamais bloquer le système RDV
        }
    }

    private function savePannePhotos(int $idRdv, ?array $files): array
    {
        if ($idRdv <= 0 || !is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $maxPhotos = 5;
        $maxSize = 10 * 1024 * 1024;
        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $uploadDir = __DIR__ . '/../views/images/pannes';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return [];
        }

        $saved = [];
        $count = min($maxPhotos, count($files['name']));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $count; $i++) {
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = (string) ($files['tmp_name'][$i] ?? '');
            $originalName = $this->sanitize((string) ($files['name'][$i] ?? 'photo'));
            $size = (int) ($files['size'][$i] ?? 0);
            if ($tmpName === '' || $size <= 0 || $size > $maxSize || !is_uploaded_file($tmpName)) {
                continue;
            }

            $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : (string) ($files['type'][$i] ?? '');
            if (!isset($allowedMime[$mime])) {
                continue;
            }

            $ext = $allowedMime[$mime];
            try {
                $entropy = bin2hex(random_bytes(4));
            } catch (Throwable $e) {
                $entropy = (string) mt_rand(1000, 9999);
            }

            $safeName = 'rdv_' . $idRdv . '_' . date('YmdHis') . '_' . $entropy . '.' . $ext;
            $targetPath = $uploadDir . '/' . $safeName;

            if (!move_uploaded_file($tmpName, $targetPath)) {
                continue;
            }

            $saved[] = [
                'path' => 'views/images/pannes/' . $safeName,
                'name' => $originalName,
                'size' => $size,
                'type' => $mime,
            ];
        }

        if ($finfo) {
            finfo_close($finfo);
        }

        return $saved;
    }

    private function updateRendezvousPhotosJson(int $idRdv, array $photos): void
    {
        if ($idRdv <= 0) {
            return;
        }

        try {
            $stmt = $this->db->prepare('UPDATE rendezvous_digital SET photos_json = :photos_json, date_modification = NOW() WHERE id_rdv = :id_rdv');
            $stmt->execute([
                ':photos_json' => json_encode($photos, JSON_UNESCAPED_UNICODE),
                ':id_rdv' => $idRdv,
            ]);
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                return;
            }
            throw $e;
        }
    }

    private function getByCreneau(int $idCreneau): array
    {
        $urgenceSelect = $this->hasUrgenceColumns()
            ? 'r.urgence_score, r.urgence_details,'
            : "0 AS urgence_score, '{}' AS urgence_details,";

        if ($this->hasPanneColumns()) {
            $sql = "SELECT
                        r.id_rdv,
                        r.type_intervention,
                        r.description_panne,
                        r.circonstances_panne,
                        r.temoins_panne,
                        r.photos_json,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee,
                        r.notes,
                        r.date_creation
                    FROM rendezvous_digital r
                    WHERE r.id_creneau = :id_creneau
                    ORDER BY r.date_creation ASC";
        } else {
            $sql = "SELECT
                        r.id_rdv,
                        r.type_intervention,
                        r.description_panne,
                        '' AS circonstances_panne,
                        '[]' AS temoins_panne,
                        '[]' AS photos_json,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee,
                        r.notes,
                        r.date_creation
                    FROM rendezvous_digital r
                    WHERE r.id_creneau = :id_creneau
                    ORDER BY r.date_creation ASC";
        }

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
        $hasPanneColumns = $this->hasPanneColumns();
        $hasUrgenceColumns = $this->hasUrgenceColumns();
        $urgenceSelect = $hasUrgenceColumns
            ? 'r.urgence_score, r.urgence_details,'
            : "0 AS urgence_score, '{}' AS urgence_details,";

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            if ($hasPanneColumns) {
                $where[] = '(r.type_intervention LIKE :search_type OR r.description_panne LIKE :search_desc OR r.circonstances_panne LIKE :search_context OR r.statut LIKE :search_status)';
            } else {
                $where[] = '(r.type_intervention LIKE :search_type OR r.description_panne LIKE :search_desc OR r.statut LIKE :search_status)';
            }
            $searchValue = '%' . $filters['search'] . '%';
            $params[':search_type'] = $searchValue;
            $params[':search_desc'] = $searchValue;
            $params[':search_status'] = $searchValue;
            if ($hasPanneColumns) {
                $params[':search_context'] = $searchValue;
            }
        }

        if ($hasPanneColumns) {
            $sql = "SELECT
                        r.id_rdv,
                        c.date_heure,
                        r.type_intervention,
                        r.description_panne,
                        r.circonstances_panne,
                        r.temoins_panne,
                        r.photos_json,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee
                    FROM rendezvous_digital r
                    INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau";
        } else {
            $sql = "SELECT
                        r.id_rdv,
                        c.date_heure,
                        r.type_intervention,
                        r.description_panne,
                        '' AS circonstances_panne,
                        '[]' AS temoins_panne,
                        '[]' AS photos_json,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee
                    FROM rendezvous_digital r
                    INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau";
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $orderBy = $hasUrgenceColumns
            ? ' ORDER BY r.urgence_score DESC, c.date_heure DESC'
            : ' ORDER BY c.date_heure DESC';
        $sql .= $orderBy . ' LIMIT :limit OFFSET :offset';

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
        $hasPanneColumns = $this->hasPanneColumns();

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            if ($hasPanneColumns) {
                $where[] = '(r.type_intervention LIKE :search_type OR r.description_panne LIKE :search_desc OR r.circonstances_panne LIKE :search_context OR r.statut LIKE :search_status)';
            } else {
                $where[] = '(r.type_intervention LIKE :search_type OR r.description_panne LIKE :search_desc OR r.statut LIKE :search_status)';
            }
            $searchValue = '%' . $filters['search'] . '%';
            $params[':search_type'] = $searchValue;
            $params[':search_desc'] = $searchValue;
            $params[':search_status'] = $searchValue;
            if ($hasPanneColumns) {
                $params[':search_context'] = $searchValue;
            }
        }

        $sql = 'SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau';

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
        $hasPanneColumns = $this->hasPanneColumns();
        $hasUrgenceColumns = $this->hasUrgenceColumns();
        $urgenceSelect = $hasUrgenceColumns
            ? 'r.urgence_score, r.urgence_details,'
            : "0 AS urgence_score, '{}' AS urgence_details,";

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            if ($hasPanneColumns) {
                $where[] = '(r.type_intervention LIKE :search_type OR r.description_panne LIKE :search_desc OR r.circonstances_panne LIKE :search_context OR r.statut LIKE :search_status)';
            } else {
                $where[] = '(r.type_intervention LIKE :search_type OR r.description_panne LIKE :search_desc OR r.statut LIKE :search_status)';
            }
            $searchValue = '%' . $filters['search'] . '%';
            $params[':search_type'] = $searchValue;
            $params[':search_desc'] = $searchValue;
            $params[':search_status'] = $searchValue;
            if ($hasPanneColumns) {
                $params[':search_context'] = $searchValue;
            }
        }

        if ($hasPanneColumns) {
            $sql = "SELECT
                        c.date_heure,
                        r.type_intervention,
                        r.description_panne,
                        r.circonstances_panne,
                        r.temoins_panne,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee
                    FROM rendezvous_digital r
                    INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau";
        } else {
            $sql = "SELECT
                        c.date_heure,
                        r.type_intervention,
                        r.description_panne,
                        '' AS circonstances_panne,
                        '[]' AS temoins_panne,
                        {$urgenceSelect}
                        r.statut,
                        r.remise_eco_appliquee
                    FROM rendezvous_digital r
                    INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau";
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.date_heure DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasPanneColumns(): bool
    {
        if ($this->hasPanneColumnsCache !== null) {
            return $this->hasPanneColumnsCache;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM rendezvous_digital LIKE 'circonstances_panne'");
            $this->hasPanneColumnsCache = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->hasPanneColumnsCache = false;
        }

        return $this->hasPanneColumnsCache;
    }

    private function hasUrgenceColumns(): bool
    {
        if ($this->hasUrgenceColumnsCache !== null) {
            return $this->hasUrgenceColumnsCache;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM rendezvous_digital LIKE 'urgence_score'");
            $this->hasUrgenceColumnsCache = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $this->hasUrgenceColumnsCache = false;
        }

        return $this->hasUrgenceColumnsCache;
    }

    private function computeUrgencePayload(array $data, ?int $rdvId = null): array
    {
        $observer = new RendezVousObserver($this->db);
        return $observer->computeUrgence($data, $rdvId);
    }

    private function maybeBroadcastUrgence(int $rdvId, array $urgence): void
    {
        if (empty($urgence['score'])) {
            return;
        }

        $observer = new RendezVousObserver($this->db);
        if (!$observer->shouldBroadcast((int) $urgence['score'])) {
            return;
        }

        $summary = $observer->fetchRdvSummary($rdvId);
        if (!$summary) {
            return;
        }

        $urgenceService = new UrgenceService();
        $config = $urgenceService->getConfig();
        $broadcaster = new UrgenceBroadcaster((array) ($config['broadcast'] ?? []));
        $listener = new RendezVousUrgenceListener($broadcaster);
        $event = new RendezVousUrgenceUpdated($rdvId, (int) $urgence['score'], (array) $urgence['details'], $summary);
        $listener->handle($event);
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

    private function temoinsToLabel($rawTemoins): string
    {
        if ($rawTemoins === null || $rawTemoins === '') {
            return '-';
        }

        $decoded = json_decode((string) $rawTemoins, true);
        if (!is_array($decoded) || empty($decoded)) {
            return '-';
        }

        return implode(', ', array_map(static fn($item) => (string) $item, $decoded));
    }

    private function truncatePdfLine(string $line, int $maxLen): string
    {
        if (mb_strlen($line, 'UTF-8') <= $maxLen) {
            return $line;
        }

        return mb_substr($line, 0, $maxLen - 3, 'UTF-8') . '...';
    }

    private function buildSimplePdf(array $lines): string
    {
        $linesPerPage = 48;
        $pagesLines = array_chunk($lines, $linesPerPage);

        $objects = [];
        $nextId = 1;

        $catalogId = $nextId++;
        $pagesId = $nextId++;
        $fontId = $nextId++;

        $objects[$fontId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        $pageIds = [];

        foreach ($pagesLines as $pageLines) {
            $content = "BT\n/F1 10 Tf\n14 TL\n40 805 Td\n";
            foreach ($pageLines as $line) {
                $content .= '(' . $this->pdfEscape($line) . ") Tj\nT*\n";
            }
            $content .= "ET";

            $contentId = $nextId++;
            $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

            $pageId = $nextId++;
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";
            $pageIds[] = $pageId;
        }

        $kids = implode(' ', array_map(static fn($id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = "<< /Type /Pages /Kids [{$kids}] /Count " . count($pageIds) . " >>";
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id] ?? 0) . "\n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function pdfEscape(string $text): string
    {
        $encoded = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
        if ($encoded === false) {
            $encoded = $text;
        }

        $encoded = str_replace('\\', '\\\\', $encoded);
        $encoded = str_replace('(', '\\(', $encoded);
        $encoded = str_replace(')', '\\)', $encoded);

        return $encoded;
    }
}
