<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-logo">
        <i class="fas fa-shield-alt" style="font-size:2.5rem;color:#00E5FF;"></i>
        <h2>Administration</h2>
        <p>Smart Garage System</p>
        <span class="admin-badge-sm"><i class="fas fa-lock"></i> Accès sécurisé</span>
    </div>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error">
            <?php foreach($_SESSION['errors'] as $e): ?>
                <div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <form id="adminLoginForm" method="POST" action="../../controllers/AdminController.php?action=login" novalidate>
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Administrateur</label>
            <div class="input-wrap">
                <i class="fas fa-envelope icon"></i>
                <input type="text" name="email" id="email" placeholder="admin@garage.com">
            </div>
            <span class="error-msg" id="emailError"></span>
        </div>
        <div class="form-group">
            <label><i class="fas fa-key"></i> Mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-key icon"></i>
                <input type="password" name="mot_de_passe" id="password" placeholder="••••••••">
            </div>
            <span class="error-msg" id="passwordError"></span>
        </div>
        <button type="submit" class="btn-primary full"><i class="fas fa-sign-in-alt"></i> Accéder au panneau admin</button>
    </form>
    <div class="back-link"><a href="../frontoffice/login.php"><i class="fas fa-arrow-left"></i> Retour à l'espace client</a></div>
</div>
<script src="../../public/js/validate-login.js"></script>
</body>
</html>
