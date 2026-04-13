<?php
// ============================================
// Smart Garage System – Main Router (index.php)
// ============================================

require_once __DIR__ . '/controllers/VehicleController.php';
require_once __DIR__ . '/controllers/DiagnosticController.php';

$vehicleController = new VehicleController();
$diagController = new DiagnosticController();

// Read action from query string
$action = isset($_GET['action']) ? $_GET['action'] : 'showVehicles';

switch ($action) {
    // ---- FrontOffice (Véhicules) ----
    case 'showVehicles':
        $vehicleController->showVehicles();
        break;

    case 'addVehicle':
        $vehicleController->addVehicle();
        break;

    // ---- FrontOffice (Diagnostics) ----
    case 'mes_diagnostics':
        $diagController->handleRequest();
        $diagnostics = $diagController->list();
        require __DIR__ . '/views/front/mes_diagnostics.php';
        break;

    // ---- BackOffice (Dashboard & Véhicules) ----
    case 'dashboard':
        $stats = $diagController->stats();
        require __DIR__ . '/views/back/dashboard.php';
        break;

    case 'manageVehicles':
        $vehicleController->manageVehicles();
        break;

    case 'editVehicle':
        $vehicleController->updateVehicle();
        break;

    case 'deleteVehicle':
        $vehicleController->deleteVehicle();
        break;

    // ---- BackOffice (Diagnostics CRUD) ----
    case 'diagnostics':
        $diagController->handleRequest();
        $diagnostics = $diagController->list();
        $vehicles = $diagController->getVehicles();
        require __DIR__ . '/views/back/diagnostics.php';
        break;

    case 'generateDiagnosticPdf':
        $diagController->generateDiagnosticPdf();
        break;

    default:
        $vehicleController->showVehicles();
        break;
}
