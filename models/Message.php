<?php
require_once __DIR__ . '/../config/Database.php';

class Message {
    private $db;
    private $table = 'message';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureTable();
    }

    private function ensureTable() {
        $sql = "CREATE TABLE IF NOT EXISTS message (
            id_message INT AUTO_INCREMENT PRIMARY KEY,
            id_intervention INT NOT NULL,
            expediteur ENUM('client', 'admin') NOT NULL,
            contenu TEXT NOT NULL,
            date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_message_intervention_date (id_intervention, date_envoi),
            CONSTRAINT fk_message_intervention FOREIGN KEY (id_intervention)
                REFERENCES intervention(id_intervention) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->exec($sql);
    }
    
    public function create($idIntervention, $expediteur, $contenu) {
        $idIntervention = (int)$idIntervention;
        $expediteur = trim((string)$expediteur);
        $contenu = trim((string)$contenu);

        if ($idIntervention <= 0 || $contenu === '') {
            return false;
        }

        if (!in_array($expediteur, ['client', 'admin'], true)) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (id_intervention, expediteur, contenu) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idIntervention, $expediteur, $contenu]);
    }

    public function listByIntervention($idIntervention) {
        $sql = "SELECT id_message, id_intervention, expediteur, contenu, date_envoi
                FROM {$this->table}
                WHERE id_intervention = ?
                ORDER BY date_envoi ASC, id_message ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$idIntervention]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
