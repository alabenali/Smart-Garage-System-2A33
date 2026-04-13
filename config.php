<?php
// config.php - Connexion PDO à la base de données garage1

define('DB_HOST', 'localhost');
define('DB_NAME', 'garage1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Démarrage de session global
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}