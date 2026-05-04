<?php

class StockAlertModel
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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
}
