<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/UrgenceService.php';

$service = new UrgenceService();
$config = $service->getConfig();
$logPath = (string) ($config['broadcast']['sse_log'] ?? __DIR__ . '/../storage/urgency-events.log');

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

if (!is_file($logPath)) {
    @touch($logPath);
}

$fp = fopen($logPath, 'r');
if (!$fp) {
    echo "event: error\n";
    echo 'data: {"message":"stream unavailable"}' . "\n\n";
    flush();
    exit;
}

fseek($fp, 0, SEEK_END);

while (!connection_aborted()) {
    $line = fgets($fp);
    if ($line === false) {
        clearstatcache();
        usleep(500000);
        continue;
    }

    $line = trim($line);
    if ($line === '') {
        continue;
    }

    echo "event: rdv_urgence_updated\n";
    echo "data: {$line}\n\n";

    if (function_exists('ob_flush')) {
        @ob_flush();
    }
    flush();
}

fclose($fp);
