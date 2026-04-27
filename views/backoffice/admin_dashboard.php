<?php
<<<<<<< HEAD
// views/backoffice/admin_dashboard.php

require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['admin_id'])) { 
    header('Location: admin_login.php'); 
    exit; 
}

// Requêtes SQL directes
$db = Database::getConnection();
$totalUsers = (int) $db->query("SELECT COUNT(*) FROM user WHERE post = 'client'")->fetchColumn();
$activeUsers = (int) $db->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND statut = 'actif'")->fetchColumn();
=======
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel   = new User();
$totalUsers  = $userModel->countAll();
$activeUsers = $userModel->countActive();
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
=======
    <link rel="stylesheet" href="style.css">
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
<<<<<<< HEAD
    <?php
    $adminPic = $_SESSION['admin_profile_pic'] ?? null;
    $adminPicUrl = null;
    if ($adminPic) {
        $sp = __DIR__ . '/../../' . $adminPic;
        if (file_exists($sp)) $adminPicUrl = '/projet_final/' . $adminPic;
    }
    ?>
    <div style="text-align:center;padding:15px 0;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:10px;">
        <?php if ($adminPicUrl): ?>
            <img src="<?= htmlspecialchars($adminPicUrl) ?>" alt="Admin" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #00E5FF;margin-bottom:8px;">
        <?php else: ?>
            <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:white;margin:0 auto 8px;border:3px solid #00E5FF;">
                <?= strtoupper(substr($_SESSION['admin_nom'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div style="color:#ccc;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['admin_nom']) ?></div>
    </div>
    <nav>
        <ul>
            <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=showStatistics"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
=======
    <nav>
        <ul>
            <li><a href="admin_dashboard.php?action=showDashboard" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
            <li><a href="users_list.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
            <li><a href="add_user.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
            <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
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
<<<<<<< HEAD
        <a href="/projet_final/controllers/AdminController.php?action=listUsers" class="btn-action"><i class="fas fa-list"></i> Voir tous les clients</a>
        <a href="/projet_final/controllers/AdminController.php?action=showAddUser" class="btn-action"><i class="fas fa-user-plus"></i> Ajouter un client</a>
        <a href="/projet_final/controllers/AdminController.php?action=showStatistics" class="btn-action"><i class="fas fa-chart-bar"></i> Statistiques</a>
    </div>
</main>
</body>
</html>
=======
        <a href="users_list.php?action=listUsers" class="btn-action"><i class="fas fa-list"></i> Voir tous les clients</a>
        <a href="add_user.php?action=showAddUser" class="btn-action"><i class="fas fa-user-plus"></i> Ajouter un client</a>
    </div>
</main>
</body>
</html>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
