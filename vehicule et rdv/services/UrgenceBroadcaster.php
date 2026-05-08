<?php

declare(strict_types=1);

class UrgenceBroadcaster
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function broadcast(array $payload): void
    {
        $driver = (string) ($this->config['driver'] ?? 'sse');
        if ($driver !== 'sse') {
            return;
        }

        $this->appendToLog($payload);
    }

    private function appendToLog(array $payload): void
    {
        $logPath = (string) ($this->config['sse_log'] ?? __DIR__ . '/../storage/urgency-events.log');
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $entry = [
            'event' => 'rdv_urgence_updated',
            'ts' => time(),
            'data' => $payload,
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            return;
        }

        $line .= "\n";
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
