<?php
// ============================================
// Système Smart Garage – Routeur Principal (index.php)
// ============================================

require_once __DIR__ . '/controllers/VehicleController.php';
require_once __DIR__ . '/controllers/CalendrierController.php';
require_once __DIR__ . '/controllers/IntegrationApiController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$controller = new VehicleController();
$calendrierController = new CalendrierController();
$integrationApiController = new IntegrationApiController();

// Récupérer l'action à partir de la chaîne de requête
$action = isset($_GET['action']) ? $_GET['action'] : 'showVehicles';

switch ($action) {
    // ---- Front Office ----
    case 'showVehicles':
        $controller->showVehicles();
        break;

    case 'vehicleHealth':
        $controller->vehicleHealth();
        break;

    case 'addVehicle':
        $controller->addVehicle();
        break;

    case 'frontCalendar':
        $calendrierController->frontCalendar();
        break;

    case 'frontCreateRdv':
        $calendrierController->frontCreate();
        break;

    case 'frontConfirmation':
        $calendrierController->frontConfirmation();
        break;

    case 'apiMonthAvailability':
        $calendrierController->apiMonthAvailability();
        break;

    case 'apiDaySlots':
        $calendrierController->apiDaySlots();
        break;

    case 'apiClientVehicles':
        $integrationApiController->clientVehicles();
        break;

    case 'apiClientRdv':
        $integrationApiController->clientRendezvous();
        break;

    case 'apiVehicleRdv':
        $integrationApiController->vehicleRendezvous();
        break;

    case 'apiVehicleDiagnostics':
        $integrationApiController->vehicleDiagnostics();
        break;

    case 'apiRdvDiagnostic':
        $integrationApiController->rdvDiagnostic();
        break;

    case 'apiClientDiagnostics':
        $integrationApiController->clientDiagnostics();
        break;

    // ---- Back Office ----
    case 'dashboard':
        $controller->dashboard();
        break;

    case 'backCalendar':
        $calendrierController->backCalendar();
        break;

    case 'backSlotDetails':
        $calendrierController->backSlotDetails();
        break;

    case 'backUpdateStatus':
        $calendrierController->backUpdateStatus();
        break;

    case 'backBlockSlot':
        $calendrierController->backBlockSlot();
        break;

    case 'backCreateManualRdv':
        $calendrierController->backCreateManual();
        break;

    case 'backRdvList':
        $calendrierController->backList();
        break;

    case 'backEditRdv':
        $calendrierController->backEditRdv();
        break;

    case 'backRdvExportCsv':
        $calendrierController->backExportCsv();
        break;

    case 'backRdvExportPdf':
        $calendrierController->backExportPdf();
        break;

    case 'adminLoyalty':
        $calendrierController->adminLoyalty();
        break;

    case 'manageVehicles':
        $controller->manageVehicles();
        break;

    case 'addVehicleBack':
        $controller->addVehicleBack();
        break;

    case 'vehicleDetail':
        $controller->vehicleDetail();
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
