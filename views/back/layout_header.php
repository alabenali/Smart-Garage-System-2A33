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
            <a href="index.php?action=dashboard" class="<?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="index.php?action=manageVehicles" class="<?php echo in_array(($action ?? ''), ['manageVehicles', 'editVehicle']) ? 'active' : ''; ?>"><i class="bi bi-car-front"></i> Véhicules</a>
            <a href="index.php?action=backCalendar" class="<?php echo ($action ?? '') === 'backCalendar' ? 'active' : ''; ?>"><i class="bi bi-calendar-week"></i> Calendrier RDV</a>
            <a href="index.php?action=backRdvList" class="<?php echo ($action ?? '') === 'backRdvList' ? 'active' : ''; ?>"><i class="bi bi-card-checklist"></i> Liste RDV</a>
            <a href="index.php?action=showVehicles"><i class="bi bi-box-arrow-up-right"></i> FrontOffice</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
