<?php
// $currentAction aide à surligner l'élément actif du menu.
$currentAction = '';
if (isset($action)) {
    $currentAction = $action;
}
$styleVersion = is_file(__DIR__ . '/../assets/css/style.css')
    ? (string) filemtime(__DIR__ . '/../assets/css/style.css')
    : (string) time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage</title>
    <meta name="description" content="Smart Garage System – Catalogue de pièces automobiles.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="views/assets/css/style.css?v=<?php echo urlencode($styleVersion); ?>">
</head>
<body>

<!-- Barre de navigation -->
<nav class="sg-navbar">
    <a href="index.php" class="brand">
        <img src="views/assets/images/logo-custom.png" alt="Smart Garage Logo" class="logo-img">
        Smart Garage
    </a>
    <ul class="nav-links">
        <li><a href="index.php?action=showCatalogue" class="<?php echo $currentAction === 'showCatalogue' ? 'active' : ''; ?>"><i class="bi bi-box-seam me-1"></i> Catalogue</a></li>
        <li><a href="index.php?action=orderPiece" class="<?php echo $currentAction === 'orderPiece' ? 'active' : ''; ?>"><i class="bi bi-cart-plus me-1"></i> Commander</a></li>
        <li><a href="index.php?action=orderHistory" class="<?php echo $currentAction === 'orderHistory' ? 'active' : ''; ?>"><i class="bi bi-clock-history me-1"></i> Historique</a></li>
        <li><a href="index.php?action=dashboard" class="<?php echo $currentAction === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2 me-1"></i> BackOffice</a></li>
    </ul>
</nav>

<!-- Contenu de page (injecté par chaque vue) -->
<div class="page-wrapper">
