<?php

require_once __DIR__ . '/../controllers/PieceController.php';
require_once __DIR__ . '/../controllers/CommandeController.php';
require_once __DIR__ . '/../controllers/PdfController.php';
require_once __DIR__ . '/../controllers/PanierController.php';
require_once __DIR__ . '/../controllers/GarantieController.php';

session_start();
$action = isset($_GET['action']) ? $_GET['action'] : 'showCatalogue';

$adminActions = [
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
    'deleteCommande',
    'exportCommandes',
    'exportCommande',
    'exportDemandes',
    'manageGaranties',
    'marquerRemplacee',
    'garantiesByClient',
    'testAlertes',
    'garantieDetail',
];

$isAdmin = isset($_SESSION['admin_id']) || (($_SESSION['role'] ?? '') === 'admin');
if (in_array($action, $adminActions, true) && !$isAdmin) {
    header('Location: /integration/samrtnour/backoffice.php');
    exit;
}

$pieceController = new PieceController();
$commandeController = new CommandeController();
$pdfController = new PdfController();
$panierController = new PanierController();
$garantieController = new GarantieController();

switch ($action) {
    case 'showCatalogue':
        $pieceController->showCatalogue();
        break;

    case 'orderPiece':
        $commandeController->orderPiece();
        break;

    case 'orderHistory':
        $commandeController->orderHistory();
        break;

    case 'orderDetail':
        $commandeController->orderDetail();
        break;

    case 'konnectSuccess':
        $commandeController->konnectSuccess();
        break;

    case 'konnectCancel':
        $commandeController->konnectCancel();
        break;

    case 'requestPiece':
        $commandeController->requestPiece();
        break;

    case 'addToCart':
        $panierController->addToCart();
        break;

    case 'removeFromCart':
        $panierController->removeFromCart();
        break;

    case 'updateQty':
        $panierController->updateQty();
        break;

    case 'getCart':
        $panierController->getCart();
        break;

    case 'clearCart':
        $panierController->clearCart();
        break;

    case 'checkout':
        $panierController->checkout();
        break;

    case 'confirmOrder':
        $panierController->confirmOrder();
        break;

    case 'orderConfirmation':
        $panierController->orderConfirmation();
        break;

    case 'dashboard':
        $pieceController->dashboard();
        break;

    case 'managePieces':
        $pieceController->managePieces();
        break;

    case 'addPiece':
        $pieceController->addPiece();
        break;

    case 'viewPiece':
        $pieceController->viewPiece();
        break;

    case 'editPiece':
        $pieceController->updatePiece();
        break;

    case 'confirmDeletePiece':
        $pieceController->confirmDeletePiece();
        break;

    case 'deletePiece':
        $pieceController->deletePiece();
        break;

    case 'manageCommandes':
        $commandeController->manageCommandes();
        break;

    case 'viewCommande':
        $commandeController->viewCommande();
        break;

    case 'updateCommandeStatus':
        $commandeController->updateCommandeStatus();
        break;

    case 'deleteCommande':
        $commandeController->deleteCommande();
        break;

    case 'exportCommandes':
        $pdfController->exportCommandes();
        break;

    case 'exportCommande':
        $pdfController->exportCommande();
        break;

    case 'exportDemandes':
        $pdfController->exportDemandes();
        break;

    // ── Garanties ──
    case 'manageGaranties':
        $garantieController->index();
        break;

    case 'marquerRemplacee':
        $garantieController->marquerRemplacee();
        break;

    case 'garantiesByClient':
        $garantieController->byClient();
        break;

    case 'testAlertes':
        $garantieController->testAlertes();
        break;

    case 'garantieDetail':
        $garantieController->detail();
        break;

    default:
        $pieceController->showCatalogue();
        break;
}
