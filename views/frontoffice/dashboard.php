<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$prenom = htmlspecialchars($_SESSION['user_prenom']);
$nom    = htmlspecialchars($_SESSION['user_nom']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage</h2></div>
    <ul class="nav-links">
        <li><a href="dashboard.php" class="active">Mon espace</a></li>
        <li><a href="profile.php">Mon profil</a></li>
    </ul>
    <div style="display:flex;align-items:center;gap:1rem;">
        <div class="avatar"><?= strtoupper(substr($prenom,0,1)) ?></div>
        <span><?= $prenom ?></span>
        <a href="../../controllers/UserController.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</nav>

<div class="container">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="welcome-banner">
        <h1>Bonjour, <?= $prenom ?> <span style="color:#00E5FF;"><?= $nom ?></span> 👋</h1>
        <p class="greeting">Bienvenue sur votre espace Smart Garage — Gestion intelligente de vos véhicules</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><i class="fas fa-wrench"></i><h3>INTERVENTIONS</h3><div class="value">4</div></div>
        <div class="stat-card"><i class="fas fa-bell"></i><h3>RAPPELS REÇUS</h3><div class="value">6</div></div>
        <div class="stat-card"><i class="fas fa-calendar-check"></i><h3>RDV À VENIR</h3><div class="value">1</div></div>
        <div class="stat-card"><i class="fas fa-leaf"></i><h3>CO₂ ÉCONOMISÉ</h3><div class="value" style="font-size:1.5rem;">3.4 kg</div></div>
    </div>

    <div class="ia-card">
        <h3><i class="fas fa-robot"></i> Recommandation IA Personnalisée</h3>
        <p style="margin-top:0.5rem;">
            <span class="ia-tag"><i class="fas fa-brain"></i> Analyse prédictive</span>
            D'après votre historique, nous vous recommandons de planifier un contrôle périodique.
        </p>
        <p style="margin-top:0.8rem;"><a href="#" class="btn-edit" style="margin-left:0;"><i class="fas fa-clock"></i> Planifier un rendez-vous</a></p>
    </div>

    <div class="info-card">
        <h3><i class="fas fa-user-circle"></i> Mes informations</h3>
        <div class="info-row">
            <span class="info-label"><i class="fas fa-user"></i> Nom complet</span>
            <span class="info-value"><?= $prenom . ' ' . $nom ?></span>
        </div>
        <div class="info-row">
            <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
            <span class="info-value"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label"></span>
            <a href="profile.php" class="btn-edit"><i class="fas fa-edit"></i> Modifier mon profil</a>
        </div>
    </div>
</div>
</body>
</html>
