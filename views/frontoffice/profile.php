<?php
// views/frontoffice/profile.php

require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: /projet_final/controllers/UserController.php?action=showLogin'); 
    exit; 
}

// Requête SQL directe
$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM user WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

// Get profile picture path
$profilePic = $user['profile_picture'] ?? null;
$avatarPath = null;
if ($profilePic) {
    // Chemin web absolu pour affichage
    $avatarPath = '/projet_final/' . $profilePic;
    // Chemin serveur pour vérification d'existence
    $serverPath = __DIR__ . '/../../' . $profilePic;
    if (!file_exists($serverPath)) {
        $avatarPath = null; // Fichier absent → afficher l'initiale
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
    <style>
        .avatar-section { text-align: center; margin-bottom: 20px; }
        .current-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #00E5FF;
            margin: 0 auto 15px;
            display: block;
        }
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 3rem;
            color: white;
            border: 4px solid #00E5FF;
        }
        .upload-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .upload-section h4 { margin-top: 0; color: #333; }
        .file-input-wrapper { position: relative; overflow: hidden; display: inline-block; }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0; top: 0; opacity: 0;
            width: 100%; height: 100%;
            cursor: pointer;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage</h2></div>
    <ul class="nav-links">
        <li><a href="/projet_final/controllers/UserController.php?action=showDashboard">Mon espace</a></li>
        <li><a href="/projet_final/controllers/UserController.php?action=showProfile" class="active">Mon profil</a></li>
    </ul>
    <a href="/projet_final/controllers/UserController.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</nav>

<div class="container" style="max-width:700px;">
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error"><?php foreach($_SESSION['errors'] as $e): ?><div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-edit"></i> Modifier mon profil</h2>
        
        <!-- Profile Picture Section -->
        <div class="avatar-section">
            <?php if ($avatarPath): ?>
                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Photo de profil" class="current-avatar" id="previewImg">
            <?php else: ?>
                <div class="avatar-placeholder" id="avatarPlaceholder"><?= strtoupper(substr($user['prenom'], 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        
        <form id="profileForm" method="POST" action="/projet_final/controllers/UserController.php?action=updateProfile" novalidate enctype="multipart/form-data">
            <div class="row2">
                <div class="form-group">
                    <label>Nom</label>
                    <div class="input-wrap"><i class="fas fa-user"></i>
                        <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($user['nom']) ?>">
                    </div>
                    <span class="error-msg" id="nomError"></span>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <div class="input-wrap"><i class="fas fa-user"></i>
                        <input type="text" name="prenom" id="prenom" value="<?= htmlspecialchars($user['prenom']) ?>">
                    </div>
                    <span class="error-msg" id="prenomError"></span>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap"><i class="fas fa-envelope"></i>
                    <input type="text" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <span class="error-msg" id="emailError"></span>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <div class="input-wrap"><i class="fas fa-phone"></i>
                    <input type="text" name="telephone" id="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                </div>
                <span class="error-msg" id="telephoneError"></span>
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <div class="input-wrap"><i class="fas fa-map-marker-alt"></i>
                    <input type="text" name="adresse" id="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                </div>
            </div>
            
            <!-- Profile Picture Upload -->
            <div class="upload-section">
                <h4><i class="fas fa-camera"></i> Photo de profil</h4>
                <div class="file-input-wrapper">
                    <button type="button" class="btn-secondary"><i class="fas fa-upload"></i> Télécharger une photo</button>
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <small style="color: #666;">Formats: JPG, PNG, GIF, WebP. Max: 2 Mo</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="/projet_final/controllers/UserController.php?action=showDashboard" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </form>

    </div>
</div>
<script src="/projet_final/views/frontoffice/js/validate-profile.js"></script>
<script>
document.getElementById('profile_picture').addEventListener('change', function(e) {
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
        // Prévisualisation instantanée
        var reader = new FileReader();
        reader.onload = function(ev) {
            var placeholder = document.getElementById('avatarPlaceholder');
            var preview = document.getElementById('previewImg');
            if (placeholder) {
                // Remplacer le placeholder par une image
                var img = document.createElement('img');
                img.id = 'previewImg';
                img.alt = 'Aperçu';
                img.className = 'current-avatar';
                img.src = ev.target.result;
                placeholder.parentNode.replaceChild(img, placeholder);
            } else if (preview) {
                preview.src = ev.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>