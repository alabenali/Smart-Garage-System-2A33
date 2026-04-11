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
    <ul class="nav-links">
        <li><a href="index.php?action=showVehicles" class="<?php echo ($action ?? '') === 'showVehicles' ? 'active' : ''; ?>"><i class="bi bi-car-front me-1"></i> Véhicules</a></li>
        <li><a href="index.php?action=addVehicle" class="<?php echo ($action ?? '') === 'addVehicle' ? 'active' : ''; ?>"><i class="bi bi-plus-circle me-1"></i> Ajouter</a></li>
        <li><a href="index.php?action=dashboard" class="<?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-1"></i> BackOffice</a></li>
    </ul>
</nav>

<!-- Page Content (injected by each view) -->
<div class="page-wrapper">
