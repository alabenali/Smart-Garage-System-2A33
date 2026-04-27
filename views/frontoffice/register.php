<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="auth-body">
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

    <form id="registerForm" method="POST" action="/projet_final/controllers/UserController.php?action=register" novalidate enctype="multipart/form-data">
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
        
        <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
        <div class="form-group" style="display:flex;justify-content:center;margin-bottom:16px;">
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>"></div>
        </div>
        <span class="error-msg" id="captchaError" style="display:block;text-align:center;margin-bottom:8px;"></span>
        <?php endif; ?>
        
        <button type="submit" class="btn-primary full"><i class="fas fa-user-plus"></i> Créer mon compte</button>
    </form>
    <?php unset($_SESSION['old']); ?>
    <div class="links"><p>Déjà un compte ? <a href="/projet_final/views/frontoffice/login.php">Se connecter</a></p></div>
</div>
<script src="/projet_final/views/frontoffice/js/validate-register.js"></script>
<?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    var recaptchaResponse = document.querySelector('.g-recaptcha-response');
    if (recaptchaResponse && !recaptchaResponse.value) {
        document.getElementById('captchaError').textContent = 'Veuillez valider le CAPTCHA.';
        document.getElementById('captchaError').style.display = 'block';
        e.preventDefault();
        return false;
    }
});
</script>
<?php endif; ?>
</body>
</html>