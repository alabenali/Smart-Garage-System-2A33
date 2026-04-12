<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage</title>
    <meta name="description" content="Smart Garage System – Catalogue de pièces automobiles.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="sg-navbar">
    <a href="index.php" class="brand">
        <img src="assets/images/logo.svg" alt="Smart Garage Logo" class="logo-img">
        Smart Garage
    </a>
    <ul class="nav-links">
        <li><a href="index.php?action=showCatalogue" class="<?php echo ($action ?? '') === 'showCatalogue' ? 'active' : ''; ?>"><i class="bi bi-box-seam me-1"></i> Catalogue</a></li>
        <li><a href="index.php?action=orderPiece" class="<?php echo ($action ?? '') === 'orderPiece' ? 'active' : ''; ?>"><i class="bi bi-cart-plus me-1"></i> Commander</a></li>
        <li><a href="index.php?action=dashboard" class="<?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-1"></i> BackOffice</a></li>
    </ul>
</nav>

<!-- Page Content (injected by each view) -->
<div class="page-wrapper">
