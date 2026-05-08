<?php

/**
 * Configuration Telegram Bot API
 * Ne jamais exposer ces données côté front
 */
return [
    'bot_token' => getenv('TELEGRAM_BOT_TOKEN') ?: '',
    'chat_id'   => getenv('TELEGRAM_CHAT_ID') ?: '',
];
