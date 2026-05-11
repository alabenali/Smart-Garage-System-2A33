<?php
if (isset($_SESSION['admin_id']) || (($_SESSION['role'] ?? '') === 'admin')) {
    header('Location: /integration/client/controllers/AdminController.php?action=showDashboard');
    exit;
}

$clientName = '';
if (!empty($_SESSION['user_prenom']) || !empty($_SESSION['user_nom'])) {
    $clientName = trim((string) ($_SESSION['user_prenom'] ?? '') . ' ' . (string) ($_SESSION['user_nom'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Smart Garage</title>
    <meta name="description" content="Smart Garage System - Gestion intelligente de votre garage automobile.">
    <?php $styleVersion = @filemtime(__DIR__ . '/../css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="/integration/client/controllers/UserController.php?action=showDashboard" class="brand-stack">
            <img src="views/images/logo.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Client<?php echo $clientName !== '' ? ' - ' . htmlspecialchars($clientName) : ''; ?></span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="/integration/client/controllers/UserController.php?action=showDashboard"><i class="bi bi-person-circle"></i> Mon espace</a>
            <a href="/integration/client/controllers/UserController.php?action=showProfile"><i class="bi bi-person"></i> Mon profil</a>
            <a href="index.php?action=showVehicles" class="<?php echo in_array(($action ?? ''), ['showVehicles', 'addVehicle', 'vehicleHealth'], true) ? 'active' : ''; ?>"><i class="bi bi-car-front"></i> Mes v&eacute;hicules</a>
            <a href="/integration/client/controllers/UserController.php?action=showMyRendezvous"><i class="bi bi-calendar-check"></i> Mes rendez-vous</a>
            <a href="/integration/samrtnour/frontoffice.php?action=showCatalogue"><i class="bi bi-box-seam"></i> Catalogue</a>
            <a href="/integration/samrtnour/frontoffice.php?action=orderPiece"><i class="bi bi-cart-plus"></i> Commander</a>
            <a href="/integration/samrtnour/frontoffice.php?action=orderHistory"><i class="bi bi-clock-history"></i> Historique</a>
            <a href="#" onclick="if (typeof toggleChatbot === 'function') { toggleChatbot(); } return false;"><i class="bi bi-chat-dots"></i> Chat IA</a>
            <a href="/integration/client/controllers/UserController.php?action=logout"><i class="bi bi-box-arrow-right"></i> D&eacute;connexion</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
