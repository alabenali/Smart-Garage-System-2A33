<?php
$currentBackAction = $action ?? ($_GET['action'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage Admin</title>
    <meta name="description" content="Smart Garage System – Panneau d'administration.">
    <?php $styleVersion = @filemtime(__DIR__ . '/../css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="views/css/style.css?v=<?php echo $styleVersion; ?>">
    <?php if (!empty($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <?php
            $cssVersion = time();
            if (strpos($css, 'views/css/') === 0) {
                $relativeCss = substr($css, strlen('views/css/'));
                $absCssPath = __DIR__ . '/../css/' . $relativeCss;
                $cssVersion = @filemtime($absCssPath) ?: time();
            }
            ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>?v=<?php echo $cssVersion; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="index.php?action=dashboard" class="brand-stack">
            <img src="views/images/logo.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Admin</span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="/integration/vehicule%20et%20rdv/index.php?action=dashboard" class="<?php echo $currentBackAction === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="/integration/client/controllers/AdminController.php?action=listUsers"><i class="bi bi-people"></i> Clients</a>
            <a href="/integration/client/controllers/AdminController.php?action=showAddUser"><i class="bi bi-person-plus"></i> Ajouter client</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=manageVehicles" class="<?php echo in_array($currentBackAction, ['manageVehicles', 'addVehicleBack', 'vehicleDetail', 'editVehicle', 'deleteVehicle'], true) ? 'active' : ''; ?>"><i class="bi bi-car-front"></i> V&eacute;hicules</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=backCalendar" class="<?php echo in_array($currentBackAction, ['backCalendar', 'backSlotDetails', 'backBlockSlot', 'backCreateManualRdv'], true) ? 'active' : ''; ?>"><i class="bi bi-calendar-week"></i> Calendrier RDV</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=backRdvList" class="<?php echo in_array($currentBackAction, ['backRdvList', 'backEditRdv', 'backRdvExportCsv', 'backRdvExportPdf', 'backUpdateStatus'], true) ? 'active' : ''; ?>"><i class="bi bi-card-checklist"></i> Liste RDV</a>
            <a href="/integration/samrtnour/backoffice.php?action=managePieces"><i class="bi bi-box-seam"></i> Pi&egrave;ces</a>
            <a href="/integration/samrtnour/backoffice.php?action=manageCommandes"><i class="bi bi-cart3"></i> Commandes</a>
            <a href="/integration/samrtnour/backoffice.php?action=manageGaranties"><i class="bi bi-shield-check"></i> Garanties</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=adminLoyalty" class="<?php echo $currentBackAction === 'adminLoyalty' ? 'active' : ''; ?>"><i class="bi bi-stars"></i> Fid&eacute;lit&eacute;</a>
            <a href="/integration/vehicule%20et%20rdv/admin/test_rapport.php" class="<?php echo $currentBackAction === 'weeklyReport' ? 'active' : ''; ?>"><i class="bi bi-file-earmark-bar-graph"></i> Rapport</a>
            <a href="/integration/client/controllers/AIController.php?action=showAssistant"><i class="bi bi-stars"></i> AI Helper</a>
            <a href="/integration/client/controllers/AdminController.php?action=showAdminProfile"><i class="bi bi-person-gear"></i> Mon profil</a>
            <a href="/integration/client/controllers/AdminController.php?action=logout"><i class="bi bi-box-arrow-right"></i> D&eacute;connexion</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
