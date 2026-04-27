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

// ── reCAPTCHA v2 ──────────────────────────────────────────────────────────────
// 1. Obtenez vos clés sur https://www.google.com/recaptcha/admin/create
// 2. Remplacez les valeurs ci-dessous
// 3. Laissez RECAPTCHA_ENABLED à true
define('RECAPTCHA_ENABLED',    true);                     // Active/désactive le système
define('RECAPTCHA_SITE_KEY',   '6LfDBswsAAAAAKmlGgnw2VEE1zmDS6CBNgziceXf'); // Clé réelle utilisateur
define('RECAPTCHA_SECRET_KEY', '6LfDBswsAAAAAG7OUz8XuQeQs324OaujIa8Ywhti'); // Clé réelle utilisateur

