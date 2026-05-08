<?php
// config.php - Configuration générale et connexion PDO

define('DB_HOST',    'localhost');
define('DB_NAME',    'garage1');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Démarrage de session global
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Classe Database ───────────────────────────────────────────────────────────
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die('<h2 style="color:red;font-family:sans-serif;">Erreur de connexion : ' . $e->getMessage() . '</h2>');
            }
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}

// ── reCAPTCHA v3 (INVISIBLE — aucune image, aucun clic) ───────────────────────
// 1. Allez sur https://www.google.com/recaptcha/admin/create
// 2. Choisissez le TYPE : "Score (v3)"  ← important, pas v2 !
// 3. Ajoutez votre domaine (ex: localhost ou votre domaine en prod)
// 4. Copiez les deux clés ci-dessous
// ── Google Gemini API (AI Helper — 100% Gratuit) ───────────────────────────
// 1. Allez sur https://aistudio.google.com/app/apikey
// 2. Cliquez "Create API key" → copiez la clé ci-dessous


// ── Google OAuth 2.0 ──────────────────────────────────────────────────────────
// 1. Allez sur https://console.cloud.google.com/
// 2. Créez un projet → APIs & Services → Identifiants → Créer des identifiants → ID client OAuth 2.0
// 3. Type : Application Web
// 4. URI de redirection autorisée : http://localhost/projet_final/controllers/UserController.php?action=googleCallback
// 5. Copiez les deux valeurs ci-dessous
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI',  'http://localhost/projet_final/controllers/UserController.php?action=googleCallback');

define('RECAPTCHA_ENABLED',    true);
define('RECAPTCHA_VERSION',    'v3');                        // ✅ NOUVEAU : indique v3
define('RECAPTCHA_SITE_KEY', '[RECAPTCHA_SITE_KEY]');
define('RECAPTCHA_SECRET_KEY', '[RECAPTCHA_SECRET_KEY]');
define('RECAPTCHA_MIN_SCORE',  0.5);                        // ✅ Seuil : 0.0 (bot) → 1.0 (humain)
// ── Google Gemini API (gratuit) ───────────────────────────────────────────────
// 1. Allez sur https://aistudio.google.com/apikey
// 2. Cliquez "Create API Key" → copiez la clé ci-dessous
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY');

// ── Groq API (gratuit, rapide) ────────────────────────────────────────────────
define('GROQ_API_KEY', 'YOUR_GROQ_API_KEY');

