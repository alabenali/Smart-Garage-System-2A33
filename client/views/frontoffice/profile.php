<?php
// views/frontoffice/profile.php

require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: /integration/client/controllers/UserController.php?action=showLogin'); 
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
    $avatarPath = '/integration/client/' . $profilePic;
    // Chemin serveur pour vérification d'existence
    $serverPath = __DIR__ . '/../../' . $profilePic;
    if (!file_exists($serverPath)) {
        $avatarPath = null; // Fichier absent → afficher l'initiale
    }
}
?>

<?php $pageTitle = 'Mon profil'; $action = 'clientProfile'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
    .client-avatar-wrap { display:flex; align-items:center; gap:1rem; margin-bottom:1.25rem; }
    .client-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--accent);
        background: #fff;
    }
    .client-avatar-placeholder {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
        background: var(--gradient-2);
        border: 3px solid var(--accent);
    }
</style>

<?php if (!empty($_SESSION['errors'])): ?>
    <div class="sg-alert sg-alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <?php foreach ($_SESSION['errors'] as $e): ?>
                <div><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php unset($_SESSION['errors']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success']); ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<h1 class="page-title">Mon profil</h1>
<p class="page-subtitle">Mettez à jour vos informations personnelles.</p>

<div class="sg-form-wrap" style="max-width: 820px;">
    <div class="client-avatar-wrap">
        <?php if ($avatarPath): ?>
            <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Photo de profil" class="client-avatar" id="previewImg">
        <?php else: ?>
            <div class="client-avatar-placeholder" id="avatarPlaceholder"><?php echo strtoupper(substr((string) $user['prenom'], 0, 1)); ?></div>
        <?php endif; ?>
        <div>
            <div style="font-weight:800;color:var(--accent-secondary);"><?php echo htmlspecialchars((string) $user['prenom'] . ' ' . (string) $user['nom']); ?></div>
            <div style="color:var(--text-secondary);font-size:0.9rem;"><?php echo htmlspecialchars((string) $user['email']); ?></div>
        </div>
    </div>

    <form id="profileForm" method="POST" action="/integration/client/controllers/UserController.php?action=updateProfile" novalidate enctype="multipart/form-data">
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars((string) $user['nom']); ?>">
                <div class="invalid-feedback" id="nomError" style="display:none;"></div>
            </div>

            <div class="sg-form-group">
                <label for="prenom">Prénom</label>
                <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars((string) $user['prenom']); ?>">
                <div class="invalid-feedback" id="prenomError" style="display:none;"></div>
            </div>

            <div class="sg-form-group full-width">
                <label for="email">Email</label>
                <input type="text" name="email" id="email" value="<?php echo htmlspecialchars((string) $user['email']); ?>">
                <div class="invalid-feedback" id="emailError" style="display:none;"></div>
            </div>

            <div class="sg-form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars((string) ($user['telephone'] ?? '')); ?>">
                <div class="invalid-feedback" id="telephoneError" style="display:none;"></div>
            </div>

            <div class="sg-form-group">
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" id="adresse" value="<?php echo htmlspecialchars((string) ($user['adresse'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group full-width">
                <label for="profile_picture">Photo de profil</label>
                <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp">
                <div style="color:var(--text-muted);font-size:0.85rem;">Formats: JPG, PNG, GIF, WebP. Max: 2 Mo</div>
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-save"></i> Enregistrer</button>
                <a href="/integration/client/controllers/UserController.php?action=showDashboard" class="btn-sg btn-sg-outline"><i class="bi bi-arrow-left"></i> Retour</a>
            </div>
        </div>
    </form>
</div>

<script src="/integration/client/views/frontoffice/js/validate-profile.js"></script>
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
                img.className = 'client-avatar';
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

<?php require __DIR__ . '/layout_footer.php'; ?>