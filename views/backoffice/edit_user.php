<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel = new User();
$id   = (int)($_GET['id'] ?? 0);
$user = $userModel->getById($id);
if (!$user) {
    $_SESSION['errors'] = ["Utilisateur introuvable."];
    header('Location: users_list.php?action=listUsers');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Client - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<aside class="sidebar">
    <div class="logo"><h2><i class="fas fa-car" style="color:#00E5FF;"></i> Smart Garage</h2></div>
    <nav><ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="users_list.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="add_user.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error"><?php foreach($_SESSION['errors'] as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-edit"></i> Modifier le client</h2>
        <form id="editForm" method="POST" action="../../controllers/AdminController.php?action=editUser" novalidate>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <div class="row2">
                <div class="form-group">
                    <label>Nom</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i>
                        <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($user['nom']) ?>">
                    </div>
                    <span class="error-msg" id="nomError"></span>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i>
                        <input type="text" name="prenom" id="prenom" value="<?= htmlspecialchars($user['prenom']) ?>">
                    </div>
                    <span class="error-msg" id="prenomError"></span>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap"><i class="fas fa-envelope icon"></i>
                    <input type="text" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <span class="error-msg" id="emailError"></span>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <div class="input-wrap"><i class="fas fa-phone icon"></i>
                    <input type="text" name="telephone" id="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                </div>
                <span class="error-msg" id="telephoneError"></span>
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <div class="input-wrap"><i class="fas fa-map-marker-alt icon"></i>
                    <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <div class="input-wrap"><i class="fas fa-toggle-on icon"></i>
                    <select name="statut">
                        <option value="actif" <?= $user['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                        <option value="inactif" <?= $user['statut'] === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="users_list.php?action=listUsers" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
            </div>
        </form>
    </div>
</main>
<script src="../../public/js/validate-edit-user.js"></script>
</body>
</html>
