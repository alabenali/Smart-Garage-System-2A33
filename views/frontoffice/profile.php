<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel = new User();
$user = $userModel->getById($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage</h2></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Mon espace</a></li>
        <li><a href="profile.php" class="active">Mon profil</a></li>
    </ul>
    <a href="../../controllers/UserController.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
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
        <form id="profileForm" method="POST" action="../../controllers/UserController.php?action=updateProfile" novalidate>
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
            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour</a>
            </div>
        </form>
    </div>
</div>
<script src="../../public/js/validate-profile.js"></script>
</body>
</html>
