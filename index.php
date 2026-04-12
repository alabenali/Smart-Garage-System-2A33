<?php
// ============================================
// Smart Garage System – Main Router (index.php)
// Module: Pièces & Commandes
// ============================================

require_once __DIR__ . '/controllers/PieceController.php';

$controller = new PieceController();

// Read action from query string
$action = isset($_GET['action']) ? $_GET['action'] : 'showCatalogue';

switch ($action) {
    // ---- FrontOffice ----
    case 'showCatalogue':
        $controller->showCatalogue();
        break;

    case 'orderPiece':
        $controller->orderPiece();
        break;

    // ---- BackOffice ----
    case 'dashboard':
        $controller->dashboard();
        break;

    case 'managePieces':
        $controller->managePieces();
        break;

    case 'addPiece':
        $controller->addPiece();
        break;

    case 'editPiece':
        $controller->updatePiece();
        break;

    case 'confirmDeletePiece':
        $controller->confirmDeletePiece();
        break;

    case 'deletePiece':
        $controller->deletePiece();
        break;

    case 'manageCommandes':
        $controller->manageCommandes();
        break;

    case 'deleteCommande':
        $controller->deleteCommande();
        break;

    default:
        $controller->showCatalogue();
        break;
}
