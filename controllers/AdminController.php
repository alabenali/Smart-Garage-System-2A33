<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    private function requireAdmin(): void {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: ../views/backoffice/admin_login.php');
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

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = "Email invalide.";
        if (empty($password) || strlen($password) < 6)
            $errors[] = "Mot de passe trop court (min. 6 caractères).";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
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
            exit;
        }
    }

    public function showDashboard(): void {
        $this->requireAdmin();
        $totalUsers  = $this->userModel->countAll();
        $activeUsers = $this->userModel->countActive();
        require_once __DIR__ . '/../views/backoffice/admin_dashboard.php';
    }

    public function listUsers(): void {
        $this->requireAdmin();
        $users = $this->userModel->getAll();
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

        if (empty($nom) || strlen($nom) < 2)          $errors[] = "Nom invalide (min. 2 caractères).";
        if (empty($prenom) || strlen($prenom) < 2)    $errors[] = "Prénom invalide.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (!empty($telephone) && !preg_match('/^\+?[0-9\s\-]{8,15}$/', $telephone)) $errors[] = "Téléphone invalide.";
        if (strlen($password) < 6)                    $errors[] = "Mot de passe trop court (min. 6 caractères).";
        if (!in_array($statut, ['actif', 'inactif']))  $errors[] = "Statut invalide.";
        if ($this->userModel->emailExists($email))     $errors[] = "Email déjà utilisé.";

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old']    = $_POST;
            header('Location: ../views/backoffice/add_user.php');
            exit;
        }

        $this->userModel->create([
            'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
            'telephone' => $telephone, 'adresse' => $adresse,
            'mot_de_passe' => $password, 'statut' => $statut, 'post' => 'client',
        ]);

        $_SESSION['success'] = "Utilisateur ajouté avec succès !";
        header('Location: ../views/backoffice/users_list.php?action=listUsers');
        exit;
    }

    public function showEditUser(): void {
        $this->requireAdmin();
        $id   = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->getById($id);
        if (!$user) {
            $_SESSION['errors'] = ["Utilisateur introuvable."];
            header('Location: ../views/backoffice/users_list.php?action=listUsers');
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
            'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
            'telephone' => $telephone, 'adresse' => $adresse, 'statut' => $statut,
        ]);

        $_SESSION['success'] = "Utilisateur modifié avec succès !";
        header('Location: ../views/backoffice/users_list.php?action=listUsers');
        exit;
    }

    public function deleteUser(): void {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->userModel->delete($id);
            $_SESSION['success'] = "Utilisateur supprimé.";
        }
        header('Location: ../views/backoffice/users_list.php?action=listUsers');
        exit;
    }

    public function logout(): void {
        session_destroy();
        header('Location: ../views/backoffice/admin_login.php');
        exit;
    }
}

// ── ROUTEUR BACK ──────────────────────────────────────────────────
$controller = new AdminController();
$action = $_GET['action'] ?? 'showLogin';

$allowedActions = ['showLogin','login','showDashboard','listUsers','showAddUser','addUser','showEditUser','editUser','deleteUser','logout'];
if (in_array($action, $allowedActions)) {
    $controller->$action();
} else {
    $controller->showLogin();
}
