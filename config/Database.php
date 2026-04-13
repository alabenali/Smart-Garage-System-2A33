<?php
// ============================================
// PDO Database Connection (Singleton Pattern)
// ============================================

class Database {
    private static $instance = null;
    private $connection;

    private $host = 'localhost';
    private $dbname = 'smart_garage_system';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    // Private constructor – prevents direct instantiation
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

    // Singleton accessor
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Return the PDO connection
    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}
}
