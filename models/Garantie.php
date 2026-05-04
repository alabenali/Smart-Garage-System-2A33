<?php
// ============================================
// Modèle Garantie – Gestion des garanties pièces
// ============================================

require_once __DIR__ . '/../config/Database.php';

class Garantie
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->ensureGarantieTable();
    }

    // ── Créer une garantie après commande ──
    public function createFromCommande(int $id_commande, int $id_piece, int $id_client, string $date_pose, int $duree_mois = 1, ?int $km_pose = null, string $technicien = ''): int
    {
        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO garanties (id_commande, id_piece, id_client, date_pose, duree_mois, kilometrage_pose, technicien)
                 VALUES (:cmd, :piece, :client, :date, :duree, :km, :tech)'
            );
            $stmt->execute([
                ':cmd'    => $id_commande,
                ':piece'  => $id_piece,
                ':client' => $id_client,
                ':date'   => $date_pose,
                ':duree'  => $duree_mois,
                ':km'     => $km_pose,
                ':tech'   => $technicien !== '' ? $technicien : null,
            ]);
            return (int) $this->conn->lastInsertId();
        } catch (Throwable $e) {
            throw new RuntimeException('Impossible de créer la garantie : ' . $e->getMessage());
        }
    }

    // ── Garanties d'un client ──
    public function getByClient(int $id_client): array
    {
        try {
            $stmt = $this->conn->prepare(
                'SELECT * FROM vue_garanties WHERE id_client = :id ORDER BY date_expiration ASC'
            );
            $stmt->execute([':id' => $id_client]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    // ── Alertes à envoyer (pour le cron) ──
    public function getAlertesToSend(): array
    {
        try {
            $stmt = $this->conn->query('SELECT * FROM vue_alertes_a_envoyer ORDER BY jours_restants ASC');
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    // ── Marquer une alerte comme envoyée ──
    public function markAlertSent(int $id_garantie, string $type): void
    {
        $columns = [
            'ALERTE_30J' => 'alerte_30j_envoyee',
            'ALERTE_7J'  => 'alerte_7j_envoyee',
            'EXPIREE'    => 'alerte_expir_envoyee',
        ];

        if (!isset($columns[$type])) {
            throw new InvalidArgumentException('Type d\'alerte invalide : ' . $type);
        }

        $col = $columns[$type];
        try {
            $stmt = $this->conn->prepare("UPDATE garanties SET {$col} = 1 WHERE id_garantie = :id");
            $stmt->execute([':id' => $id_garantie]);
        } catch (Throwable $e) {
            throw new RuntimeException('Impossible de marquer l\'alerte : ' . $e->getMessage());
        }
    }

    // ── Expirer les garanties dépassées ──
    public function expireOldGaranties(): int
    {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE garanties SET statut = 'expiree' WHERE statut = 'active' AND date_expiration < CURDATE()"
            );
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Throwable $e) {
            return 0;
        }
    }

    // ── Garanties expirant bientôt ──
    public function getExpiringSoon(int $jours = 30): array
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT * FROM vue_garanties WHERE statut = 'active' AND jours_restants BETWEEN 0 AND :j ORDER BY jours_restants ASC"
            );
            $stmt->execute([':j' => $jours]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    // ── Statistiques garanties ──
    public function getStats(): array
    {
        try {
            $stmt = $this->conn->query(
                "SELECT
                    COUNT(*)                                                    AS total,
                    SUM(CASE WHEN statut = 'active' THEN 1 ELSE 0 END)          AS actives,
                    SUM(CASE WHEN statut = 'expiree' THEN 1 ELSE 0 END)         AS expirees,
                    SUM(CASE WHEN statut = 'remplacee' THEN 1 ELSE 0 END)       AS remplacees,
                    SUM(CASE WHEN statut = 'active' AND DATEDIFF(date_expiration, CURDATE()) <= 30 AND DATEDIFF(date_expiration, CURDATE()) >= 0 THEN 1 ELSE 0 END) AS expirent_bientot
                 FROM garanties"
            );
            $row = $stmt->fetch();
            return [
                'total'           => (int) ($row['total'] ?? 0),
                'actives'         => (int) ($row['actives'] ?? 0),
                'expirees'        => (int) ($row['expirees'] ?? 0),
                'remplacees'      => (int) ($row['remplacees'] ?? 0),
                'expirent_bientot'=> (int) ($row['expirent_bientot'] ?? 0),
            ];
        } catch (Throwable $e) {
            return ['total' => 0, 'actives' => 0, 'expirees' => 0, 'remplacees' => 0, 'expirent_bientot' => 0];
        }
    }

    // ── Marquer une garantie comme remplacée ──
    public function markRemplacee(int $id_garantie, string $notes = ''): void
    {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE garanties SET statut = 'remplacee', notes = CONCAT(COALESCE(notes,''), :n) WHERE id_garantie = :id"
            );
            $noteText = $notes !== '' ? "\n[Remplacement " . date('d/m/Y') . '] ' . $notes : '';
            $stmt->execute([':n' => $noteText, ':id' => $id_garantie]);
        } catch (Throwable $e) {
            throw new RuntimeException('Impossible de marquer la garantie : ' . $e->getMessage());
        }
    }

    // ── Récupérer une garantie par ID ──
    public function getById(int $id)
    {
        try {
            $stmt = $this->conn->prepare('SELECT * FROM vue_garanties WHERE id_garantie = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ?: false;
        } catch (Throwable $e) {
            return false;
        }
    }

    // ── Toutes les garanties (avec filtre optionnel) ──
    public function getAll(string $filtre = 'toutes'): array
    {
        try {
            $where = '';
            switch ($filtre) {
                case 'actives':
                    $where = "WHERE statut = 'active' AND jours_restants > 30";
                    break;
                case 'bientot':
                    $where = "WHERE statut = 'active' AND jours_restants BETWEEN 0 AND 30";
                    break;
                case 'expirees':
                    $where = "WHERE statut = 'expiree' OR (statut = 'active' AND jours_restants < 0)";
                    break;
                case 'remplacees':
                    $where = "WHERE statut = 'remplacee'";
                    break;
            }
            $stmt = $this->conn->query("SELECT * FROM vue_garanties {$where} ORDER BY date_expiration ASC");
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    // ── Récupérer la durée garantie d'une pièce ──
    public function getDureePiece(int $id_piece): int
    {
        try {
            $stmt = $this->conn->prepare('SELECT garantie_mois FROM pieces WHERE id_piece = :id');
            $stmt->execute([':id' => $id_piece]);
            $row = $stmt->fetch();
            return $row ? (int) $row['garantie_mois'] : 1;
        } catch (Throwable $e) {
            return 1;
        }
    }

    // ── Auto-migration ──
    private function ensureGarantieTable()
    {
        try {
            $this->conn->query('SELECT 1 FROM garanties LIMIT 1');
        } catch (Throwable $e) {
            $this->runMigration();
        }
    }

    private function runMigration()
    {
        // Ajouter colonne garantie_mois sur pieces
        try {
            $ck = $this->conn->query("SHOW COLUMNS FROM pieces LIKE 'garantie_mois'");
            if (!$ck->fetch()) {
                $this->conn->exec("ALTER TABLE pieces ADD COLUMN garantie_mois INT DEFAULT 1 COMMENT 'Duree garantie en mois'");
            }
        } catch (Throwable $e) {}

        // Créer la table garanties
        try {
            $this->conn->exec("CREATE TABLE IF NOT EXISTS garanties (
                id_garantie          INT AUTO_INCREMENT PRIMARY KEY,
                id_commande          INT NOT NULL,
                id_piece             INT NOT NULL,
                id_client            INT DEFAULT NULL,
                date_pose            DATE NOT NULL,
                duree_mois           INT NOT NULL DEFAULT 1,
                date_expiration      DATE GENERATED ALWAYS AS (DATE_ADD(date_pose, INTERVAL duree_mois MONTH)) STORED,
                kilometrage_pose     INT DEFAULT NULL,
                technicien           VARCHAR(100) DEFAULT NULL,
                statut               ENUM('active','expiree','remplacee') DEFAULT 'active',
                alerte_30j_envoyee   TINYINT(1) DEFAULT 0,
                alerte_7j_envoyee    TINYINT(1) DEFAULT 0,
                alerte_expir_envoyee TINYINT(1) DEFAULT 0,
                notes                TEXT DEFAULT NULL,
                created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_expiration_statut (date_expiration, statut),
                INDEX idx_client (id_client),
                FOREIGN KEY (id_commande) REFERENCES commandes(id_commande) ON DELETE CASCADE,
                FOREIGN KEY (id_piece)    REFERENCES pieces(id_piece)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {}

        // Créer les vues
        try {
            $this->conn->exec("CREATE OR REPLACE VIEW vue_garanties AS
                SELECT g.id_garantie, g.id_commande, g.id_piece, g.id_client, g.date_pose,
                       g.date_expiration, g.duree_mois, g.statut, g.kilometrage_pose, g.technicien,
                       g.alerte_30j_envoyee, g.alerte_7j_envoyee, g.alerte_expir_envoyee,
                       g.notes, g.created_at,
                       DATEDIFF(g.date_expiration, CURDATE()) AS jours_restants,
                       p.nom AS nom_piece, p.reference AS ref_piece, p.marque AS marque_piece, p.categorie AS categorie_piece,
                       c.nom_client, c.prenom_client,
                       CONCAT(COALESCE(c.prenom_client,''), ' ', COALESCE(c.nom_client,'')) AS nom_complet,
                       c.telephone, c.telephone AS email
                FROM garanties g
                INNER JOIN pieces p    ON p.id_piece = g.id_piece
                INNER JOIN commandes c ON c.id_commande = g.id_commande");
        } catch (Throwable $e) {}

        try {
            $this->conn->exec("CREATE OR REPLACE VIEW vue_alertes_a_envoyer AS
                SELECT vg.*,
                    CASE
                        WHEN vg.jours_restants <= 0 AND vg.alerte_expir_envoyee = 0 THEN 'EXPIREE'
                        WHEN vg.jours_restants <= 7 AND vg.jours_restants > 0 AND vg.alerte_7j_envoyee = 0 THEN 'ALERTE_7J'
                        WHEN vg.jours_restants <= 30 AND vg.jours_restants > 7 AND vg.alerte_30j_envoyee = 0 THEN 'ALERTE_30J'
                    END AS type_alerte
                FROM vue_garanties vg
                WHERE vg.statut = 'active'
                  AND ((vg.jours_restants <= 30 AND vg.jours_restants > 7 AND vg.alerte_30j_envoyee = 0)
                    OR (vg.jours_restants <= 7  AND vg.jours_restants > 0 AND vg.alerte_7j_envoyee = 0)
                    OR (vg.jours_restants <= 0  AND vg.alerte_expir_envoyee = 0))");
        } catch (Throwable $e) {}
    }
}
