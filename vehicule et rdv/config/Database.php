<?php
// ============================================
// Connexion à la base de données via PDO (Modèle Singleton)
// ============================================

class Database {
    private static $instance = null;
    private $connection;

    private $host = 'localhost';
    private $dbname = 'smart_garage';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    // Constructeur privé – empêche l'instanciation directe
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    // Accesseur de l'instance unique (Singleton)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Renvoie la connexion PDO
    public function getConnection() {
        return $this->connection;
    }

    // Empêche le clonage
    private function __clone() {}
}
