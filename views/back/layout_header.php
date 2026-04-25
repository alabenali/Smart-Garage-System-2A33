<?php
$styleVersion = is_file(__DIR__ . '/../assets/css/style.css')
    ? (string) filemtime(__DIR__ . '/../assets/css/style.css')
    : (string) time();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' – ' : ''; ?>Smart Garage Admin</title>
    <meta name="description" content="Smart Garage System – Panneau d'administration des pièces.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="views/assets/css/style.css?v=<?php echo urlencode($styleVersion); ?>">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="index.php?action=dashboard" class="brand-stack">
            <img src="views/assets/images/logo-custom.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Admin</span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="index.php?action=dashboard" class="<?php echo ($action ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="index.php?action=managePieces" class="<?php echo in_array(($action ?? ''), ['managePieces', 'addPiece', 'editPiece', 'confirmDeletePiece'], true) ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Pièces</a>
            <a href="index.php?action=manageCommandes" class="<?php echo ($action ?? '') === 'manageCommandes' ? 'active' : ''; ?>"><i class="bi bi-cart3"></i> Commandes</a>
            <a href="index.php?action=showCatalogue"><i class="bi bi-box-arrow-up-right"></i> FrontOffice</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">
