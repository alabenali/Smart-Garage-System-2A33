<?php
// ============================================
// Smart Garage - Constantes globales
// ============================================

if (file_exists(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}

// Email du gerant (destinataire des rapports)
define('GERANT_EMAIL', 'alaeddine.bensalem@esprit.tn');

// Configuration SMTP (Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'benaliala02@gmail.com');
define('SMTP_PASS', '');
define('SMTP_PORT', 587);

// Cle API OpenAI
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', '');
}
