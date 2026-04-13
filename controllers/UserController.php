<?php
// controllers/UserController.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

class UserController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function showLogin(): void {
        require_once __DIR__ . '/../views/frontoffice/login.php';
    }

    public function login(): void {
        $errors   = [];
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['mot_de_passe'] ?? '');

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
            header('Location: ../views/frontoffice/login.php');
            exit;
        }

        $user = $this->userModel->verifyPassword($email, $password);
        if ($user && $user['post'] === 'client') {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nom']    = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email']  = $user['email'];
            $_SESSION['role']        = 'client';
            header('Location: ../views/frontoffice/dashboard.php');
            exit;
        } else {
            $_SESSION['errors'] = ["Email ou mot de passe incorrect."];
            header('Location: ../views/frontoffice/login.php');
            exit;
        }
    }

    public function showRegister(): void {
        require_once __DIR__ . '/../views/frontoffice/register.php';
    }

    public function register(): void {
        $errors    = [];
        $nom       = trim($_POST['nom'] ?? '');
        $prenom    = trim($_POST['prenom'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse   = trim($_POST['adresse'] ?? '');
        $password  = trim($_POST['mot_de_passe'] ?? '');
        $confirm   = trim($_POST['confirm_password'] ?? '');

        if (empty($nom) || strlen($nom) < 2)
            $errors[] = "Le nom doit contenir au moins 2 caractères.";
        if (empty($prenom) || strlen($prenom) < 2)
            $errors[] = "Le prénom doit contenir au moins 2 caractères.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if (!empty($telephone) && !preg_match('/^\+?[0-9\s\-]{8,15}$/', $telephone))
            $errors[] = "Numéro de téléphone invalide.";
        if (strlen($password) < 6)
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        if ($password !== $confirm)
            $errors[] = "Les mots de passe ne correspondent pas.";
        if ($this->userModel->emailExists($email))
            $errors[] = "Cet email est déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            header('Location: ../views/frontoffice/register.php');
            exit;
        }

        $created = $this->userModel->create([
            'nom'          => $nom,
            'prenom'       => $prenom,
            'email'        => $email,
            'telephone'    => $telephone,
            'adresse'      => $adresse,
            'mot_de_passe' => $password,
            'post'         => 'client',
        ]);

        if ($created) {
            $_SESSION['success'] = "Compte créé avec succès ! Connectez-vous.";
            header('Location: ../views/frontoffice/login.php');
            exit;
        } else {
            $_SESSION['errors'] = ["Erreur lors de la création du compte."];
            header('Location: ../views/frontoffice/register.php');
            exit;
        }
    }

    public function showProfile(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../views/frontoffice/login.php');
            exit;
        }
        $user = $this->userModel->getById($_SESSION['user_id']);
        require_once __DIR__ . '/../views/frontoffice/profile.php';
    }

    public function updateProfile(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../views/frontoffice/login.php');
            exit;
        }

        $errors    = [];
        $id        = (int) $_SESSION['user_id'];
        $nom       = trim($_POST['nom'] ?? '');
        $prenom    = trim($_POST['prenom'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse   = trim($_POST['adresse'] ?? '');

        if (empty($nom) || strlen($nom) < 2)
            $errors[] = "Le nom est invalide.";
        if (empty($prenom) || strlen($prenom) < 2)
            $errors[] = "Le prénom est invalide.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if ($this->userModel->emailExists($email, $id))
            $errors[] = "Cet email est déjà utilisé par un autre compte.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header('Location: ../views/frontoffice/profile.php');
            exit;
        }

        $this->userModel->update($id, [
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => $email,
            'telephone' => $telephone,
            'adresse'   => $adresse,
            'statut'    => 'actif',
        ]);

        $_SESSION['user_nom']    = $nom;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_email']  = $email;
        $_SESSION['success']     = "Profil mis à jour avec succès !";
        header('Location: ../views/frontoffice/profile.php');
        exit;
    }

    public function showDashboard(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../views/frontoffice/login.php');
            exit;
        }
        $user = $this->userModel->getById($_SESSION['user_id']);
        require_once __DIR__ . '/../views/frontoffice/dashboard.php';
    }

    public function logout(): void {
        session_destroy();
        header('Location: ../views/frontoffice/login.php');
        exit;
    }
}

// ── ROUTEUR FRONT ─────────────────────────────────────────────────
$controller = new UserController();
$action = $_GET['action'] ?? 'showLogin';

$allowedActions = ['showLogin','login','showRegister','register','showProfile','updateProfile','logout','showDashboard'];
if (in_array($action, $allowedActions)) {
    $controller->$action();
} else {
    $controller->showLogin();
}
