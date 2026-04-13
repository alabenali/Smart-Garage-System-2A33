<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel   = new User();
$totalUsers  = $userModel->countAll();
$activeUsers = $userModel->countActive();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
    <nav>
        <ul>
            <li><a href="admin_dashboard.php?action=showDashboard" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
            <li><a href="users_list.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
            <li><a href="add_user.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
            <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h1><i class="fas fa-tachometer-alt" style="color:#00E5FF;"></i> Tableau de bord</h1>
        <span class="admin-badge"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_nom']) ?></span>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <i class="fas fa-users"></i>
            <div class="number"><?= $totalUsers ?></div>
            <div class="label">Total Clients</div>
        </div>
        <div class="stat-box">
            <i class="fas fa-user-check"></i>
            <div class="number"><?= $activeUsers ?></div>
            <div class="label">Clients Actifs</div>
        </div>
        <div class="stat-box">
            <i class="fas fa-user-times"></i>
            <div class="number"><?= $totalUsers - $activeUsers ?></div>
            <div class="label">Clients Inactifs</div>
        </div>
    </div>

    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
        <a href="users_list.php?action=listUsers" class="btn-action"><i class="fas fa-list"></i> Voir tous les clients</a>
        <a href="add_user.php?action=showAddUser" class="btn-action"><i class="fas fa-user-plus"></i> Ajouter un client</a>
    </div>
</main>
</body>
</html>
