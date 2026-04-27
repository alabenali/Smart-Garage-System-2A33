<?php
// models/Database.php

require_once __DIR__ . '/../config.php';

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

    // Empêcher clone et instanciation directe
    private function __construct() {}
    private function __clone() {}
}