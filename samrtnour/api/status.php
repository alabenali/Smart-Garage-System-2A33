<?php

declare(strict_types=1);

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

$bridgeDir = dirname(__DIR__);
$projectRoot = dirname($bridgeDir);

$paths = [
    'samrt_nour.index' => $bridgeDir . DIRECTORY_SEPARATOR . 'samrt nour' . DIRECTORY_SEPARATOR . 'index.php',
    'samrt_nour.webhook.telegram' => $bridgeDir . DIRECTORY_SEPARATOR . 'samrt nour' . DIRECTORY_SEPARATOR . 'webhook' . DIRECTORY_SEPARATOR . 'telegram_webhook.php',
    'client.UserController' => $projectRoot . DIRECTORY_SEPARATOR . 'client' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'UserController.php',
    'client.AdminController' => $projectRoot . DIRECTORY_SEPARATOR . 'client' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AdminController.php',
    'vehicule_et_rdv.index' => $projectRoot . DIRECTORY_SEPARATOR . 'vehicule et rdv' . DIRECTORY_SEPARATOR . 'index.php',
];

$exists = [];
foreach ($paths as $key => $path) {
    $exists[$key] = is_file($path);
}

$response = [
    'ok' => true,
    'timestamp' => gmdate('c'),
    'project_root' => $projectRoot,
    'exists' => $exists,
    'links' => [
        'samrtnour' => '../samrtnour/index.php',
        'samrt_nour' => '../samrtnour/' . rawurlencode('samrt nour') . '/index.php',
        'client_frontoffice' => '../client/controllers/UserController.php?action=showLogin',
        'client_backoffice' => '../client/controllers/AdminController.php?action=showLogin',
        'vehicule_et_rdv' => '../vehicule%20et%20rdv/index.php',
    ],
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
