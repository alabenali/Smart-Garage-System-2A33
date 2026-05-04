<?php
// ============================================
// Modèle Panier – Gestion du panier multi-achats
// ============================================

require_once __DIR__ . '/../config/Database.php';

class Panier
{
    private $conn;
    const TVA_RATE = 0.19;
    const FRAIS_LIVRAISON = 15.00;
    const SEUIL_LIVRAISON_GRATUITE = 500.00;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->ensurePanierTables();
    }

    public function getOrCreate($session_id, $id_client = null)
    {
        $stmt = $this->conn->prepare('SELECT * FROM panier WHERE session_id = :sid AND statut = :s LIMIT 1');
        $stmt->execute([':sid' => $session_id, ':s' => 'actif']);
        $panier = $stmt->fetch();

        if (!$panier && $id_client !== null) {
            $stmt2 = $this->conn->prepare('SELECT * FROM panier WHERE id_client = :cid AND statut = :s LIMIT 1');
            $stmt2->execute([':cid' => (int)$id_client, ':s' => 'actif']);
            $panier = $stmt2->fetch();
            if ($panier) {
                $this->conn->prepare('UPDATE panier SET session_id = :sid WHERE id_panier = :id')
                    ->execute([':sid' => $session_id, ':id' => $panier['id_panier']]);
            }
        }

        if (!$panier) {
            $ins = $this->conn->prepare('INSERT INTO panier (session_id, id_client, statut) VALUES (:sid, :cid, :s)');
            $ins->execute([':sid' => $session_id, ':cid' => $id_client, ':s' => 'actif']);
            return ['id_panier' => (int)$this->conn->lastInsertId(), 'session_id' => $session_id, 'id_client' => $id_client, 'statut' => 'actif'];
        }

        if ($id_client !== null && (int)($panier['id_client'] ?? 0) === 0) {
            $this->conn->prepare('UPDATE panier SET id_client = :cid WHERE id_panier = :id')
                ->execute([':cid' => (int)$id_client, ':id' => $panier['id_panier']]);
        }

        return $panier;
    }

    public function addItem($id_panier, $id_piece, $quantite)
    {
        $quantite = max(1, (int)$quantite);
        $piece = $this->getPieceForCart($id_piece);
        if (!$piece) {
            return ['success' => false, 'message' => 'Pièce introuvable.', 'item_count' => $this->getItemCount($id_panier), 'total' => $this->getCartTotal($id_panier)];
        }

        $stock = (int)$piece['quantite_stock'];
        $existing = $this->getExistingItem($id_panier, $id_piece);

        if ($existing) {
            $newQte = min((int)$existing['quantite'] + $quantite, $stock);
            if ($newQte <= 0) {
                return ['success' => false, 'message' => 'Stock insuffisant pour « ' . $piece['nom'] . ' ».', 'item_count' => $this->getItemCount($id_panier), 'total' => $this->getCartTotal($id_panier)];
            }
            $this->conn->prepare('UPDATE panier_items SET quantite = :q WHERE id_panier = :p AND id_piece = :i')
                ->execute([':q' => $newQte, ':p' => $id_panier, ':i' => $id_piece]);
        } else {
            if ($quantite > $stock) {
                return ['success' => false, 'message' => 'Stock insuffisant. Seulement ' . $stock . ' unité(s) disponible(s).', 'item_count' => $this->getItemCount($id_panier), 'total' => $this->getCartTotal($id_panier)];
            }
            $this->conn->prepare('INSERT INTO panier_items (id_panier, id_piece, quantite, prix_snapshot) VALUES (:p, :i, :q, :px)')
                ->execute([':p' => $id_panier, ':i' => $id_piece, ':q' => $quantite, ':px' => $piece['prix_unitaire']]);
        }

        return ['success' => true, 'message' => '« ' . $piece['nom'] . ' » ajouté au panier.', 'item_count' => $this->getItemCount($id_panier), 'total' => $this->getCartTotal($id_panier)];
    }

    public function updateQuantity($id_panier, $id_piece, $nouvelle_quantite)
    {
        $nouvelle_quantite = (int)$nouvelle_quantite;
        if ($nouvelle_quantite <= 0) return $this->removeItem($id_panier, $id_piece);

        $piece = $this->getPieceForCart($id_piece);
        if (!$piece) return ['success' => false, 'message' => 'Pièce introuvable.', 'total' => $this->getCartTotal($id_panier)];

        if ($nouvelle_quantite > (int)$piece['quantite_stock']) {
            return ['success' => false, 'message' => 'Stock insuffisant. Maximum ' . (int)$piece['quantite_stock'] . ' unité(s).', 'total' => $this->getCartTotal($id_panier)];
        }

        $this->conn->prepare('UPDATE panier_items SET quantite = :q WHERE id_panier = :p AND id_piece = :i')
            ->execute([':q' => $nouvelle_quantite, ':p' => $id_panier, ':i' => $id_piece]);

        return ['success' => true, 'message' => 'Quantité mise à jour.', 'total' => $this->getCartTotal($id_panier)];
    }

    public function removeItem($id_panier, $id_piece)
    {
        $this->conn->prepare('DELETE FROM panier_items WHERE id_panier = :p AND id_piece = :i')
            ->execute([':p' => $id_panier, ':i' => $id_piece]);
        return ['success' => true, 'message' => 'Article retiré du panier.', 'total' => $this->getCartTotal($id_panier)];
    }

    public function getItems($id_panier)
    {
        $stmt = $this->conn->prepare(
            'SELECT pi.id_item, pi.id_piece, pi.quantite, pi.prix_snapshot,
                    (pi.quantite * pi.prix_snapshot) AS sous_total,
                    p.nom, p.marque, p.reference, p.categorie, p.image,
                    p.prix_unitaire AS prix_actuel, p.quantite_stock AS stock_actuel
             FROM panier_items pi
             INNER JOIN pieces p ON p.id_piece = pi.id_piece
             WHERE pi.id_panier = :p ORDER BY pi.added_at ASC'
        );
        $stmt->execute([':p' => $id_panier]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['prix_a_change'] = (float)$item['prix_snapshot'] !== (float)$item['prix_actuel'];
        }
        unset($item);
        return $items;
    }

    public function getCartSummary($id_panier)
    {
        $items = $this->getItems($id_panier);
        $nb = 0; $ht = 0.0; $priceChanged = false;
        foreach ($items as $it) {
            $nb += (int)$it['quantite'];
            $ht += (float)$it['sous_total'];
            if ($it['prix_a_change']) $priceChanged = true;
        }
        $tva = round($ht * self::TVA_RATE, 2);
        $liv = empty($items) ? 0.00 : ($ht >= self::SEUIL_LIVRAISON_GRATUITE ? 0.00 : self::FRAIS_LIVRAISON);
        $ttc = empty($items) ? 0.00 : round($ht + $tva + $liv, 2);
        return ['items' => $items, 'nb_articles' => $nb, 'sous_total_ht' => $ht, 'tva' => $tva, 'frais_livraison' => $liv, 'total_ttc' => $ttc, 'has_price_changes' => $priceChanged];
    }

    public function clear($id_panier)
    {
        $this->conn->prepare('DELETE FROM panier_items WHERE id_panier = :p')->execute([':p' => $id_panier]);
        return ['success' => true, 'message' => 'Panier vidé.'];
    }

    public function checkStock($id_panier)
    {
        $items = $this->getItems($id_panier);
        foreach ($items as $item) {
            if ((int)$item['quantite'] > (int)$item['stock_actuel']) {
                return ['ok' => false, 'piece_manquante' => $item['nom'], 'dispo' => (int)$item['stock_actuel'], 'demande' => (int)$item['quantite']];
            }
        }
        return ['ok' => true];
    }

    public function convertToCommande($id_panier, $client_data, $payment_method)
    {
        try {
            $this->conn->beginTransaction();

            $stockCheck = $this->checkStock($id_panier);
            if (!$stockCheck['ok']) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Stock insuffisant pour « ' . $stockCheck['piece_manquante'] . ' ». Disponible : ' . $stockCheck['dispo'] . ', demandé : ' . $stockCheck['demande'] . '.'];
            }

            $summary = $this->getCartSummary($id_panier);
            if (empty($summary['items'])) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Le panier est vide.'];
            }

            $payLabel = $payment_method === 'konnect' ? 'Konnect' : 'Paiement a la livraison';
            $payStatus = $payment_method === 'konnect' ? 'En attente' : 'Non paye';
            $statut = $payment_method === 'konnect' ? 'Paiement initie' : 'En attente';
            $first = $summary['items'][0];

            $insCmd = $this->conn->prepare(
                'INSERT INTO commandes (id_piece, nom_client, prenom_client, telephone, quantite, montant_total, statut, payment_method, payment_status, montant_ht, tva, frais_livraison, montant_ttc, source, id_panier, note)
                 VALUES (:ip, :nc, :pc, :tel, :q, :mt, :st, :pm, :ps, :mht, :tva, :fl, :ttc, :src, :pan, :note)'
            );
            $insCmd->execute([
                ':ip' => (int)$first['id_piece'], ':nc' => $client_data['nom_client'] ?? '', ':pc' => $client_data['prenom_client'] ?? '',
                ':tel' => $client_data['telephone'] ?? '', ':q' => $summary['nb_articles'], ':mt' => $summary['total_ttc'],
                ':st' => $statut, ':pm' => $payLabel, ':ps' => $payStatus,
                ':mht' => $summary['sous_total_ht'], ':tva' => $summary['tva'], ':fl' => $summary['frais_livraison'],
                ':ttc' => $summary['total_ttc'], ':src' => 'panier', ':pan' => $id_panier, ':note' => $client_data['note'] ?? null,
            ]);
            $id_commande = (int)$this->conn->lastInsertId();

            $insIt = $this->conn->prepare('INSERT INTO commande_items (id_commande, id_piece, quantite, prix_unitaire) VALUES (:c, :i, :q, :p)');
            $updSt = $this->conn->prepare('UPDATE pieces SET quantite_stock = quantite_stock - :q WHERE id_piece = :i');

            foreach ($summary['items'] as $item) {
                $insIt->execute([':c' => $id_commande, ':i' => (int)$item['id_piece'], ':q' => (int)$item['quantite'], ':p' => (float)$item['prix_snapshot']]);
                $updSt->execute([':q' => (int)$item['quantite'], ':i' => (int)$item['id_piece']]);
            }

            $this->conn->prepare('UPDATE panier SET statut = :s WHERE id_panier = :p')->execute([':s' => 'converti', ':p' => $id_panier]);
            $this->conn->commit();

            return ['success' => true, 'id_commande' => $id_commande, 'montant_ttc' => $summary['total_ttc'], 'nb_articles' => $summary['nb_articles'], 'message' => 'Commande #' . $id_commande . ' créée avec succès.'];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            throw $e;
        }
    }

    private function getPieceForCart($id_piece)
    {
        $stmt = $this->conn->prepare('SELECT id_piece, nom, marque, reference, prix_unitaire, quantite_stock FROM pieces WHERE id_piece = :i');
        $stmt->execute([':i' => (int)$id_piece]);
        $r = $stmt->fetch();
        return $r ?: false;
    }

    private function getExistingItem($id_panier, $id_piece)
    {
        $stmt = $this->conn->prepare('SELECT * FROM panier_items WHERE id_panier = :p AND id_piece = :i');
        $stmt->execute([':p' => $id_panier, ':i' => $id_piece]);
        $r = $stmt->fetch();
        return $r ?: false;
    }

    private function getItemCount($id_panier)
    {
        $stmt = $this->conn->prepare('SELECT COALESCE(SUM(quantite), 0) FROM panier_items WHERE id_panier = :p');
        $stmt->execute([':p' => $id_panier]);
        return (int)$stmt->fetchColumn();
    }

    private function getCartTotal($id_panier)
    {
        $stmt = $this->conn->prepare('SELECT COALESCE(SUM(quantite * prix_snapshot), 0) FROM panier_items WHERE id_panier = :p');
        $stmt->execute([':p' => $id_panier]);
        return (float)$stmt->fetchColumn();
    }

    private function ensurePanierTables()
    {
        // Vérifier si les tables existent, sinon les créer directement en DDL
        foreach (['panier', 'panier_items', 'commande_items'] as $t) {
            try {
                $this->conn->query("SELECT 1 FROM {$t} LIMIT 1");
            } catch (Throwable $e) {
                // Au moins une table manquante → créer toutes les tables
                $this->runMigration();
                break;
            }
        }
        $this->ensureCommandeColumns();
    }

    private function runMigration()
    {
        $ddl = [
            "CREATE TABLE IF NOT EXISTS panier (
                id_panier  INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(100) NOT NULL,
                id_client  INT DEFAULT NULL,
                statut     ENUM('actif','converti','abandonne') DEFAULT 'actif',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_session (session_id),
                INDEX idx_client_statut (id_client, statut)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS panier_items (
                id_item       INT AUTO_INCREMENT PRIMARY KEY,
                id_panier     INT NOT NULL,
                id_piece      INT NOT NULL,
                quantite      INT NOT NULL DEFAULT 1,
                prix_snapshot DECIMAL(10,2) NOT NULL,
                added_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_panier) REFERENCES panier(id_panier) ON DELETE CASCADE,
                FOREIGN KEY (id_piece)  REFERENCES pieces(id_piece)  ON DELETE CASCADE,
                UNIQUE KEY unique_item (id_panier, id_piece)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS commande_items (
                id_item       INT AUTO_INCREMENT PRIMARY KEY,
                id_commande   INT NOT NULL,
                id_piece      INT NOT NULL,
                quantite      INT NOT NULL,
                prix_unitaire DECIMAL(10,2) NOT NULL,
                sous_total    DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire) STORED,
                FOREIGN KEY (id_commande) REFERENCES commandes(id_commande) ON DELETE CASCADE,
                FOREIGN KEY (id_piece)    REFERENCES pieces(id_piece)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($ddl as $sql) {
            try {
                $this->conn->exec($sql);
            } catch (Throwable $ex) {
                // Ignorer si la table existe déjà ou autre erreur non bloquante
            }
        }
    }

    private function ensureCommandeColumns()
    {
        $cols = [
            'montant_ht' => "ALTER TABLE commandes ADD COLUMN montant_ht DECIMAL(10,2) DEFAULT 0",
            'tva' => "ALTER TABLE commandes ADD COLUMN tva DECIMAL(10,2) DEFAULT 0",
            'frais_livraison' => "ALTER TABLE commandes ADD COLUMN frais_livraison DECIMAL(10,2) DEFAULT 15.00",
            'montant_ttc' => "ALTER TABLE commandes ADD COLUMN montant_ttc DECIMAL(10,2) DEFAULT 0",
            'source' => "ALTER TABLE commandes ADD COLUMN source ENUM('direct','panier','intervention') DEFAULT 'direct'",
            'id_panier' => "ALTER TABLE commandes ADD COLUMN id_panier INT DEFAULT NULL",
            'note' => "ALTER TABLE commandes ADD COLUMN note TEXT DEFAULT NULL",
        ];
        foreach ($cols as $cn => $sql) {
            try {
                $ck = $this->conn->query("SHOW COLUMNS FROM commandes LIKE '{$cn}'");
                if (!$ck->fetch()) $this->conn->exec($sql);
            } catch (Throwable $e) {}
        }
    }
}
