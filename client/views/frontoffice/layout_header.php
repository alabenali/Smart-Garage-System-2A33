<?php
/**
 * Layout header (Client FrontOffice) - style aligned with module vehicule et rdv.
 * Expected vars: $pageTitle (string), $action (string)
 */
$pageTitle = $pageTitle ?? 'Smart Garage';
$action = $action ?? '';
$styleVersion = @filemtime(__DIR__ . '/../../../../vehicule et rdv/views/css/style.css') ?: time();

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
    <title><?php echo htmlspecialchars($pageTitle); ?> – Smart Garage</title>
    <meta name="description" content="Smart Garage – Espace client.">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/integration/vehicule%20et%20rdv/views/css/style.css?v=<?php echo $styleVersion; ?>">
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
            <a href="/integration/client/controllers/UserController.php?action=showDashboard" class="<?php echo $action === 'clientDashboard' ? 'active' : ''; ?>"><i class="bi bi-house"></i> Mon espace</a>
            <a href="/integration/client/controllers/UserController.php?action=showProfile" class="<?php echo $action === 'clientProfile' ? 'active' : ''; ?>"><i class="bi bi-person"></i> Mon profil</a>
            <a href="/integration/vehicule%20et%20rdv/index.php?action=showVehicles" class="<?php echo $action === 'clientVehicles' ? 'active' : ''; ?>"><i class="bi bi-car-front"></i> Mes v&eacute;hicules</a>
            <a href="/integration/client/controllers/UserController.php?action=showMyRendezvous" class="<?php echo $action === 'clientRendezvous' ? 'active' : ''; ?>"><i class="bi bi-calendar-check"></i> Mes rendez-vous</a>
            <a href="/integration/samrtnour/frontoffice.php?action=showCatalogue" class="<?php echo in_array($action, ['clientPieces', 'clientPiecesCatalogue', 'showCatalogue'], true) ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Catalogue</a>
            <a href="/integration/samrtnour/frontoffice.php?action=orderPiece" class="<?php echo in_array($action, ['clientPieceOrder', 'orderPiece'], true) ? 'active' : ''; ?>"><i class="bi bi-cart-plus"></i> Commander</a>
            <a href="/integration/samrtnour/frontoffice.php?action=orderHistory" class="<?php echo in_array($action, ['clientPieceHistory', 'orderHistory'], true) ? 'active' : ''; ?>"><i class="bi bi-clock-history"></i> Historique</a>
            <a href="#" onclick="if (typeof toggleChatbot === 'function') { toggleChatbot(); } return false;"><i class="bi bi-chat-dots"></i> Chat IA</a>
            <a href="/integration/client/controllers/UserController.php?action=logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
