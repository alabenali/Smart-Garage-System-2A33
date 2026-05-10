<?php

declare(strict_types=1);

$bridgeDir = dirname(__DIR__);
$projectRoot = dirname($bridgeDir);

$paths = [
    'samrt_nour.index' => $bridgeDir . DIRECTORY_SEPARATOR . 'samrt nour' . DIRECTORY_SEPARATOR . 'index.php',
    'client.UserController' => $projectRoot . DIRECTORY_SEPARATOR . 'client' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'UserController.php',
    'vehicule_et_rdv.index' => $projectRoot . DIRECTORY_SEPARATOR . 'vehicule et rdv' . DIRECTORY_SEPARATOR . 'index.php',
];

$exists = [];
foreach ($paths as $key => $path) {
    $exists[$key] = is_file($path);
}

$result = [
    'ok' => true,
    'timestamp' => gmdate('c'),
    'exists' => $exists,
];

fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);
