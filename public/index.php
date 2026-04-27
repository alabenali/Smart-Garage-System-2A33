<?php
declare(strict_types=1);

require_once __DIR__ . '/../controller/PageController.php';

use Controller\PageController;

(new PageController())->handleRequest();
