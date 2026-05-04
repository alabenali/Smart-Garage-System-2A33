<?php

// Fonction pour parser un fichier .env simple si nécessaire
if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Charger .env depuis la racine du projet
loadEnv(__DIR__ . '/../.env');

$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/samrtnour/samrt%20nour';

return [
    'bot_token'        => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '8650376157:AAGqZq89HQzttYHlFxISxXjOqvBWQoypd-M',
    'admin_chat_id'    => $_ENV['TELEGRAM_ADMIN_CHAT_ID'] ?? '6672388992',
    'webhook_token'    => $_ENV['TELEGRAM_WEBHOOK_TOKEN'] ?? 'super-secret-webhook-token',
    'rate_limit_ms'    => 1000,       // délai entre messages en ms
    'alert_cooldown_h' => 24,         // heures entre deux mêmes alertes
    'webhook_url'      => $appUrl . '/webhook/telegram_webhook.php',
];

// Helper pour setWebhook
function setup_telegram_webhook()
{
    $config = require __DIR__ . '/telegram.php';
    $url = "https://api.telegram.org/bot{$config['bot_token']}/setWebhook";
    $webhookUrl = $config['webhook_url'] . '?token=' . $config['webhook_token'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "?url=" . urlencode($webhookUrl));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
