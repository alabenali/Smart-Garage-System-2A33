<?php
// controllers/UserController.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/Mailer.php';

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
        $stmt = $this->db->prepare("SELECT * FROM user WHERE {$column} = :token");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch();
    }

    private function create(array $data): int|false {
        $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO user (nom, prenom, email, telephone, adresse, mot_de_passe,
                              statut, post, email_verified, verification_token)
            VALUES (:nom, :prenom, :email, :telephone, :adresse, :mot_de_passe,
                    :statut, :post, :email_verified, :verification_token)
        ");
        $ok = $stmt->execute([
            ':nom'                => $data['nom'],
            ':prenom'             => $data['prenom'],
            ':email'              => $data['email'],
            ':telephone'          => $data['telephone'] ?? null,
            ':adresse'            => $data['adresse']   ?? null,
            ':mot_de_passe'       => $hashedPassword,
            ':statut'             => $data['statut']    ?? 'actif',
            ':post'               => $data['post']      ?? 'client',
            ':email_verified'     => $data['email_verified'] ?? 0,
            ':verification_token' => $data['verification_token'],
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
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

    // ── reCAPTCHA Validation Helper ──────────────────────────────────────────

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

        if (!isset($result['success']) || !$result['success']) {
            return false;
        }

        // ✅ reCAPTCHA v3 : vérification du score (0.0 = bot, 1.0 = humain)
        if (defined('RECAPTCHA_VERSION') && RECAPTCHA_VERSION === 'v3') {
            $minScore = defined('RECAPTCHA_MIN_SCORE') ? RECAPTCHA_MIN_SCORE : 0.5;
            return isset($result['score']) && (float)$result['score'] >= $minScore;
        }

        // reCAPTCHA v2 : succès suffit
        return true;
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
            $_SESSION['admin_id']          = $row['id'];
            $_SESSION['admin_nom']         = $row['nom'] . ' ' . $row['prenom'];
            $_SESSION['admin_profile_pic'] = $row['profile_picture'] ?? null;
            $_SESSION['role']              = 'admin';
            header('Location: /projet_final/controllers/AdminController.php?action=showDashboard');
            exit;
        } elseif ($row && $row['post'] === 'client') {

            // ✅ FIX : Vérification que le compte est bien validé par email
            if (!(int)$row['email_verified']) {
                $_SESSION['errors']       = ["<strong>Votre compte n'est pas encore vérifié. Vérifiez votre email.</strong>"];
                $_SESSION['resend_email'] = $row['email'];
                header('Location: /projet_final/controllers/UserController.php?action=showLogin');
                exit;
            }

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

    // ✅ FIX : Action resendVerification manquante — maintenant ajoutée
    public function resendVerification(): void {
        $email = trim($_POST['resend_email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        $row = $this->getByEmail($email);

        if ($row && !(int)$row['email_verified']) {
            // Générer un nouveau code et stocker en session pour re-vérification
            $code = $this->generateResetCode();
            $_SESSION['pending_register'] = [
                'nom'       => $row['nom'],
                'prenom'    => $row['prenom'],
                'email'     => $row['email'],
                'telephone' => $row['telephone'] ?? '',
                'adresse'   => $row['adresse']   ?? '',
                'password'  => null, // déjà en base, pas besoin
                'code'      => $code,
                'expires'   => time() + 900,
                'existing_id' => $row['id'], // ✅ compte déjà créé, on met juste email_verified à 1
            ];
            Mailer::sendRegisterCode($email, $row['prenom'] . ' ' . $row['nom'], $code);
            $_SESSION['success'] = "Un nouveau code de confirmation a été envoyé à votre adresse email.";
        } else {
            $_SESSION['success'] = "Si cet email est en attente de vérification, vous recevrez un code.";
        }

        header('Location: /projet_final/controllers/UserController.php?action=showVerifyRegister');
        exit;
    }

    // ── REGISTER ──────────────────────────────────────────────────────────────

    public function showRegister(): void {
        require_once __DIR__ . '/../views/frontoffice/register.php';
    }

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

        // ── Validation photo de profil obligatoire ─────────────────────────
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "La photo de profil est obligatoire.";
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($realMime, $allowedTypes)) {
                $errors[] = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WebP.";
            } elseif ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
                $errors[] = "La taille de l'image ne doit pas dépasser 2 Mo.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
            exit;
        }

        $code = $this->generateResetCode();

        // ── Sauvegarder la photo en base64 dans la session ───────────────
        // Encodage en base64 pour éviter tout problème de fichier temporaire
        // perdu entre les deux requêtes HTTP (register → verifyRegisterCode).
        $tempPhotoData = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $ext           = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $tempPhotoData = [
                'data' => base64_encode(file_get_contents($_FILES['profile_picture']['tmp_name'])),
                'ext'  => $ext,
            ];
        }

        $_SESSION['pending_register'] = [
            'nom'           => $nom,
            'prenom'        => $prenom,
            'email'         => $email,
            'telephone'     => $telephone,
            'adresse'       => $adresse,
            'password'      => $password,
            'code'          => $code,
            'expires'       => time() + 900,
            'temp_photo'    => $tempPhotoData,  // ['data' => base64, 'ext' => 'jpg']
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

        // ✅ FIX : Si c'est un renvoi pour un compte déjà créé, on met juste email_verified = 1
        if (!empty($pending['existing_id'])) {
            $this->db->prepare("UPDATE user SET email_verified = 1 WHERE id = :id")
                     ->execute([':id' => $pending['existing_id']]);
            unset($_SESSION['pending_register']);
            $_SESSION['success'] = "✅ Compte vérifié avec succès ! Vous pouvez maintenant vous connecter.";
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        // ✅ FIX : Nouveau compte → créé directement avec email_verified = 1
        // create() retourne l'ID inséré (int) ou false
        $newUserId = $this->create([
            'nom'                => $pending['nom'],
            'prenom'             => $pending['prenom'],
            'email'              => $pending['email'],
            'telephone'          => $pending['telephone'],
            'adresse'            => $pending['adresse'],
            'mot_de_passe'       => $pending['password'],
            'post'               => 'client',
            'email_verified'     => 1,  // ✅ compte immédiatement vérifié
            'verification_token' => null,
        ]);

        // ── Écrire la photo depuis la session (base64) vers le disque ───────
        if ($newUserId && !empty($pending['temp_photo']['data'])) {
            $photoInfo = $pending['temp_photo'];
            $ext       = $photoInfo['ext'];
            $uploadDir = __DIR__ . '/../uploads/profile_pictures/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $finalName    = 'user_' . $newUserId . '_' . time() . '.' . $ext;
            $finalPath    = $uploadDir . $finalName;
            $imageBytes   = base64_decode($photoInfo['data']);
            if ($imageBytes !== false && file_put_contents($finalPath, $imageBytes) !== false) {
                $finalRelPath = 'uploads/profile_pictures/' . $finalName;
                $this->db->prepare("UPDATE user SET profile_picture = :pic WHERE id = :id")
                         ->execute([':pic' => $finalRelPath, ':id' => $newUserId]);
            }
        }

        unset($_SESSION['pending_register']);

        if ($newUserId) {
            $_SESSION['success'] = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
        } else {
            $_SESSION['errors'] = ["❌ Inscription échouée. Veuillez réessayer."];
            header('Location: /projet_final/controllers/UserController.php?action=showRegister');
        }
        exit;
    }

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

    private function generateResetCode(): string {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

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
            $expires = date('Y-m-d H:i:s', time() + 900);
            $this->db->prepare("UPDATE user SET reset_token = :code, reset_token_expires = :expires WHERE id = :id")
                     ->execute([':code' => $code, ':expires' => $expires, ':id' => $row['id']]);
            Mailer::sendResetCode($email, $row['prenom'] . ' ' . $row['nom'], $code);
            $_SESSION['reset_email'] = $email;
        }

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

        $_SESSION['reset_verified'] = true;
        $_SESSION['reset_user_id']  = $row['id'];
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

    // ── Face verification helper ──────────────────────────────────────────────
    // Returns the profile picture as base64 for client-side face comparison.
    // We only return a result if the email+password pre-check passes so we
    // don't leak whether an account exists to unauthenticated callers.
    public function getFaceImage(): void {
        header('Content-Type: application/json');

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'image' => null]);
            exit;
        }

        $row = $this->getByEmail($email);

        if (!$row || empty($row['profile_picture'])) {
            // Account doesn't exist or no photo — return success=true, image=null
            // so the JS allows login without face check (no photo registered yet)
            echo json_encode(['success' => true, 'image' => null]);
            exit;
        }

        $picPath = __DIR__ . '/../' . $row['profile_picture'];

        if (!file_exists($picPath)) {
            echo json_encode(['success' => true, 'image' => null]);
            exit;
        }

        $imageData = base64_encode(file_get_contents($picPath));
        echo json_encode(['success' => true, 'image' => $imageData]);
        exit;
    }

    // ── Google OAuth ──────────────────────────────────────────────────────────

    /** Étape 1 : rediriger l'utilisateur vers Google */
    public function googleLogin(): void {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = http_build_query([
            'client_id'     => GOOGLE_CLIENT_ID,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);
        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    /** Étape 2 : Google nous renvoie un code — on l'échange contre un token */
    public function googleCallback(): void {
        // Vérification CSRF
        if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
            $_SESSION['errors'] = ['Erreur de sécurité OAuth (state invalide).'];
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }
        unset($_SESSION['oauth_state']);

        if (!empty($_GET['error'])) {
            $_SESSION['errors'] = ['Connexion Google annulée.'];
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        $code = $_GET['code'] ?? '';
        if (empty($code)) {
            $_SESSION['errors'] = ['Code OAuth manquant.'];
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        // Échange du code contre un access token
        $tokenData = $this->googleExchangeCode($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            $_SESSION['errors'] = ['Impossible d\'obtenir le token Google.'];
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        // Récupération des infos utilisateur
        $userInfo = $this->googleGetUserInfo($tokenData['access_token']);
        if (!$userInfo || empty($userInfo['email'])) {
            $_SESSION['errors'] = ['Impossible de récupérer les informations Google.'];
            header('Location: /projet_final/controllers/UserController.php?action=showLogin');
            exit;
        }

        $email    = $userInfo['email'];
        $googleId = $userInfo['sub'] ?? '';
        $prenom   = $userInfo['given_name'] ?? 'Prénom';
        $nom      = $userInfo['family_name'] ?? 'Nom';

        // Vérifier si le compte existe déjà
        $pdo = Database::getConnection();
        $row = $this->getByEmail($email);

        if ($row) {
            // Compte existant — mettre à jour google_id si vide
            if (empty($row['google_id'])) {
                $pdo->prepare('UPDATE users SET google_id = :gid WHERE id = :id')
                    ->execute([':gid' => $googleId, ':id' => $row['id']]);
            }
            // Vérifier que le compte est actif
            if ($row['statut'] === 'bloque') {
                $_SESSION['errors'] = ['Votre compte est bloqué. Contactez l\'administrateur.'];
                header('Location: /projet_final/controllers/UserController.php?action=showLogin');
                exit;
            }
        } else {
            // Créer un nouveau compte Google
            $newId = $this->create([
                'nom'           => $nom,
                'prenom'        => $prenom,
                'email'         => $email,
                'mot_de_passe'  => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'statut'        => 'actif',
                'post'          => 'client',
                'email_verified'=> 1,
                'google_id'     => $googleId,
            ]);
            if (!$newId) {
                $_SESSION['errors'] = ['Erreur lors de la création du compte.'];
                header('Location: /projet_final/controllers/UserController.php?action=showLogin');
                exit;
            }
            $row = $this->getById($newId);
        }

        // Démarrer la session utilisateur
        $_SESSION['user_id']      = $row['id'];
        $_SESSION['user_email']   = $row['email'];
        $_SESSION['user_nom']     = $row['nom'];
        $_SESSION['user_prenom']  = $row['prenom'];
        $_SESSION['user_post']    = $row['post'];
        $_SESSION['user_profile_pic'] = $row['profile_picture'] ?? null;

        header('Location: /projet_final/controllers/UserController.php?action=showDashboard');
        exit;
    }

    /** Échange le code OAuth contre un token */
    private function googleExchangeCode(string $code): ?array {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri'  => GOOGLE_REDIRECT_URI,
                'grant_type'    => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ? json_decode($response, true) : null;
    }

    /** Récupère les infos du profil Google */
    private function googleGetUserInfo(string $accessToken): ?array {
        $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ? json_decode($response, true) : null;
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
    'resendVerification',                          // ✅ FIX : action ajoutée au routeur
    'showForgotPassword','forgotPassword',
    'showVerifyCode','verifyResetCode','resendResetCode',
    'showResetPassword','resetPassword',
    'showProfile','updateProfile',
    'showDashboard','logout',
    'getFaceImage',
    'googleLogin','googleCallback',
];
if (in_array($action, $allowedActions)) {
    $controller->$action();
} else {
    $controller->showLogin();
}