<?php
$pageTitle = $pageTitle ?? 'Admin';
$currentAction = $currentAction ?? '';
$adminName = $_SESSION['admin_nom'] ?? 'Admin';
$garageBase = '/integration/vehicule%20et%20rdv';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Smart Garage Admin</title>
    <?php $garageCssPath = dirname(__DIR__, 3) . '/vehicule et rdv/views/css/style.css'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $garageBase; ?>/views/css/style.css?v=<?php echo @filemtime($garageCssPath) ?: time(); ?>">
    <style>
        .client-admin-chip { display:inline-flex; align-items:center; gap:0.45rem; padding:0.45rem 0.75rem; border-radius:999px; background:var(--info-bg); color:var(--accent-secondary); font-size:0.84rem; font-weight:700; }
        .client-topline { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
        .client-link { color:var(--accent-secondary); font-weight:700; text-decoration:none; }
        .client-link:hover { color:var(--accent); }
        .client-mini-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:0.9rem; margin:1rem 0; }
        .badge-category { display:inline-flex; align-items:center; padding:0.35rem 0.65rem; border-radius:999px; background:var(--info-bg); color:var(--accent-secondary); font-size:0.84rem; font-weight:800; }
        .client-empty { border-top:1px solid var(--border-color); }
        @media (max-width:768px){ .client-topline{display:block;} .client-topline .btn-group-actions{margin-top:1rem;} }
    </style>
    <?php if (!empty($extraHead)) { echo $extraHead; } ?>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="/integration/client/controllers/AdminController.php?action=showDashboard" class="brand-stack">
            <img src="<?php echo $garageBase; ?>/views/images/logo.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Admin</span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="/integration/client/controllers/AdminController.php?action=showDashboard" class="<?php echo $currentAction === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="<?php echo in_array($currentAction, ['clients', 'clientDetail', 'editClient'], true) ? 'active' : ''; ?>"><i class="bi bi-people"></i> Clients</a>
            <a href="/integration/client/controllers/AdminController.php?action=showAddUser" class="<?php echo $currentAction === 'addClient' ? 'active' : ''; ?>"><i class="bi bi-person-plus"></i> Ajouter client</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=manageVehicles"><i class="bi bi-car-front"></i> V&eacute;hicules</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=backCalendar"><i class="bi bi-calendar-week"></i> Calendrier RDV</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=backRdvList"><i class="bi bi-card-checklist"></i> Liste RDV</a>
            <a href="/integration/samrtnour/backoffice.php?action=managePieces" class="<?php echo in_array($currentAction, ['pieces', 'managePieces', 'addPiece', 'viewPiece', 'editPiece', 'confirmDeletePiece', 'deletePiece'], true) ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Pi&egrave;ces</a>
            <a href="/integration/samrtnour/backoffice.php?action=manageCommandes" class="<?php echo in_array($currentAction, ['manageCommandes', 'viewCommande', 'updateCommandeStatus', 'deleteCommande', 'exportCommandes', 'exportCommande', 'exportDemandes'], true) ? 'active' : ''; ?>"><i class="bi bi-cart3"></i> Commandes</a>
            <a href="/integration/samrtnour/backoffice.php?action=manageGaranties" class="<?php echo in_array($currentAction, ['manageGaranties', 'marquerRemplacee', 'garantiesByClient', 'testAlertes', 'garantieDetail'], true) ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> Garanties</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=adminLoyalty"><i class="bi bi-stars"></i> Fid&eacute;lit&eacute;</a>
            <a href="/integration/vehicule%20et%20rdv/admin/test_rapport.php"><i class="bi bi-file-earmark-bar-graph"></i> Rapport</a>
            <a href="/integration/client/controllers/AIController.php?action=showAssistant" class="<?php echo $currentAction === 'aiHelper' ? 'active' : ''; ?>"><i class="bi bi-stars"></i> AI Helper</a>
            <a href="/integration/client/controllers/AdminController.php?action=showAdminProfile" class="<?php echo $currentAction === 'profile' ? 'active' : ''; ?>"><i class="bi bi-person-gear"></i> Mon profil</a>
            <a href="/integration/client/controllers/AdminController.php?action=logout"><i class="bi bi-box-arrow-right"></i> D&eacute;connexion</a>
        </nav>
    </aside>
    <main class="app-main">
        <div class="page-wrapper">
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['errors'])): ?>
                <div class="sg-alert sg-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
