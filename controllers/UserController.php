<?php
// controllers/UserController.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/Mailer.php'; // Mailer.php est maintenant dans controllers/ (déplacé depuis helpers/)

class UserController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── Helpers DB ────────────────────────────────────────────────────────────

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

    private function getByToken(string $column, string $token): array|false {
        // column is internal, not user input
        $stmt = $this->db->prepare("SELECT * FROM user WHERE {$column} = :token");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    private function create(array $data): bool { // pour la creation dans la base de donnees 
        $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO user (nom, prenom, email, telephone, adresse, mot_de_passe,
                              statut, post, email_verified, verification_token)
            VALUES (:nom, :prenom, :email, :telephone, :adresse, :mot_de_passe,
                    :statut, :post, 0, :verification_token)
        ");
        return $stmt->execute([
            ':nom'                => $data['nom'],
            ':prenom'             => $data['prenom'],
            ':email'              => $data['email'],
            ':telephone'          => $data['telephone'] ?? null,
            ':adresse'            => $data['adresse']   ?? null,
            ':mot_de_passe'       => $hashedPassword,
            ':statut'             => $data['statut']    ?? 'actif',
            ':post'               => $data['post']      ?? 'client',
            ':verification_token' => $data['verification_token'],
        ]);
    }
// pour la modif dans la BD 
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
            ':adresse'   => $data['adresse']   ?? null,
            ':statut'    => $data['statut'],
            ':id'        => $id,
        ]);
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
        $user->setAdresse($row['adresse']     ?? null);
        $user->setMotDePasse($row['mot_de_passe']);
        $user->setStatut($row['statut']       ?? 'actif');
        $user->setPost($row['post']           ?? 'client');
        $user->setCreatedAt($row['created_at'] ?? null);
        return $user;
    }

    // ── reCAPTCHA Validation Helper ─────────────────────────────────────────
    
    private function verifyRecaptcha(string $recaptchaResponse): bool {      // . Appeler l'API Google pour vérifier le token
        if (!defined('RECAPTCHA_ENABLED') || !RECAPTCHA_ENABLED) {
            return true; // Skip if not enabled
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
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $_SESSION['errors'] = ["Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WebP."];
            return null;
        }
        
        // Validate file size
        if ($file['size'] > $maxSize) {
            $_SESSION['errors'] = ["La taille de l'image ne doit pas dépasser 2 Mo."];
            return null;
        }
        
        // Create uploads directory if not exists
        $uploadDir = __DIR__ . '/../uploads/profile_pictures';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Delete old profile picture if exists (only uploaded files, not avatars)
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

    // ── LOGIN ─────────────────────────────────────────────────────────────────

    public function showLogin(): void {
        require_once __DIR__ . '/../views/frontoffice/login.php';
    }

    public function login(): void {
        $errors   = [];
        $email    = trim($_POST['email']        ?? '');
        $password = trim($_POST['mot_de_passe'] ?? '');
        $recaptcha = $_POST['g-recaptcha-response'] ?? '';

        // Validate reCAPTCHA
        if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
            if (!$this->verifyRecaptcha($recaptcha)) {
                $errors[] = "Veuillez valider le CAPTCHA.";
            }
        }

        if (empty($email)) {
            $errors[] = "L'email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide.";
        }
        if (empty($password)) {
            $errors[] = "Le mot de passe est obligatoire.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        $row = $this->verifyPassword($email, $password);

        if ($row && $row['post'] === 'admin') {
            // Redirection automatique vers le backoffice pour les admins
            $_SESSION['admin_id']          = $row['id'];
            $_SESSION['admin_nom']         = $row['nom'] . ' ' . $row['prenom'];
            $_SESSION['admin_profile_pic'] = $row['profile_picture'] ?? null;
            $_SESSION['role']              = 'admin';
            header('Location: /projet_final/controllers/AdminController.php?action=showDashboard');
            exit;
        } elseif ($row && $row['post'] === 'client') {
            $_SESSION['user_id']          = $row['id'];
            $_SESSION['user_nom']         = $row['nom'];
            $_SESSION['user_prenom']      = $row['prenom'];
            $_SESSION['user_email']       = $row['email'];
            $_SESSION['user_profile_pic'] = $row['profile_picture'] ?? null;
            $_SESSION['role']             = 'client';
            header('Location: /projet_final/controllers/UserController.php?action=showDashboard');
            exit;
        } else {
            $_SESSION['errors'] = ["Email ou mot de passe incorrect."];
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }
    }

    // ── REGISTER ──────────────────────────────────────────────────────────────

    public function showRegister(): void {
        require_once __DIR__ . '/../views/frontoffice/register.php';
    }





// pour la validation du formulaire 
// et ainsi appeler la fonction d envoi dee mail
    public function register(): void { 
        $errors    = [];
        $nom       = trim($_POST['nom']             ?? '');
        $prenom    = trim($_POST['prenom']           ?? '');
        $email     = trim($_POST['email']            ?? '');
        $telephone = trim($_POST['telephone']        ?? '');
        $adresse   = trim($_POST['adresse']          ?? '');
        $password  = trim($_POST['mot_de_passe']     ?? '');
        $confirm   = trim($_POST['confirm_password'] ?? '');
        $recaptcha = $_POST['g-recaptcha-response']  ?? '';

        // Validate reCAPTCHA
        if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED) {
            if (!$this->verifyRecaptcha($recaptcha)) {
                $errors[] = "Veuillez valider le CAPTCHA.";
            }
        }

        if (empty($nom)    || strlen($nom)    < 2 || !preg_match("/^[a-zA-ZÀ-ÖØ-öø-ÿ '\-]+$/", $nom))
            $errors[] = "Le nom doit contenir au moins 2 caractères (lettres uniquement).";
        if (empty($prenom) || strlen($prenom) < 2 || !preg_match("/^[a-zA-ZÀ-ÖØ-öø-ÿ '\-]+$/", $prenom))
            $errors[] = "Le prénom doit contenir au moins 2 caractères (lettres uniquement).";
        if (empty($email)  || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        elseif ($this->emailExists($email))
            $errors[] = "Un compte existe déjà avec cet email.";
        if (!empty($telephone) && !preg_match('/^\+?[0-9\s\-]{8,15}$/', $telephone))
            $errors[] = "Numéro de téléphone invalide.";
        if (strlen($password) < 6)
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        if ($password !== $confirm)
            $errors[] = "Les mots de passe ne correspondent pas.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
            exit;
        }

        // Générer un code à 6 chiffres et stocker les données en session (pas encore en base)
        $code = $this->generateResetCode();
        $_SESSION['pending_register'] = [
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => $email,
            'telephone' => $telephone,
            'adresse'   => $adresse,
            'password'  => $password,
            'code'      => $code,
            'expires'   => time() + 900,
        ];

        Mailer::sendRegisterCode($email, $prenom . ' ' . $nom, $code);

        header('Location: /projet_final/controllers/UserController.php?action=showVerifyRegister');
        exit;
    }

    public function showVerifyRegister(): void {
        if (empty($_SESSION['pending_register'])) {
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
            exit;
        }
        require_once __DIR__ . '/../views/frontoffice/verify_register.php';
    }
// verifier le code de confirmation d inscription
    public function verifyRegisterCode(): void { 

        $code    = trim($_POST['register_code'] ?? '');
        $pending = $_SESSION['pending_register'] ?? null;

        if (!$pending) {
            $_SESSION['errors'] = ["Session expirée. Veuillez recommencer l'inscription."];
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
            exit;
        }

        if (time() > $pending['expires']) {
            unset($_SESSION['pending_register']);
            $_SESSION['errors'] = ["Le code a expiré. Veuillez recommencer l'inscription."];
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
            exit;
        }

        if ($code !== $pending['code']) {
            $_SESSION['errors'] = ["❌ Code incorrect. Vérifiez votre email et réessayez."];
            header('Location: /projet_final/controllers/UserController.php?action=showVerifyRegister');
            exit;
        }

        // Code correct → créer le compte
        $created = $this->create([
            'nom'                => $pending['nom'],
            'prenom'             => $pending['prenom'],
            'email'              => $pending['email'],
            'telephone'          => $pending['telephone'],
            'adresse'            => $pending['adresse'],
            'mot_de_passe'       => $pending['password'],
            'post'               => 'client',
            'verification_token' => null,
        ]);

        unset($_SESSION['pending_register']);

        if ($created) {
            $_SESSION['success'] = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
        } else {
            $_SESSION['errors'] = ["❌ Inscription échouée. Veuillez réessayer."];
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
        }
        exit;
    }
// la fct SendRgisterCode se trouve dans mailer.php
    public function resendRegisterCode(): void { 
        $pending = $_SESSION['pending_register'] ?? null;
        if (!$pending) {
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
            exit;
        }
        $code = $this->generateResetCode();
        $_SESSION['pending_register']['code']    = $code;
        $_SESSION['pending_register']['expires'] = time() + 900;
        Mailer::sendRegisterCode($pending['email'], $pending['prenom'] . ' ' . $pending['nom'], $code);
        $_SESSION['success'] = "Un nouveau code a été envoyé à votre adresse email.";
        header('Location: /projet_final/controllers/UserController.php?action=showVerifyRegister');
        exit;
    }

    // ── FORGOT PASSWORD ───────────────────────────────────────────────────────

    public function showForgotPassword(): void {
        require_once __DIR__ . '/../views/frontoffice/forgot_password.php';
    }







// fct qui genere les codessssss
    private function generateResetCode(): string {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }






// valider l existence de mail dans la BD 
// et puis appeler la fct d envoi de mail 
    public function forgotPassword(): void {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors'] = ["Veuillez saisir un email valide."];
            header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
            exit;
        }

        $row = $this->getByEmail($email);
        if ($row) {
            $code    = $this->generateResetCode();
            $expires = date('Y-m-d H:i:s', time() + 900); // 15 minutes
            // Sauvegarde le code en base :: 
            $this->db->prepare("UPDATE user SET reset_token = :code, reset_token_expires = :expires WHERE id = :id")
                     ->execute([':code' => $code, ':expires' => $expires, ':id' => $row['id']]);
            Mailer::sendResetCode($email, $row['prenom'] . ' ' . $row['nom'], $code);
            $_SESSION['reset_email'] = $email;
        }

        // Même message que le compte existe ou non (sécurité)
        $_SESSION['success'] = "Si cet email est associé à un compte, vous recevrez un code dans quelques minutes.";
        header('Location: /projet_final/controllers/UserController.php?action=showVerifyCode');
        exit;
    }

    public function showVerifyCode(): void {
        if (empty($_SESSION['reset_email'])) {
            header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
            exit;
        }
        require_once __DIR__ . '/../views/frontoffice/verify_code.php';
    }
//verifier le code de reinstallation de mdp
    public function verifyResetCode(): void {
        $code  = trim($_POST['reset_code'] ?? '');
        $email = $_SESSION['reset_email']  ?? '';

        if (empty($email)) {
            header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
            exit;
        }

        if (empty($code) || strlen($code) !== 6) {
            $_SESSION['errors'] = ["Veuillez entrer le code à 6 chiffres."];
            header('Location: /projet_final/controllers/UserController.php?action=showVerifyCode');
            exit;
        }

        $row = $this->getByEmail($email);
        if (!$row || $row['reset_token'] !== $code) {
            $_SESSION['errors'] = ["Code incorrect. Vérifiez votre email et réessayez."];
            header('Location: /projet_final/controllers/UserController.php?action=showVerifyCode');
            exit;
        }

        if (strtotime($row['reset_token_expires']) < time()) {
            $_SESSION['errors'] = ["Ce code a expiré. Veuillez faire une nouvelle demande."];
            $this->db->prepare("UPDATE user SET reset_token = NULL, reset_token_expires = NULL WHERE id = :id")
                     ->execute([':id' => $row['id']]);
            unset($_SESSION['reset_email']);
            header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
            exit;
        }

        // Code valide → on autorise la page de nouveau mot de passe
        $_SESSION['reset_verified'] = true;
        $_SESSION['reset_user_id']  = $row['id'];
        // Invalider le code immédiatement
        $this->db->prepare("UPDATE user SET reset_token = NULL, reset_token_expires = NULL WHERE id = :id")
                 ->execute([':id' => $row['id']]);

        header('Location: /projet_final/controllers/UserController.php?action=showResetPassword');
        exit;
    }

    public function resendResetCode(): void {
        $email = $_SESSION['reset_email'] ?? '';

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $row = $this->getByEmail($email);
            if ($row) {
                $code    = $this->generateResetCode();
                $expires = date('Y-m-d H:i:s', time() + 900);
                $this->db->prepare("UPDATE user SET reset_token = :code, reset_token_expires = :expires WHERE id = :id")
                         ->execute([':code' => $code, ':expires' => $expires, ':id' => $row['id']]);
                Mailer::sendResetCode($email, $row['prenom'] . ' ' . $row['nom'], $code);
            }
        }

        $_SESSION['success'] = "Un nouveau code a été envoyé à votre adresse email.";
        header('Location: /projet_final/controllers/UserController.php?action=showVerifyCode');
        exit;
    }

    public function showResetPassword(): void {
        if (empty($_SESSION['reset_verified'])) {
            $_SESSION['errors'] = ["Veuillez d'abord vérifier votre code."];
            header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
            exit;
        }
        require_once __DIR__ . '/../views/frontoffice/reset_password.php';
    }
// fct qui modifie le mdp dans la    BD
    public function resetPassword(): void {
        $password = trim($_POST['mot_de_passe']     ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');
        $userId   = $_SESSION['reset_user_id']      ?? 0;
        $errors   = [];

        if (empty($_SESSION['reset_verified']) || !$userId) {
            $_SESSION['errors'] = ["Session expirée. Recommencez la procédure."];
            header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
            exit;
        }

        if (strlen($password) < 6)  $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        if ($password !== $confirm)  $errors[] = "Les mots de passe ne correspondent pas.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /projet_final/controllers/UserController.php?action=showResetPassword');
            exit;
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $this->db->prepare("UPDATE user SET mot_de_passe = :mdp WHERE id = :id")
                 ->execute([':mdp' => $hashed, ':id' => $userId]);

        unset($_SESSION['reset_verified'], $_SESSION['reset_user_id'], $_SESSION['reset_email']);
        $_SESSION['success'] = "✅ Mot de passe réinitialisé avec succès ! Connectez-vous.";
        header('Location: /projet_final/controllers/UserController.php?action=showLogin');
        exit;
    }








    // ── PROFIL & DASHBOARD ────────────────────────────────────────────────────

    public function showProfile(): void {
        if (!isset($_SESSION['user_id'])) { header('Location: /projet_final/controllers/UserController.php?action=showLogin'); exit; }
        $user = $this->getById($_SESSION['user_id']);
        require_once __DIR__ . '/../views/frontoffice/profile.php';
    }

    public function updateProfile(): void {
        if (!isset($_SESSION['user_id'])) { header('Location: /projet_final/controllers/UserController.php?action=showLogin'); exit; }

        $errors    = [];
        $id        = (int) $_SESSION['user_id'];
        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse   = trim($_POST['adresse']   ?? '');

        if (empty($nom)    || strlen($nom)    < 2 || !preg_match("/^[a-zA-ZÀ-ÖØ-öø-ÿ '\-]+$/", $nom))
            $errors[] = "Le nom est invalide.";
        if (empty($prenom) || strlen($prenom) < 2 || !preg_match("/^[a-zA-ZÀ-ÖØ-öø-ÿ '\-]+$/", $prenom))
            $errors[] = "Le prénom est invalide.";
        if (empty($email)  || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if ($this->emailExists($email, $id))
            $errors[] = "Cet email est déjà utilisé par un autre compte.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: /projet_final/controllers/UserController.php?action=showProfile');
            exit;
        }

        $this->update($id, ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'telephone' => $telephone, 'adresse' => $adresse, 'statut' => 'actif']);
        
        // Handle profile picture upload
        $profilePic = $this->handleProfilePictureUpload($id);
        if ($profilePic) {
            $this->db->prepare("UPDATE user SET profile_picture = :pic WHERE id = :id")
                     ->execute([':pic' => $profilePic, ':id' => $id]);
            $_SESSION['user_profile_pic'] = $profilePic;
        }
        
        $_SESSION['user_nom']    = $nom;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_email']  = $email;
        $_SESSION['success']     = "Profil mis à jour avec succès !";
        header('Location: /projet_final/controllers/UserController.php?action=showProfile');
        exit;
    }

    public function showDashboard(): void {
        if (!isset($_SESSION['user_id'])) { header('Location: /projet_final/controllers/UserController.php?action=showLogin'); exit; }
        $user = $this->getById($_SESSION['user_id']);
        require_once __DIR__ . '/../views/frontoffice/dashboard.php';
    }

    public function logout(): void {
        session_destroy();
        header('Location: /projet_final/controllers/UserController.php?action=showLogin');
        exit;
    }
}

// ── ROUTEUR FRONT ─────────────────────────────────────────────────────────────
$controller     = new UserController();
$action         = $_GET['action'] ?? 'showLogin';
$allowedActions = [
    'showLogin','login',
    'showRegister','register',
    'showVerifyRegister','verifyRegisterCode','resendRegisterCode',
    'showForgotPassword','forgotPassword',
    'showVerifyCode','verifyResetCode','resendResetCode',
    'showResetPassword','resetPassword',
    'showProfile','updateProfile',
    'showDashboard','logout',
];
if (in_array($action, $allowedActions)) {
    $controller->$action();
} else {
    $controller->showLogin();
}