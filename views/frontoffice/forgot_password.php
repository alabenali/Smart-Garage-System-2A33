<?php require_once __DIR__ . '/../../config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="auth-logo">
        <i class="fas fa-car" style="font-size:2.5rem;color:#00E5FF;"></i>
        <h2>Smart Garage</h2>
        <p>Réinitialisation du mot de passe</p>
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

    <p style="color:#6B6B6B;font-size:0.9rem;margin-bottom:1.2rem;text-align:center;">
        Entrez votre adresse email pour recevoir un code de réinitialisation.
    </p>

    <form method="POST" action="/projet_final/controllers/UserController.php?action=forgotPassword">
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Adresse email</label>
            <div class="input-wrap">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="votre@email.com" required>
            </div>
        </div>
        <button type="submit" class="btn-primary full">
            <i class="fas fa-paper-plane"></i> Envoyer le code
        </button>
    </form>

    <div class="links">
        <p><a href="/projet_final/views/frontoffice/login.php"><i class="fas fa-arrow-left"></i> Retour à la connexion</a></p>
    </div>
</div>
</body>
</html>