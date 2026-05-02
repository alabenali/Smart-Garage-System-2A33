<?php

declare(strict_types=1);

require_once __DIR__ . '/../../controllers/CalendrierController.php';

$controller = new CalendrierController();
$controller->apiRendezVous();
