<?php
// ============================================
// Smart Garage - Constantes globales
// ============================================

if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}

// Email du gerant (destinataire des rapports)
if (!defined('GERANT_EMAIL')) {
    define('GERANT_EMAIL', getenv('SMART_GARAGE_GERANT_EMAIL') ?: 'alaeddine.bensalem@esprit.tn');
}

// Configuration SMTP (Gmail)
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMART_GARAGE_SMTP_HOST') ?: 'smtp.gmail.com');
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', getenv('SMART_GARAGE_SMTP_USER') ?: 'benaliala02@gmail.com');
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', getenv('SMART_GARAGE_SMTP_PASS') ?: '');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int) (getenv('SMART_GARAGE_SMTP_PORT') ?: 587));
}

// Cle API OpenAI
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', '');
}
