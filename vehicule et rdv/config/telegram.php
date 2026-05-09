<?php

/**
 * Configuration Telegram Bot API
 * Ne jamais exposer ces données côté front
 */
if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}

return [
    'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: (defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : ''),
    'chat_id'   => getenv('TELEGRAM_CHAT_ID') ?: (defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : ''),
];
