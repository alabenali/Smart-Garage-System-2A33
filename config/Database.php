<?php
// ============================================
// PDO Database Connection (Singleton Pattern)
// Optional credentials: project root .env (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET)
// ============================================

class Database {
    private static $instance = null;
    private $connection;

    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    private static function loadEnvFile() {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_readable($path)) {
            return;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\"'");
            if ($k === '') {
                continue;
            }
            if (!array_key_exists($k, $_ENV)) {
                $_ENV[$k] = $v;
            }
            if (getenv($k) === false) {
                putenv($k . '=' . $v);
            }
        }
    }

    private static function env($key, $default) {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        $v = getenv($key);
        return $v !== false ? $v : $default;
    }

    private function __construct() {
        self::loadEnvFile();

        $this->host = self::env('DB_HOST', 'localhost');
        $this->dbname = self::env('DB_NAME', 'smart_garage_system');
        $this->username = self::env('DB_USER', 'root');
        $this->password = self::env('DB_PASS', '');
        $this->charset = self::env('DB_CHARSET', 'utf8mb4');

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    private function __clone() {}
}
