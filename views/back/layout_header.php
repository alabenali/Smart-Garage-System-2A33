<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage Admin</title>
    <meta name="description" content="Smart Garage System – Panneau d'administration.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar (BackOffice) -->
<nav class="sg-navbar">
    <a href="index.php?action=dashboard" class="brand">
        <img src="assets/images/logo.png" alt="Smart Garage Logo" class="logo-img">
        Smart Garage <span style="color:var(--accent); font-weight:400; font-size:0.8rem; margin-left:4px;">Admin</span>
    </a>
    <ul class="nav-links">
        <li><a href="index.php?action=dashboard" class="<?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
        <li><a href="index.php?action=manageVehicles" class="<?php echo in_array(($action ?? ''), ['manageVehicles','editVehicle']) ? 'active' : ''; ?>"><i class="bi bi-car-front me-1"></i> Véhicules</a></li>
        <li><a href="index.php?action=diagnostics" class="<?php echo ($action ?? '') === 'diagnostics' ? 'active' : ''; ?>"><i class="bi bi-clipboard2-pulse me-1"></i> Diagnostics</a></li>
        <li><a href="index.php?action=showVehicles"><i class="bi bi-box-arrow-up-right me-1"></i> FrontOffice</a></li>
    </ul>
</nav>

<!-- Page Content -->
<div class="page-wrapper">
