<?php
require_once __DIR__ . '/../config/Database.php';

class TypeIntervention {
    private $db;
    private $table = 'type_intervention';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $sql = "SELECT id_type, nom, description, prix FROM {$this->table} ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($idType) {
        $sql = "SELECT id_type, nom, description, prix FROM {$this->table} WHERE id_type = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$idType]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
