<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage</title>
    <meta name="description" content="Smart Garage System – Gestion intelligente de votre garage automobile.">
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

<!-- Barre de navigation -->
<nav class="sg-navbar">
    <a href="index.php" class="brand">
        <img src="views/images/logo.png" alt="Smart Garage Logo" class="logo-img">
        Smart Garage
    </a>
    <ul class="nav-links">
        <li><a href="index.php?action=showVehicles" class="<?php echo ($action ?? '') === 'showVehicles' ? 'active' : ''; ?>"><i class="bi bi-car-front me-1"></i> Véhicules</a></li>
        <li><a href="index.php?action=frontCalendar" class="<?php echo ($action ?? '') === 'frontCalendar' ? 'active' : ''; ?>"><i class="bi bi-calendar-check me-1"></i> Rendez-vous</a></li>
        <li><a href="index.php?action=dashboard" class="<?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-1"></i> BackOffice</a></li>
    </ul>
</nav>

<!-- Contenu de la page (injecté par chaque vue) -->
<div class="page-wrapper">
