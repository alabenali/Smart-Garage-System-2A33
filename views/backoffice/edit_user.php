<?php
<<<<<<< HEAD
// views/backoffice/edit_user.php
// Cette vue est toujours appelée via AdminController->showEditUser()
// qui fournit la variable $user — ne pas instancier User directement ici

require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /projet_final/controllers/AdminController.php?action=showLogin');
    exit;
}
// $user est injecté par AdminController->showEditUser()
if (!isset($user)) {
    header('Location: /projet_final/controllers/AdminController.php?action=listUsers');
=======
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel = new User();
$id   = (int)($_GET['id'] ?? 0);
$user = $userModel->getById($id);
if (!$user) {
    $_SESSION['errors'] = ["Utilisateur introuvable."];
    header('Location: users_list.php?action=listUsers');
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Client - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
=======
    <link rel="stylesheet" href="style.css">
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
</head>
<body>
<aside class="sidebar">
    <div class="logo"><h2><i class="fas fa-car" style="color:#00E5FF;"></i> Smart Garage</h2></div>
    <nav><ul>
<<<<<<< HEAD
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showStatistics"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
=======
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="users_list.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="add_user.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error"><?php foreach($_SESSION['errors'] as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-edit"></i> Modifier le client</h2>
<<<<<<< HEAD
        
        <!-- Photo de profil actuelle -->
        <?php
        $pic = $user['profile_picture'] ?? null;
        $picUrl = null;
        if ($pic) {
            $serverPath = __DIR__ . '/../../' . $pic;
            if (file_exists($serverPath)) {
                $picUrl = '/projet_final/' . $pic;
            }
        }
        ?>
        <div style="text-align:center;margin-bottom:20px;">
            <?php if ($picUrl): ?>
                <img src="<?= htmlspecialchars($picUrl) ?>" id="previewImgAdmin" alt="Photo" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #00E5FF;">
            <?php else: ?>
                <div id="previewImgAdmin" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:white;margin:0 auto;border:3px solid #00E5FF;">
                    <?= strtoupper(substr($user['prenom'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <form id="editForm" method="POST" action="../../controllers/AdminController.php?action=editUser" novalidate enctype="multipart/form-data">
=======
        <form id="editForm" method="POST" action="../../controllers/AdminController.php?action=editUser" novalidate>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
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
<<<<<<< HEAD
            <div class="form-group">
                <label><i class="fas fa-camera"></i> Photo de profil</label>
                <input type="file" name="profile_picture" id="profile_picture_edit" accept="image/jpeg,image/png,image/gif,image/webp" style="padding:8px;">
                <small style="color:#888;">JPG, PNG, GIF, WebP — max 2 Mo</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="/projet_final/controllers/AdminController.php?action=listUsers" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
=======
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="users_list.php?action=listUsers" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
            </div>
        </form>
    </div>
</main>
<<<<<<< HEAD
<script src="/projet_final/views/backoffice/js/validate-edit-user.js"></script>
<script>
document.getElementById('profile_picture_edit').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        var file = this.files[0];
        if (file.size > 2 * 1024 * 1024) { alert('Max 2 Mo.'); this.value=''; return; }
        var reader = new FileReader();
        reader.onload = function(ev) {
            var el = document.getElementById('previewImgAdmin');
            if (el.tagName === 'IMG') {
                el.src = ev.target.result;
            } else {
                var img = document.createElement('img');
                img.id = 'previewImgAdmin';
                img.src = ev.target.result;
                img.style = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #00E5FF;';
                el.parentNode.replaceChild(img, el);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
=======
<script src="../../public/js/validate-edit-user.js"></script>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
</body>
</html>
