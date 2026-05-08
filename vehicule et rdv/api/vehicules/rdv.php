<?php

declare(strict_types=1);

require_once __DIR__ . '/../../controllers/IntegrationApiController.php';

$controller = new IntegrationApiController();
$controller->vehicleRendezvous();
