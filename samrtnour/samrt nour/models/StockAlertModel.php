<?php

class StockAlertModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    public function getPiecesEnRupture(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM pieces WHERE quantite_stock <= 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPiecesStockFaible(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM pieces WHERE quantite_stock > 0 AND quantite_stock <= seuil_alerte");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alerteDejaEnvoyee(int $id_piece, string $type): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM telegram_alerts_log 
            WHERE id_piece = ? AND type_alerte = ? AND resolue = 0 
            AND message_id IS NOT NULL
            AND envoyee_le >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$id_piece, $type]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function logAlerteEnvoyee(int $id_piece, string $type, int $stock, ?int $messageId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO telegram_alerts_log (id_piece, type_alerte, stock_au_moment, message_id, envoyee_le) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$id_piece, $type, $stock, $messageId]);
    }

    public function marquerResolue(int $id_piece, string $type): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE telegram_alerts_log SET resolue = 1, resolue_le = NOW() 
            WHERE id_piece = ? AND type_alerte = ? AND resolue = 0
        ");
        $stmt->execute([$id_piece, $type]);
    }

    public function getAlertesNonResolues(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM telegram_alerts_log WHERE resolue = 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStockStats(): array
    {
        $stats = [
            'nb_ruptures' => 0,
            'nb_faible' => 0,
            'nb_ok' => 0,
            'valeur_totale' => 0.0,
            'pieces_critiques' => []
        ];

        $stmt = $this->pdo->query("
            SELECT 
                SUM(CASE WHEN quantite_stock <= 0 THEN 1 ELSE 0 END) as nb_ruptures,
                SUM(CASE WHEN quantite_stock > 0 AND quantite_stock <= seuil_alerte THEN 1 ELSE 0 END) as nb_faible,
                SUM(CASE WHEN quantite_stock > seuil_alerte THEN 1 ELSE 0 END) as nb_ok,
                SUM(quantite_stock * prix_unitaire) as valeur_totale
            FROM pieces
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['nb_ruptures'] = (int)$row['nb_ruptures'];
            $stats['nb_faible'] = (int)$row['nb_faible'];
            $stats['nb_ok'] = (int)$row['nb_ok'];
            $stats['valeur_totale'] = (float)$row['valeur_totale'];
        }

        $stmtCrit = $this->pdo->query("SELECT id_piece, nom, reference FROM pieces WHERE quantite_stock <= 0");
        $stats['pieces_critiques'] = $stmtCrit->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    public function getPieceById(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pieces WHERE id_piece = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function ensureTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS telegram_alerts_log (
              id INT AUTO_INCREMENT PRIMARY KEY,
              id_piece INT NOT NULL,
              type_alerte ENUM('rupture','stock_faible') NOT NULL,
              stock_au_moment INT NOT NULL,
              message_id BIGINT DEFAULT NULL,
              envoyee_le DATETIME DEFAULT CURRENT_TIMESTAMP,
              resolue TINYINT(1) DEFAULT 0,
              resolue_le DATETIME DEFAULT NULL,
              FOREIGN KEY (id_piece) REFERENCES pieces(id_piece) ON DELETE CASCADE,
              INDEX idx_piece_type (id_piece, type_alerte, resolue)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $this->pdo->exec($sql);
    }
}
