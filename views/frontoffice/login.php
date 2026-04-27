<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script> // chargement de l API Recaptcha dans la partie Views
    <?php endif; ?>
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="auth-logo">
        <i class="fas fa-car" style="font-size:2.5rem;color:#00E5FF;"></i>
        <h2>Smart Garage</h2>
        <p>Espace Client</p>
    </div>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error">
            <?php foreach($_SESSION['errors'] as $e): ?>
                <div><i class="fas fa-exclamation-circle"></i> <?= $e /* HTML intentionnel pour <strong> */ ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
        <?php if (!empty($_SESSION['resend_email'])): ?>
        <form method="POST" action="/projet_final/controllers/UserController.php?action=resendVerification" style="text-align:center;margin-bottom:12px;">
            <input type="hidden" name="resend_email" value="<?= htmlspecialchars($_SESSION['resend_email']) ?>">
            <?php unset($_SESSION['resend_email']); ?>
            <button type="submit" style="background:none;border:none;color:#00E5FF;cursor:pointer;text-decoration:underline;font-size:0.9rem;">
                <i class="fas fa-paper-plane"></i> Renvoyer l'email de confirmation
            </button>
        </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="/projet_final/controllers/UserController.php?action=login" novalidate autocomplete="off">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="text" name="email" id="email" placeholder="votre@email.com" autocomplete="off">
            </div>
            <span class="error-msg" id="emailError"></span>
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="mot_de_passe" id="password" placeholder="••••••••" autocomplete="new-password">
            </div>
            <span class="error-msg" id="passwordError"></span>
        </div>

        <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
        <div class="form-group" style="display:flex;justify-content:center;margin-bottom:16px;">
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars(RECAPTCHA_SITE_KEY) ?>"></div>
        </div>
        <span class="error-msg" id="captchaError" style="display:block;text-align:center;margin-bottom:8px;"></span>
        <?php endif; ?>

        <button type="submit" class="btn-primary full"><i class="fas fa-sign-in-alt"></i> Se connecter</button>
    </form>

    <div class="links">
        <p><a href="/projet_final/controllers/UserController.php?action=showForgotPassword"><i class="fas fa-key"></i> Mot de passe oublié ?</a></p>
        <p>Pas de compte ? <a href="/projet_final/views/frontoffice/register.php">S'inscrire</a></p>
    </div>
</div>
<script src="/projet_final/views/frontoffice/js/validate-login.js"></script>
<?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    <?php if (RECAPTCHA_ENABLED): ?>
    var recaptchaResponse = document.querySelector('.g-recaptcha-response');
    if (recaptchaResponse && !recaptchaResponse.value) {
        document.getElementById('captchaError').textContent = 'Veuillez valider le CAPTCHA.';
        document.getElementById('captchaError').style.display = 'block';
        e.preventDefault();
        return false;
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>
</body>
</html>