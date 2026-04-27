<?php
// views/backoffice/admin_profile.php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /projet_final/controllers/AdminController.php?action=showLogin');
    exit;
}
// $admin est injecté par AdminController->showAdminProfile()
if (!isset($admin)) {
    header('Location: /projet_final/controllers/AdminController.php?action=showDashboard');
    exit;
}

$pic = $admin['profile_picture'] ?? null;
$picUrl = null;
if ($pic) {
    $sp = __DIR__ . '/../../' . $pic;
    if (file_exists($sp)) $picUrl = '/projet_final/' . $pic;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Admin Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
    <style>
        .avatar-section { text-align:center; margin-bottom:25px; }
        .avatar-circle {
            width:110px; height:110px; border-radius:50%; object-fit:cover;
            border:4px solid #00E5FF; margin:0 auto 12px; display:block;
        }
        .avatar-placeholder {
            width:110px; height:110px; border-radius:50%;
            background:linear-gradient(135deg,#667eea,#764ba2);
            display:flex; align-items:center; justify-content:center;
            font-size:2.8rem; color:white; margin:0 auto 12px;
            border:4px solid #00E5FF;
        }
        .upload-hint { color:#888; font-size:0.85rem; }
        .file-upload-btn {
            display:inline-block; padding:8px 18px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white; border-radius:6px; cursor:pointer;
            font-size:0.9rem; transition:opacity .2s;
        }
        .file-upload-btn:hover { opacity:.85; }
        .file-upload-btn input[type=file] { display:none; }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
    <div style="text-align:center;padding:15px 0;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:10px;">
        <?php if ($picUrl): ?>
            <img src="<?= htmlspecialchars($picUrl) ?>" alt="Admin" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #00E5FF;margin-bottom:8px;">
        <?php else: ?>
            <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:white;margin:0 auto 8px;border:3px solid #00E5FF;">
                <?= strtoupper(substr($admin['prenom'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div style="color:#ccc;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['admin_nom']) ?></div>
    </div>
    <nav><ul>
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showStatistics"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile" class="active"><i class="fas fa-user-cog"></i> Mon profil</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error"><?php foreach($_SESSION['errors'] as $e): ?><div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h1><i class="fas fa-user-cog" style="color:#00E5FF;"></i> Mon Profil</h1>
    </div>

    <div class="card" style="max-width:600px;">

        <!-- Photo de profil actuelle -->
        <div class="avatar-section">
            <?php if ($picUrl): ?>
                <img src="<?= htmlspecialchars($picUrl) ?>" alt="Photo de profil" class="avatar-circle" id="adminPreviewImg">
            <?php else: ?>
                <div class="avatar-placeholder" id="adminPreviewImg"><?= strtoupper(substr($admin['prenom'], 0, 1)) ?></div>
            <?php endif; ?>
            <p class="upload-hint">Cliquez sur "Choisir une photo" pour modifier votre photo de profil</p>
        </div>

        <form method="POST" action="/projet_final/controllers/AdminController.php?action=updateAdminProfile" novalidate enctype="multipart/form-data">

            <div class="row2">
                <div class="form-group">
                    <label>Nom</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i>
                        <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($admin['nom']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i>
                        <input type="text" name="prenom" id="prenom" value="<?= htmlspecialchars($admin['prenom']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap"><i class="fas fa-envelope icon"></i>
                    <input type="text" name="email" value="<?= htmlspecialchars($admin['email']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Téléphone</label>
                <div class="input-wrap"><i class="fas fa-phone icon"></i>
                    <input type="text" name="telephone" value="<?= htmlspecialchars($admin['telephone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Adresse</label>
                <div class="input-wrap"><i class="fas fa-map-marker-alt icon"></i>
                    <input type="text" name="adresse" value="<?= htmlspecialchars($admin['adresse'] ?? '') ?>">
                </div>
            </div>

            <!-- Upload photo -->
            <div class="form-group">
                <label><i class="fas fa-camera"></i> Photo de profil</label>
                <label class="file-upload-btn">
                    <i class="fas fa-upload"></i> Choisir une photo
                    <input type="file" name="profile_picture" id="adminProfilePic" accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
                <span id="fileLabel" style="margin-left:10px;color:#888;font-size:0.85rem;">Aucun fichier sélectionné</span>
                <br><small class="upload-hint">JPG, PNG, GIF, WebP — max 2 Mo</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="/projet_final/controllers/AdminController.php?action=showDashboard" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('adminProfilePic').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        var file = this.files[0];
        var validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Format non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
            this.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La taille ne doit pas dépasser 2 Mo.');
            this.value = '';
            return;
        }
        document.getElementById('fileLabel').textContent = file.name;
        // Prévisualisation
        var reader = new FileReader();
        reader.onload = function(ev) {
            var el = document.getElementById('adminPreviewImg');
            if (el.tagName === 'IMG') {
                el.src = ev.target.result;
            } else {
                var img = document.createElement('img');
                img.id = 'adminPreviewImg';
                img.alt = 'Aperçu';
                img.className = 'avatar-circle';
                img.src = ev.target.result;
                el.parentNode.replaceChild(img, el);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
