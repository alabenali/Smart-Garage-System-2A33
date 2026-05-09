<?php
// $currentAction aide à surligner l'élément actif du menu.
$currentAction = '';
if (isset($action)) {
    $currentAction = $action;
}
$styleVersion = is_file(__DIR__ . '/../assets/css/style.css')
    ? (string) filemtime(__DIR__ . '/../assets/css/style.css')
    : (string) time();

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
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage</title>
    <meta name="description" content="Smart Garage System – Catalogue de pièces automobiles.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="views/assets/css/style.css?v=<?php echo urlencode($styleVersion); ?>">
</head>
<body>

<div class="app-shell">
    <aside class="app-sidebar">
        <a href="/integration/client/controllers/UserController.php?action=showDashboard" class="brand-stack">
            <img src="/integration/vehicule%20et%20rdv/views/images/logo.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Client<?php echo $clientName !== '' ? ' • ' . htmlspecialchars($clientName) : ''; ?></span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="/integration/client/controllers/UserController.php?action=showDashboard"><i class="bi bi-person-circle"></i> Mon espace</a>
            <a href="/integration/client/controllers/UserController.php?action=showProfile"><i class="bi bi-person"></i> Mon profil</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=showVehicles"><i class="bi bi-car-front"></i> Mes v&eacute;hicules</a>
            <a href="/integration/client/controllers/UserController.php?action=showMyRendezvous"><i class="bi bi-calendar-check"></i> Mes rendez-vous</a>
            <a href="/integration/diagnostic/frontoffice.php?action=mes_diagnostics"><i class="bi bi-clipboard2-pulse"></i> Mes diagnostics</a>
            <a href="/integration/diagnostic/frontoffice.php?action=client_interventions"><i class="bi bi-tools"></i> Mes interventions</a>
            <a href="/integration/diagnostic/frontoffice.php?action=client_messages"><i class="bi bi-chat-left-text"></i> Suivi messages</a>
            <a href="/integration/samrtnour/frontoffice.php?action=showCatalogue" class="<?php echo $currentAction === 'showCatalogue' ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Catalogue</a>
            <a href="/integration/samrtnour/frontoffice.php?action=orderPiece" class="<?php echo $currentAction === 'orderPiece' ? 'active' : ''; ?>"><i class="bi bi-cart-plus"></i> Commander</a>
            <a href="/integration/samrtnour/frontoffice.php?action=orderHistory" class="<?php echo $currentAction === 'orderHistory' ? 'active' : ''; ?>"><i class="bi bi-clock-history"></i> Historique</a>
            <a href="#" onclick="if (typeof toggleChatbot === 'function') { toggleChatbot(); } return false;"><i class="bi bi-chat-dots"></i> Chat IA</a>
            <a href="/integration/client/controllers/UserController.php?action=logout"><i class="bi bi-box-arrow-right"></i> D&eacute;connexion</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
