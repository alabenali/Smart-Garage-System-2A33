<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                <div><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="../../controllers/UserController.php?action=login" novalidate>
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="text" name="email" id="email" placeholder="votre@email.com">
            </div>
            <span class="error-msg" id="emailError"></span>
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="mot_de_passe" id="password" placeholder="••••••••">
            </div>
            <span class="error-msg" id="passwordError"></span>
        </div>
        <button type="submit" class="btn-primary full"><i class="fas fa-sign-in-alt"></i> Se connecter</button>
    </form>

    <div class="links">
        <p>Pas de compte ? <a href="register.php">S'inscrire</a></p>
        <p style="margin-top:0.5rem;"><a href="../../views/backoffice/admin_login.php">Accès Administrateur</a></p>
    </div>
</div>
<script src="../../public/js/validate-login.js"></script>
</body>
</html>
