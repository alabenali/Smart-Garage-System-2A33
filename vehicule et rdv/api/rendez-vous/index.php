<?php

declare(strict_types=1);

require_once __DIR__ . '/../../controllers/CalendrierController.php';

$pathInfo = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
if ($pathInfo === '') {
    $uriPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $marker = '/api/rendez-vous/';
    $pos = is_string($uriPath) ? strpos($uriPath, $marker) : false;
    if ($pos !== false) {
        $pathInfo = trim(substr($uriPath, $pos + strlen($marker)), '/');
    }
}

if ($pathInfo !== '') {
    $parts = explode('/', $pathInfo);
    if (($parts[0] ?? '') === 'urgents') {
        $_GET['scope'] = 'urgents';
    } elseif (ctype_digit((string) ($parts[0] ?? ''))) {
        $_GET['id'] = (int) $parts[0];
    }
}

$controller = new CalendrierController();
$controller->apiRendezVous();
