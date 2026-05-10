<?php
$styleVersion = is_file(__DIR__ . '/../assets/css/style.css')
    ? (string) filemtime(__DIR__ . '/../assets/css/style.css')
    : (string) time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage Admin</title>
    <meta name="description" content="Smart Garage System – Panneau d'administration des pièces.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="views/assets/css/style.css?v=<?php echo urlencode($styleVersion); ?>">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="/integration/client/controllers/AdminController.php?action=showDashboard" class="brand-stack">
            <img src="/integration/vehicule%20et%20rdv/views/images/logo.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Admin</span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="/integration/client/controllers/AdminController.php?action=showDashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="/integration/client/controllers/AdminController.php?action=listUsers"><i class="bi bi-people"></i> Clients</a>
            <a href="/integration/client/controllers/AdminController.php?action=showAddUser"><i class="bi bi-person-plus"></i> Ajouter client</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=manageVehicles"><i class="bi bi-car-front"></i> V&eacute;hicules</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=backCalendar"><i class="bi bi-calendar-week"></i> Calendrier RDV</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=backRdvList"><i class="bi bi-card-checklist"></i> Liste RDV</a>
            <a href="/integration/diagnostic/backoffice.php?action=diagnostics"><i class="bi bi-clipboard2-pulse"></i> Diagnostic</a>
            <a href="/integration/diagnostic/backoffice.php?action=admin_interventions"><i class="bi bi-tools"></i> Interventions</a>
            <a href="/integration/diagnostic/backoffice.php?action=messages"><i class="bi bi-chat-dots"></i> Messages</a>
            <a href="index.php?action=managePieces" class="<?php echo in_array(($action ?? ''), ['managePieces', 'addPiece', 'viewPiece', 'editPiece', 'confirmDeletePiece', 'deletePiece'], true) ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Pièces</a>
            <a href="index.php?action=manageCommandes" class="<?php echo in_array(($action ?? ''), ['manageCommandes', 'viewCommande', 'updateCommandeStatus', 'deleteCommande', 'exportCommandes', 'exportCommande', 'exportDemandes'], true) ? 'active' : ''; ?>"><i class="bi bi-cart3"></i> Commandes</a>
            <a href="index.php?action=manageGaranties" class="<?php echo in_array(($action ?? ''), ['manageGaranties', 'marquerRemplacee', 'garantiesByClient', 'testAlertes', 'garantieDetail'], true) ? 'active' : ''; ?>"><i class="bi bi-shield-check"></i> Garanties</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=adminLoyalty"><i class="bi bi-stars"></i> Fid&eacute;lit&eacute;</a>
            <a href="/integration/vehicule%20et%20rdv/admin/test_rapport.php"><i class="bi bi-file-earmark-bar-graph"></i> Rapport</a>
            <a href="/integration/client/controllers/AIController.php?action=showAssistant"><i class="bi bi-stars"></i> AI Helper</a>
            <a href="/integration/client/controllers/AdminController.php?action=showAdminProfile"><i class="bi bi-person-gear"></i> Mon profil</a>
            <a href="/integration/client/controllers/AdminController.php?action=logout"><i class="bi bi-box-arrow-right"></i> D&eacute;connexion</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
