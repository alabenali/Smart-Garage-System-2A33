<?php

require_once __DIR__ . '/../controllers/PieceController.php';
require_once __DIR__ . '/../controllers/CommandeController.php';
require_once __DIR__ . '/../controllers/PdfController.php';

session_start();

$pieceController = new PieceController();
$commandeController = new CommandeController();
$pdfController = new PdfController();

$action = isset($_GET['action']) ? $_GET['action'] : 'showCatalogue';

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

    case 'konnectSuccess':
        $commandeController->konnectSuccess();
        break;

    case 'konnectCancel':
        $commandeController->konnectCancel();
        break;

    case 'requestPiece':
        $commandeController->requestPiece();
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

    default:
        $pieceController->showCatalogue();
        break;
}
