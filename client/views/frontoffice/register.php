<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $styleVersion = @filemtime(__DIR__ . '/../../../vehicule et rdv/views/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="/integration/vehicule%20et%20rdv/views/css/style.css?v=<?php echo $styleVersion; ?>">
    <link rel="stylesheet" href="/integration/client/views/frontoffice/auth_sg.css?v=<?php echo @filemtime(__DIR__ . '/auth_sg.css') ?: time(); ?>">
    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <!-- ✅ reCAPTCHA v3 : script invisible, aucune case à cocher -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>"></script>
    <?php endif; ?>
</head>
<body class="auth-body">
<div class="client-auth-shell">
<div class="auth-card">
    <div class="auth-logo">
        <i class="fas fa-user-plus" style="font-size:2rem;color:#00E5FF;"></i>
        <h2>Créer un compte</h2>
    </div>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error">
            <?php foreach($_SESSION['errors'] as $e): ?>
                <div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="/integration/client/controllers/UserController.php?action=register" novalidate enctype="multipart/form-data">
        <!-- ✅ reCAPTCHA v3 : champ caché rempli automatiquement par JS avant envoi -->
        <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-register">
        <?php endif; ?>

        <div class="row">
            <div class="form-group">
                <label>Nom</label>
                <div class="input-wrap"><i class="fas fa-user"></i>
                    <input type="text" name="nom" id="nom" placeholder="Ben Ali" value="<?= htmlspecialchars($_SESSION['old']['nom'] ?? '') ?>">
                </div>
                <span class="error-msg" id="nomError"></span>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <div class="input-wrap"><i class="fas fa-user"></i>
                    <input type="text" name="prenom" id="prenom" placeholder="Ahmed" value="<?= htmlspecialchars($_SESSION['old']['prenom'] ?? '') ?>">
                </div>
                <span class="error-msg" id="prenomError"></span>
            </div>
        </div>
        <div class="form-group">
            <label>Email</label>
            <div class="input-wrap"><i class="fas fa-envelope"></i>
                <input type="text" name="email" id="email" placeholder="votre@email.com" value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>">
            </div>
            <span class="error-msg" id="emailError"></span>
        </div>
        <div class="form-group">
            <label>Téléphone</label>
            <div class="input-wrap"><i class="fas fa-phone"></i>
                <input type="text" name="telephone" id="telephone" placeholder="+216 XX XXX XXX" value="<?= htmlspecialchars($_SESSION['old']['telephone'] ?? '') ?>">
            </div>
            <span class="error-msg" id="telephoneError"></span>
        </div>
        <div class="form-group">
            <label>Adresse</label>
            <div class="input-wrap"><i class="fas fa-map-marker-alt"></i>
                <input type="text" name="adresse" id="adresse" placeholder="Tunis, Tunisie" value="<?= htmlspecialchars($_SESSION['old']['adresse'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <div class="input-wrap"><i class="fas fa-lock"></i>
                <input type="password" name="mot_de_passe" id="password" placeholder="Min. 6 caractères">
            </div>
            <span class="error-msg" id="passwordError"></span>
        </div>
        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <div class="input-wrap"><i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••">
            </div>
            <span class="error-msg" id="confirmError"></span>
        </div>
        <div class="form-group">
            <label>Photo de profil <span style="color:#ff4d4d;">*</span></label>
            <div class="input-wrap" style="flex-direction:column; align-items:flex-start; padding:10px 14px; gap:8px;">
                <label for="profile_picture" id="photoLabel" style="cursor:pointer; display:flex; align-items:center; gap:8px; color:#00E5FF; font-weight:500;">
                    <i class="fas fa-camera"></i> Choisir une photo
                </label>
                <input type="file" name="profile_picture" id="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                <div id="photoPreviewWrap" style="display:none; margin-top:6px;">
                    <img id="photoPreview" src="#" alt="Aperçu" style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:2px solid #00E5FF;">
                </div>
            </div>
            <span class="error-msg" id="photoError"></span>
        </div>

        <!-- ✅ Plus de bloc g-recaptcha visible ici — tout est invisible avec v3 -->

        <button type="submit" class="btn-primary full" id="registerBtn">
            <i class="fas fa-user-plus"></i> Créer mon compte
        </button>
    </form>
    <?php unset($_SESSION['old']); ?>
    <div class="links"><p>Déjà un compte ? <a href="/integration/client/views/frontoffice/login.php">Se connecter</a></p></div>
</div>
 </div>
<script src="/integration/client/views/frontoffice/js/validate-register.js"></script>
<script>
// Preview photo de profil
document.getElementById('profile_picture').addEventListener('change', function() {
    const file = this.files[0];
    const label = document.getElementById('photoLabel');
    const wrap  = document.getElementById('photoPreviewWrap');
    const prev  = document.getElementById('photoPreview');
    if (file) {
        label.innerHTML = '<i class="fas fa-check-circle" style="color:#00ff88;"></i> ' + file.name;
        const reader = new FileReader();
        reader.onload = e => { prev.src = e.target.result; wrap.style.display = 'block'; };
        reader.readAsDataURL(file);
    }
});
</script>
<?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
<script>
// ✅ reCAPTCHA v3 : génère un token invisible au moment du submit
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    grecaptcha.ready(function() {
        grecaptcha.execute('<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>', {action: 'register'})
            .then(function(token) {
                document.getElementById('g-recaptcha-response-register').value = token;
                form.submit();
            });
    });
});
</script>
<?php endif; ?>
</body>
</html>