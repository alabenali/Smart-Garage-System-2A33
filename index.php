<?php
// ============================================
// Smart Garage System – Main Router (index.php)
// ============================================

require_once __DIR__ . '/controllers/VehicleController.php';

$controller = new VehicleController();

// Read action from query string
$action = isset($_GET['action']) ? $_GET['action'] : 'showVehicles';

switch ($action) {
    // ---- FrontOffice ----
    case 'showVehicles':
        $controller->showVehicles();
        break;

    case 'addVehicle':
        $controller->addVehicle();
        break;

    // ---- BackOffice ----
    case 'dashboard':
        $controller->dashboard();
        break;

    case 'manageVehicles':
        $controller->manageVehicles();
        break;

    case 'editVehicle':
        $controller->updateVehicle();
        break;

    case 'deleteVehicle':
        $controller->deleteVehicle();
        break;

    default:
        $controller->showVehicles();
        break;
}
