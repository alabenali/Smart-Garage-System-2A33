<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
<<<<<<< HEAD
    private PDO $db;

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
    
    private function verifyRecaptcha(string $recaptchaResponse): bool {  //   // . Appeler l'API Google pour vérifier le token
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
            header('Location: /projet_final/controllers/AdminController.php?action=showLogin');
=======
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: ../views/backoffice/admin_login.php');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
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
<<<<<<< HEAD
        $recaptcha = $_POST['g-recaptcha-response'] ?? '';

        // Validate reCAPTCHA
        if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
            if (!$this->verifyRecaptcha($recaptcha)) {
                $errors[] = "Veuillez valider le CAPTCHA.";
            }
        }
=======
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if (empty($password) || strlen($password) < 6)
            $errors[] = "Mot de passe trop court (min. 6 caractères).";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
<<<<<<< HEAD
            header('Location: /projet_final/controllers/AdminController.php?action=showLogin');
            exit;
        }

        $row = $this->verifyPassword($email, $password);
        if ($row && $row['post'] === 'admin') {
            $_SESSION['admin_id']          = $row['id'];
            $_SESSION['admin_nom']         = $row['nom'] . ' ' . $row['prenom'];
            $_SESSION['admin_profile_pic'] = $row['profile_picture'] ?? null;
            $_SESSION['role']              = 'admin';
            header('Location: /projet_final/controllers/AdminController.php?action=showDashboard');
            exit;
        } else {
            $_SESSION['errors'] = ["Identifiants administrateur incorrects."];
            header('Location: /projet_final/controllers/AdminController.php?action=showLogin');
=======
            header('Location: ../views/backoffice/admin_login.php');
            exit;
        }

        // Vérification : post = 'admin' obligatoire
        $user = $this->userModel->verifyPassword($email, $password);
        if ($user && $user['post'] === 'admin') {
            $_SESSION['admin_id']  = $user['id'];
            $_SESSION['admin_nom'] = $user['nom'] . ' ' . $user['prenom'];
            $_SESSION['role']      = 'admin';
            header('Location: ../views/backoffice/admin_dashboard.php');
            exit;
        } else {
            $_SESSION['errors'] = ["Identifiants administrateur incorrects."];
            header('Location: ../views/backoffice/admin_login.php');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
            exit;
        }
    }

    public function showDashboard(): void {
        $this->requireAdmin();
<<<<<<< HEAD
        $totalUsers  = $this->countAll();
        $activeUsers = $this->countActive();
=======
        $totalUsers  = $this->userModel->countAll();
        $activeUsers = $this->userModel->countActive();
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        require_once __DIR__ . '/../views/backoffice/admin_dashboard.php';
    }

    public function listUsers(): void {
        $this->requireAdmin();
<<<<<<< HEAD
        $users = $this->getAll();
=======
        $users = $this->userModel->getAll();
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        require_once __DIR__ . '/../views/backoffice/users_list.php';
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

<<<<<<< HEAD
        if (empty($nom) || strlen($nom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $nom))       $errors[] = "Nom invalide (lettres uniquement, min. 2 caractères).";
        if (empty($prenom) || strlen($prenom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $prenom)) $errors[] = "Prénom invalide (lettres uniquement).";
=======
        if (empty($nom) || strlen($nom) < 2)          $errors[] = "Nom invalide (min. 2 caractères).";
        if (empty($prenom) || strlen($prenom) < 2)    $errors[] = "Prénom invalide.";
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (!empty($telephone) && !preg_match('/^\+?[0-9\s\-]{8,15}$/', $telephone)) $errors[] = "Téléphone invalide.";
        if (strlen($password) < 6)                    $errors[] = "Mot de passe trop court (min. 6 caractères).";
        if (!in_array($statut, ['actif', 'inactif']))  $errors[] = "Statut invalide.";
<<<<<<< HEAD
        if ($this->emailExists($email))                $errors[] = "Email déjà utilisé.";
=======
        if ($this->userModel->emailExists($email))     $errors[] = "Email déjà utilisé.";
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
<<<<<<< HEAD
            header('Location: /projet_final/controllers/AdminController.php?action=showAddUser');
            exit;
        }

        $this->create([
=======
            header('Location: ../views/backoffice/add_user.php');
            exit;
        }

        $this->userModel->create([
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
            'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
            'telephone' => $telephone, 'adresse' => $adresse,
            'mot_de_passe' => $password, 'statut' => $statut, 'post' => 'client',
        ]);

        $_SESSION['success'] = "Utilisateur ajouté avec succès !";
<<<<<<< HEAD
        header('Location: /projet_final/controllers/AdminController.php?action=listUsers');
=======
        header('Location: ../views/backoffice/users_list.php?action=listUsers');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        exit;
    }

    public function showEditUser(): void {
        $this->requireAdmin();
        $id   = (int)($_GET['id'] ?? 0);
<<<<<<< HEAD
        $user = $this->getById($id);
        if (!$user) {
            $_SESSION['errors'] = ["Utilisateur introuvable."];
            header('Location: /projet_final/controllers/AdminController.php?action=listUsers');
=======
        $user = $this->userModel->getById($id);
        if (!$user) {
            $_SESSION['errors'] = ["Utilisateur introuvable."];
            header('Location: ../views/backoffice/users_list.php?action=listUsers');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
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

<<<<<<< HEAD
        if (empty($nom) || strlen($nom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $nom))       $errors[] = "Nom invalide (lettres uniquement, min. 2 caractères).";
        if (empty($prenom) || strlen($prenom) < 2 || !preg_match('/^[a-zA-ZÀ-ÖØ-öø-ÿ \'\\-]+$/', $prenom)) $errors[] = "Prénom invalide (lettres uniquement).";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (!in_array($statut, ['actif', 'inactif']))   $errors[] = "Statut invalide.";
        if ($this->emailExists($email, $id))            $errors[] = "Email déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /projet_final/controllers/AdminController.php?action=showEditUser&id=' . $id);
            exit;
        }

        $this->update($id, [
=======
        if (empty($nom) || strlen($nom) < 2)           $errors[] = "Nom invalide.";
        if (empty($prenom) || strlen($prenom) < 2)     $errors[] = "Prénom invalide.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (!in_array($statut, ['actif', 'inactif']))   $errors[] = "Statut invalide.";
        if ($this->userModel->emailExists($email, $id)) $errors[] = "Email déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ../views/backoffice/edit_user.php?action=showEditUser&id=' . $id);
            exit;
        }

        $this->userModel->update($id, [
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
            'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
            'telephone' => $telephone, 'adresse' => $adresse, 'statut' => $statut,
        ]);

<<<<<<< HEAD
        // Gestion photo de profil du client
        $profilePic = $this->handleProfilePictureUpload($id);
        if ($profilePic) {
            $this->db->prepare("UPDATE user SET profile_picture = :pic WHERE id = :id")
                     ->execute([':pic' => $profilePic, ':id' => $id]);
        }

        $_SESSION['success'] = "Utilisateur modifié avec succès !";
        header('Location: /projet_final/controllers/AdminController.php?action=listUsers');
=======
        $_SESSION['success'] = "Utilisateur modifié avec succès !";
        header('Location: ../views/backoffice/users_list.php?action=listUsers');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        exit;
    }

    public function deleteUser(): void {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
<<<<<<< HEAD
            $this->delete($id);
            $_SESSION['success'] = "Utilisateur supprimé.";
        }
        header('Location: /projet_final/controllers/AdminController.php?action=listUsers');
=======
            $this->userModel->delete($id);
            $_SESSION['success'] = "Utilisateur supprimé.";
        }
        header('Location: ../views/backoffice/users_list.php?action=listUsers');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        exit;
    }

    public function logout(): void {
<<<<<<< HEAD
    session_destroy();
    header('Location: /projet_final/views/frontoffice/login.php');
    exit;
}

    // ── Profil Admin ──────────────────────────────────────────────────

    public function showAdminProfile(): void {
        $this->requireAdmin();
        $id   = (int) $_SESSION['admin_id'];
        $admin = $this->getById($id);
        if (!$admin) {
            $_SESSION['errors'] = ["Compte administrateur introuvable."];
            header('Location: /projet_final/controllers/AdminController.php?action=showDashboard');
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
            header('Location: /projet_final/controllers/AdminController.php?action=showAdminProfile');
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
        header('Location: /projet_final/controllers/AdminController.php?action=showAdminProfile');
        exit;
    }

    
    
    

    
    // ── Statistiques ─────────────────────────────────────────────────

    public function showStatistics(): void {
        $this->requireAdmin();
        
        // Statistiques des clients
        $totalClients = (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client'")->fetchColumn();
        $activeClients = (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND statut = 'actif'")->fetchColumn();
        $inactiveClients = $totalClients - $activeClients;
        
        // Statistiques par mois (inscriptions des 12 derniers mois)
        $monthlyStats = $this->db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM user 
            WHERE post = 'client' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Statistiques par jour de la semaine
        $dayStats = $this->db->query("
            SELECT DAYOFWEEK(created_at) as day_num, COUNT(*) as count 
            FROM user 
            WHERE post = 'client' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DAYOFWEEK(created_at)
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Répartition par statut
        $statutStats = $this->db->query("
            SELECT statut, COUNT(*) as count 
            FROM user 
            WHERE post = 'client'
            GROUP BY statut
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Clients avec email vérifié
        $verifiedClients = (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND email_verified = 1")->fetchColumn();
        
        // Clients avec photo de profil
        $clientsWithPhoto = (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND profile_picture IS NOT NULL")->fetchColumn();
        
        require_once __DIR__ . '/../views/backoffice/statistics.php';
    }


=======
        session_destroy();
        header('Location: ../views/backoffice/admin_login.php');
        exit;
    }
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
}

// ── ROUTEUR BACK ──────────────────────────────────────────────────
$controller = new AdminController();
$action = $_GET['action'] ?? 'showLogin';

<<<<<<< HEAD
$allowedActions = ['showLogin','login','showDashboard','listUsers','showAddUser','addUser','showEditUser','editUser','deleteUser','logout','showAdminProfile','updateAdminProfile','showStatistics'];
=======
$allowedActions = ['showLogin','login','showDashboard','listUsers','showAddUser','addUser','showEditUser','editUser','deleteUser','logout'];
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
if (in_array($action, $allowedActions)) {
    $controller->$action();
} else {
    $controller->showLogin();
<<<<<<< HEAD
}
=======
}
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
