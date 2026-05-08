<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private PDO $db;
    private ?PDO $garageDb = null;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── Helpers DB (anciennement dans User.php) ───────────────────────

    private function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM user WHERE post = 'client' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    private function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function getByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    private function create(array $data): bool {
        $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO user (nom, prenom, email, telephone, adresse, mot_de_passe, statut, post)
            VALUES (:nom, :prenom, :email, :telephone, :adresse, :mot_de_passe, :statut, :post)
        ");
        return $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':telephone'    => $data['telephone'] ?? null,
            ':adresse'      => $data['adresse'] ?? null,
            ':mot_de_passe' => $hashedPassword,
            ':statut'       => $data['statut'] ?? 'actif',
            ':post'         => $data['post'] ?? 'client',
        ]);
    }

    private function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE user
            SET nom = :nom, prenom = :prenom, email = :email,
                telephone = :telephone, adresse = :adresse, statut = :statut
            WHERE id = :id
        ");
        return $stmt->execute([
            ':nom'       => $data['nom'],
            ':prenom'    => $data['prenom'],
            ':email'     => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':adresse'   => $data['adresse'] ?? null,
            ':statut'    => $data['statut'],
            ':id'        => $id,
        ]);
    }

    private function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM user WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    private function countAll(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client'")->fetchColumn();
    }

    private function countActive(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND statut = 'actif'")->fetchColumn();
    }

    private function getGarageDb(): ?PDO {
        if ($this->garageDb instanceof PDO) {
            return $this->garageDb;
        }

        try {
            $this->garageDb = new PDO(
                'mysql:host=localhost;dbname=smart_garage;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Throwable $e) {
            $this->garageDb = null;
        }

        return $this->garageDb;
    }

    private function garageFetchAll(string $sql, array $params = []): array {
        $garageDb = $this->getGarageDb();
        if (!$garageDb) {
            return [];
        }

        try {
            $stmt = $garageDb->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    private function garageFetchColumn(string $sql, array $params = [], mixed $default = 0): mixed {
        $garageDb = $this->getGarageDb();
        if (!$garageDb) {
            return $default;
        }

        try {
            $stmt = $garageDb->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return $value === false ? $default : $value;
        } catch (Throwable $e) {
            return $default;
        }
    }

    private function getGroupedCounts(string $table, string $column): array {
        $rows = $this->garageFetchAll("SELECT {$column} AS id_client, COUNT(*) AS total FROM {$table} WHERE {$column} IS NOT NULL GROUP BY {$column}");
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['id_client']] = (int) $row['total'];
        }
        return $counts;
    }

    private function enrichUsersWithGarageStats(array $users): array {
        $vehicleCounts = $this->getGroupedCounts('vehicle', 'id_client');
        $rdvCounts = $this->getGroupedCounts('rendezvous_digital', 'id_client');
        $urgenceRows = $this->garageFetchAll('SELECT id_client, AVG(COALESCE(urgence_score, 0)) AS avg_urgence FROM rendezvous_digital WHERE id_client IS NOT NULL GROUP BY id_client');
        $urgenceByClient = [];
        foreach ($urgenceRows as $row) {
            $urgenceByClient[(int) $row['id_client']] = round((float) $row['avg_urgence'], 1);
        }

        foreach ($users as &$user) {
            $id = (int) ($user['id'] ?? 0);
            $user['vehicles_count'] = $vehicleCounts[$id] ?? 0;
            $user['rdv_count'] = $rdvCounts[$id] ?? 0;
            $user['avg_urgence'] = $urgenceByClient[$id] ?? 0;
        }
        unset($user);

        return $users;
    }

    private function getUsersByIds(array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id, nom, prenom, email, telephone, statut FROM user WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['id']] = $row;
        }
        return $map;
    }

    private function getClientVehicles(int $idClient): array {
        return $this->garageFetchAll('SELECT * FROM vehicle WHERE id_client = :id_client ORDER BY date_ajout DESC, id DESC', [
            ':id_client' => $idClient,
        ]);
    }

    private function getClientRendezvous(int $idClient): array {
        return $this->garageFetchAll(
            'SELECT r.*, c.date_heure, v.marque, v.modele, v.immatriculation
             FROM rendezvous_digital r
             LEFT JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
             LEFT JOIN vehicle v ON v.id = r.id_vehicle
             WHERE r.id_client = :id_client
             ORDER BY COALESCE(c.date_heure, r.date_creation) DESC, r.id_rdv DESC',
            [':id_client' => $idClient]
        );
    }

    private function buildAdminStats(): array {
        $totalUsers = $this->countAll();
        $activeUsers = $this->countActive();
        $inactiveUsers = max(0, $totalUsers - $activeUsers);
        $newThisMonth = (int) $this->db
            ->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")
            ->fetchColumn();

        $totalVehicles = (int) $this->garageFetchColumn('SELECT COUNT(*) FROM vehicle', [], 0);
        $totalRdv = (int) $this->garageFetchColumn('SELECT COUNT(*) FROM rendezvous_digital', [], 0);
        $avgUrgence = round((float) $this->garageFetchColumn('SELECT AVG(COALESCE(urgence_score, 0)) FROM rendezvous_digital', [], 0), 1);
        $avgVehiclesPerClient = $totalUsers > 0 ? round($totalVehicles / $totalUsers, 1) : 0;
        $avgRdvPerClient = $totalUsers > 0 ? round($totalRdv / $totalUsers, 1) : 0;

        $topActiveRows = $this->garageFetchAll(
            'SELECT id_client, COUNT(*) AS rdv_total
             FROM rendezvous_digital
             WHERE id_client IS NOT NULL
             GROUP BY id_client
             ORDER BY rdv_total DESC
             LIMIT 5'
        );
        $clientsById = $this->getUsersByIds(array_column($topActiveRows, 'id_client'));
        $topActiveClients = [];
        foreach ($topActiveRows as $row) {
            $clientId = (int) $row['id_client'];
            $client = $clientsById[$clientId] ?? null;
            $topActiveClients[] = [
                'id' => $clientId,
                'name' => $client ? trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) : 'Client #' . $clientId,
                'email' => $client['email'] ?? '-',
                'rdv_total' => (int) $row['rdv_total'],
            ];
        }

        $problematicVehicles = $this->garageFetchAll(
            'SELECT v.id, v.marque, v.modele, v.immatriculation, v.id_client,
                    COUNT(r.id_rdv) AS rdv_total,
                    AVG(COALESCE(r.urgence_score, 0)) AS avg_urgence,
                    SUM(CASE WHEN COALESCE(r.urgence_score, 0) >= 7 THEN 1 ELSE 0 END) AS urgent_total
             FROM vehicle v
             LEFT JOIN rendezvous_digital r ON r.id_vehicle = v.id
             GROUP BY v.id, v.marque, v.modele, v.immatriculation, v.id_client
             HAVING rdv_total > 0
             ORDER BY urgent_total DESC, avg_urgence DESC, rdv_total DESC
             LIMIT 5'
        );

        return [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'newThisMonth' => $newThisMonth,
            'totalVehicles' => $totalVehicles,
            'totalRdv' => $totalRdv,
            'avgUrgence' => $avgUrgence,
            'avgVehiclesPerClient' => $avgVehiclesPerClient,
            'avgRdvPerClient' => $avgRdvPerClient,
            'topActiveClients' => $topActiveClients,
            'problematicVehicles' => $problematicVehicles,
        ];
    }

    private function verifyPassword(string $email, string $password): array|false {
        $row = $this->getByEmail($email);
        if ($row && password_verify($password, $row['mot_de_passe'])) {
            return $row;
        }
        return false;
    }

    private function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM user WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $excludeId]);
        return $stmt->fetch() !== false;
    }

    private function hydrateUser(array $row): User {
        $user = new User();
        $user->setId((int) $row['id']);
        $user->setNom($row['nom']);
        $user->setPrenom($row['prenom']);
        $user->setEmail($row['email']);
        $user->setTelephone($row['telephone'] ?? null);
        $user->setAdresse($row['adresse'] ?? null);
        $user->setMotDePasse($row['mot_de_passe']);
        $user->setStatut($row['statut'] ?? 'actif');
        $user->setPost($row['post'] ?? 'client');
        $user->setCreatedAt($row['created_at'] ?? null);
        return $user;
    }

    // ── reCAPTCHA Validation Helper ─────────────────────────────────────────
    
    private function verifyRecaptcha(string $recaptchaResponse): bool {
        if (!defined('RECAPTCHA_ENABLED') || !RECAPTCHA_ENABLED) {
            return true;
        }
        
        if (empty($recaptchaResponse)) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }

    // ── Profile Picture Upload Helper ────────────────────────────────────────
    
    private function handleProfilePictureUpload(int $userId): ?string {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024;
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $_SESSION['errors'] = ["Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WebP."];
            return null;
        }
        
        if ($file['size'] > $maxSize) {
            $_SESSION['errors'] = ["La taille de l'image ne doit pas dépasser 2 Mo."];
            return null;
        }
        
        $uploadDir = __DIR__ . '/../uploads/profile_pictures';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $user = $this->getById($userId);
            if (!empty($user['profile_picture']) && strpos($user['profile_picture'], 'uploads/') === 0) {
                $oldFile = __DIR__ . '/../' . $user['profile_picture'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            return 'uploads/profile_pictures/' . $filename;
        }
        
        return null;
    }

    // ── Actions ───────────────────────────────────────────────────────

    private function requireAdmin(): void {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
            exit;
        }
    }

    public function showLogin(): void {
        require_once __DIR__ . '/../views/backoffice/admin_login.php';
    }

    public function login(): void {
        $errors   = [];
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['mot_de_passe'] ?? '');
        $recaptcha = $_POST['g-recaptcha-response'] ?? '';

        // Validate reCAPTCHA
        if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
            if (!$this->verifyRecaptcha($recaptcha)) {
                $errors[] = "Veuillez valider le CAPTCHA.";
            }
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if (empty($password) || strlen($password) < 6)
            $errors[] = "Mot de passe trop court (min. 6 caractères).";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
            exit;
        }

        $row = $this->verifyPassword($email, $password);
        if ($row && $row['post'] === 'admin') {
            $_SESSION['admin_id']          = $row['id'];
            $_SESSION['admin_nom']         = $row['nom'] . ' ' . $row['prenom'];
            $_SESSION['admin_profile_pic'] = $row['profile_picture'] ?? null;
            $_SESSION['role']              = 'admin';
            header('Location: /integration/client/controllers/AdminController.php?action=showDashboard');
            exit;
        } else {
            $_SESSION['errors'] = ["Identifiants administrateur incorrects."];
            header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
            exit;
        }
    }

    public function showDashboard(): void {
        $this->requireAdmin();
        $stats = $this->buildAdminStats();
        extract($stats);
        require_once __DIR__ . '/../views/backoffice/admin_dashboard.php';
    }

    public function listUsers(): void {
        $this->requireAdmin();
        $users = $this->enrichUsersWithGarageStats($this->getAll());
        require_once __DIR__ . '/../views/backoffice/users_list.php';
    }

    public function showClientDetail(): void {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $user = $this->getById($id);
        if (!$user || ($user['post'] ?? '') !== 'client') {
            $_SESSION['errors'] = ["Client introuvable."];
            header('Location: /integration/client/controllers/AdminController.php?action=listUsers');
            exit;
        }

        $vehicles = $this->getClientVehicles($id);
        $rdvs = $this->getClientRendezvous($id);
        $avgUrgence = 0;
        if (!empty($rdvs)) {
            $sum = array_sum(array_map(static fn($row) => (int) ($row['urgence_score'] ?? 0), $rdvs));
            $avgUrgence = round($sum / count($rdvs), 1);
        }

        require_once __DIR__ . '/../views/backoffice/client_detail.php';
    }

    public function showAddUser(): void {
        $this->requireAdmin();
        require_once __DIR__ . '/../views/backoffice/add_user.php';
    }

    public function addUser(): void {
        $this->requireAdmin();
        $errors    = [];
        $nom       = trim($_POST['nom'] ?? '');
        $prenom    = trim($_POST['prenom'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse   = trim($_POST['adresse'] ?? '');
        $password  = trim($_POST['mot_de_passe'] ?? '');
        $statut    = $_POST['statut'] ?? 'actif';

        if (empty($nom) || strlen($nom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $nom))       $errors[] = "Nom invalide (lettres uniquement, min. 2 caractères).";
        if (empty($prenom) || strlen($prenom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $prenom)) $errors[] = "Prénom invalide (lettres uniquement).";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (!empty($telephone) && !preg_match('/^\+?[0-9\s\-]{8,15}$/', $telephone)) $errors[] = "Téléphone invalide.";
        if (strlen($password) < 6)                    $errors[] = "Mot de passe trop court (min. 6 caractères).";
        if (!in_array($statut, ['actif', 'inactif']))  $errors[] = "Statut invalide.";
        if ($this->emailExists($email))                $errors[] = "Email déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            header('Location: /integration/client/controllers/AdminController.php?action=showAddUser');
            exit;
        }

        $this->create([
            'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
            'telephone' => $telephone, 'adresse' => $adresse,
            'mot_de_passe' => $password, 'statut' => $statut, 'post' => 'client',
        ]);

        $_SESSION['success'] = "Utilisateur ajouté avec succès !";
        header('Location: /integration/client/controllers/AdminController.php?action=listUsers');
        exit;
    }

    public function showEditUser(): void {
        $this->requireAdmin();
        $id   = (int)($_GET['id'] ?? 0);
        $user = $this->getById($id);
        if (!$user) {
            $_SESSION['errors'] = ["Utilisateur introuvable."];
            header('Location: /integration/client/controllers/AdminController.php?action=listUsers');
            exit;
        }
        require_once __DIR__ . '/../views/backoffice/edit_user.php';
    }

    public function editUser(): void {
        $this->requireAdmin();
        $errors    = [];
        $id        = (int)($_POST['id'] ?? 0);
        $nom       = trim($_POST['nom'] ?? '');
        $prenom    = trim($_POST['prenom'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse   = trim($_POST['adresse'] ?? '');
        $statut    = $_POST['statut'] ?? 'actif';

        if (empty($nom) || strlen($nom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $nom))       $errors[] = "Nom invalide (lettres uniquement, min. 2 caractères).";
        if (empty($prenom) || strlen($prenom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $prenom)) $errors[] = "Prénom invalide (lettres uniquement).";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (!in_array($statut, ['actif', 'inactif']))   $errors[] = "Statut invalide.";
        if ($this->emailExists($email, $id))            $errors[] = "Email déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /integration/client/controllers/AdminController.php?action=showEditUser&id=' . $id);
            exit;
        }

        $this->update($id, [
            'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
            'telephone' => $telephone, 'adresse' => $adresse, 'statut' => $statut,
        ]);

        // Gestion photo de profil du client
        $profilePic = $this->handleProfilePictureUpload($id);
        if ($profilePic) {
            $this->db->prepare("UPDATE user SET profile_picture = :pic WHERE id = :id")
                     ->execute([':pic' => $profilePic, ':id' => $id]);
        }

        $_SESSION['success'] = "Utilisateur modifié avec succès !";
        header('Location: /integration/client/controllers/AdminController.php?action=listUsers');
        exit;
    }

    public function deleteUser(): void {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->delete($id);
            $_SESSION['success'] = "Utilisateur supprimé.";
        }
        header('Location: /integration/client/controllers/AdminController.php?action=listUsers');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: /integration/client/views/frontoffice/login.php');
        exit;
    }

    // ── Profil Admin ──────────────────────────────────────────────────

    public function showAdminProfile(): void {
        $this->requireAdmin();
        $id   = (int) $_SESSION['admin_id'];
        $admin = $this->getById($id);
        if (!$admin) {
            $_SESSION['errors'] = ["Compte administrateur introuvable."];
            header('Location: /integration/client/controllers/AdminController.php?action=showDashboard');
            exit;
        }
        require_once __DIR__ . '/../views/backoffice/admin_profile.php';
    }

    public function updateAdminProfile(): void {
        $this->requireAdmin();
        $id        = (int) $_SESSION['admin_id'];
        $errors    = [];
        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse   = trim($_POST['adresse']   ?? '');

        if (empty($nom)   || strlen($nom)   < 2 || !preg_match("/^[a-zA-ZÀ-ÖØ-öø-ÿ '\-]+$/", $nom))
            $errors[] = "Le nom est invalide.";
        if (empty($prenom)|| strlen($prenom) < 2 || !preg_match("/^[a-zA-ZÀ-ÖØ-öø-ÿ '\-]+$/", $prenom))
            $errors[] = "Le prénom est invalide.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if ($this->emailExists($email, $id))
            $errors[] = "Cet email est déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /integration/client/controllers/AdminController.php?action=showAdminProfile');
            exit;
        }

        $this->update($id, [
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => $email,
            'telephone' => $telephone,
            'adresse'   => $adresse,
            'statut'    => 'actif',
        ]);

        // Gestion photo de profil admin
        $profilePic = $this->handleProfilePictureUpload($id);
        if ($profilePic) {
            $this->db->prepare("UPDATE user SET profile_picture = :pic WHERE id = :id")
                     ->execute([':pic' => $profilePic, ':id' => $id]);
            $_SESSION['admin_profile_pic'] = $profilePic;
        }

        $_SESSION['admin_nom'] = $nom . ' ' . $prenom;
        $_SESSION['success']   = "Profil administrateur mis à jour avec succès !";
        header('Location: /integration/client/controllers/AdminController.php?action=showAdminProfile');
        exit;
    }


}

// ── ROUTEUR BACK ──────────────────────────────────────────────────
$controller = new AdminController();
$action = $_GET['action'] ?? 'showLogin';

$allowedActions = ['showLogin','login','showDashboard','listUsers','showClientDetail','showAddUser','addUser','showEditUser','editUser','deleteUser','logout','showAdminProfile','updateAdminProfile'];
if (in_array($action, $allowedActions)) {
    $controller->$action();
} else {
    $controller->showLogin();
}
