<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si admin, on redirige vers le backoffice.
if (isset($_SESSION['admin_id']) || (($_SESSION['role'] ?? '') === 'admin')) {
    header('Location: /integration/samrtnour/backoffice.php');
    exit;
}

// Si client non connecté, on renvoie vers la page de login existante.
if (empty($_SESSION['user_id'])) {
    header('Location: /integration/client/controllers/UserController.php?action=showLogin');
    exit;
}

$allowedActions = [
    'showCatalogue',
    'orderPiece',
    'orderHistory',
    'orderDetail',
    'konnectSuccess',
    'konnectCancel',
    'requestPiece',
    'addToCart',
    'removeFromCart',
    'updateQty',
    'getCart',
    'clearCart',
    'checkout',
    'confirmOrder',
    'orderConfirmation',
];

$action = isset($_GET['action']) ? (string) $_GET['action'] : 'showCatalogue';
if (!in_array($action, $allowedActions, true)) {
    $action = 'showCatalogue';
}

$target = rawurlencode('samrt nour') . '/index.php';

// On propage les paramètres utiles (sans toucher aux contrats du module interne).
$query = $_GET;
$query['action'] = $action;

header('Location: ' . $target . '?' . http_build_query($query));
exit;
