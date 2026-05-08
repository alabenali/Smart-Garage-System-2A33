<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Backoffice réservé à l'admin (session déjà utilisée par client/vehicule et rdv).
if (!(isset($_SESSION['admin_id']) || (($_SESSION['role'] ?? '') === 'admin'))) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$allowedActions = [
    'dashboard',
    'managePieces',
    'addPiece',
    'viewPiece',
    'editPiece',
    'confirmDeletePiece',
    'deletePiece',
    'manageCommandes',
    'viewCommande',
    'updateCommandeStatus',
    'manageGaranties',
    'deleteCommande',
    'exportCommandes',
    'exportCommande',
    'exportDemandes',
    'marquerRemplacee',
    'garantiesByClient',
    'testAlertes',
    'garantieDetail',
];

$action = isset($_GET['action']) ? (string) $_GET['action'] : 'dashboard';
if (!in_array($action, $allowedActions, true)) {
    $action = 'dashboard';
}

$target = rawurlencode('samrt nour') . '/index.php';
$query = $_GET;
$query['action'] = $action;

header('Location: ' . $target . '?' . http_build_query($query));
exit;
