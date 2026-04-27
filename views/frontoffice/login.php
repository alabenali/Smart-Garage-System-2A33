<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
    <?php if (defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script> // chargement de l API Recaptcha dans la partie Views
    <?php endif; ?>
=======
    <link rel="stylesheet" href="style.css">
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
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
<<<<<<< HEAD
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
=======
                <div><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="../../controllers/UserController.php?action=login" novalidate>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
<<<<<<< HEAD
                <input type="text" name="email" id="email" placeholder="votre@email.com" autocomplete="off">
=======
                <input type="text" name="email" id="email" placeholder="votre@email.com">
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
            </div>
            <span class="error-msg" id="emailError"></span>
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
<<<<<<< HEAD
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

=======
                <input type="password" name="mot_de_passe" id="password" placeholder="••••••••">
            </div>
            <span class="error-msg" id="passwordError"></span>
        </div>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
        <button type="submit" class="btn-primary full"><i class="fas fa-sign-in-alt"></i> Se connecter</button>
    </form>

    <div class="links">
<<<<<<< HEAD
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
=======
        <p>Pas de compte ? <a href="register.php">S'inscrire</a></p>
        <p style="margin-top:0.5rem;"><a href="../../views/backoffice/admin_login.php">Accès Administrateur</a></p>
    </div>
</div>
<script src="../../public/js/validate-login.js"></script>
</body>
</html>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
