<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage</title>
    <meta name="description" content="Smart Garage System – Gestion intelligente de votre garage automobile.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="sg-navbar">
    <a href="index.php" class="brand">
        <img src="assets/images/logo.png" alt="Smart Garage Logo" class="logo-img">
        Smart Garage
    </a>
    <?php $vehicleQuery = isset($vehicleId) && (int)$vehicleId > 0 ? ('&vehicle_id=' . (int)$vehicleId) : ''; ?>
    <ul class="nav-links">
        <li><a href="index.php?action=client_interventions<?php echo $vehicleQuery; ?>" class="<?php echo ($action ?? '') === 'client_interventions' ? 'active' : ''; ?>"><i class="bi bi-tools me-1"></i> Mes Interventions</a></li>
    </ul>
</nav>

<!-- Page Content (injected by each view) -->
<div class="page-wrapper">
