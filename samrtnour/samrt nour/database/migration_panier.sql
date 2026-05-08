-- ============================================
-- Migration : Système de Panier Multi-Achats
-- Compatible MySQL 8.0+ / MariaDB 10.6+
-- ============================================

-- ──────────────────────────────────────────────
-- A) NOUVELLES TABLES
-- ──────────────────────────────────────────────

-- Table panier : stocke les paniers actifs, convertis ou abandonnés
CREATE TABLE IF NOT EXISTS panier (
    id_panier  INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    id_client  INT DEFAULT NULL,
    statut     ENUM('actif','converti','abandonne') DEFAULT 'actif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_client_statut (id_client, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table panier_items : lignes du panier avec prix snapshot
CREATE TABLE IF NOT EXISTS panier_items (
    id_item       INT AUTO_INCREMENT PRIMARY KEY,
    id_panier     INT NOT NULL,
    id_piece      INT NOT NULL,
    quantite      INT NOT NULL DEFAULT 1,
    prix_snapshot DECIMAL(10,2) NOT NULL,
    added_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_panier) REFERENCES panier(id_panier) ON DELETE CASCADE,
    FOREIGN KEY (id_piece)  REFERENCES pieces(id_piece)  ON DELETE CASCADE,
    UNIQUE KEY unique_item (id_panier, id_piece)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table commande_items : détail des pièces pour les commandes multi-pièces
CREATE TABLE IF NOT EXISTS commande_items (
    id_item       INT AUTO_INCREMENT PRIMARY KEY,
    id_commande   INT NOT NULL,
    id_piece      INT NOT NULL,
    quantite      INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    sous_total    DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire) STORED,
    FOREIGN KEY (id_commande) REFERENCES commandes(id_commande) ON DELETE CASCADE,
    FOREIGN KEY (id_piece)    REFERENCES pieces(id_piece)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- B) ALTER TABLE commandes (ajout non destructif)
-- ──────────────────────────────────────────────

-- Ajout de colonnes uniquement si elles n'existent pas déjà.
-- Exécuter chaque bloc séparément ; ignorer l'erreur si la colonne existe.

-- Montant HT
ALTER TABLE commandes ADD COLUMN montant_ht DECIMAL(10,2) DEFAULT 0;
-- TVA
ALTER TABLE commandes ADD COLUMN tva DECIMAL(10,2) DEFAULT 0;
-- Frais de livraison
ALTER TABLE commandes ADD COLUMN frais_livraison DECIMAL(10,2) DEFAULT 15.00;
-- Montant TTC
ALTER TABLE commandes ADD COLUMN montant_ttc DECIMAL(10,2) DEFAULT 0;
-- Source de la commande
ALTER TABLE commandes ADD COLUMN source ENUM('direct','panier','intervention') DEFAULT 'direct';
-- Référence au panier d'origine
ALTER TABLE commandes ADD COLUMN id_panier INT DEFAULT NULL;
-- Note libre du client
ALTER TABLE commandes ADD COLUMN note TEXT DEFAULT NULL;

-- ──────────────────────────────────────────────
-- C) VUE SQL pour le dashboard
-- ──────────────────────────────────────────────

CREATE OR REPLACE VIEW vue_commande_detail AS
SELECT
    c.id_commande,
    c.statut,
    c.date_commande,
    c.montant_ttc,
    c.source,
    c.nom_client,
    c.prenom_client,
    c.telephone,
    COUNT(ci.id_item)                                     AS nb_pieces,
    GROUP_CONCAT(p.nom ORDER BY p.nom SEPARATOR ' | ')    AS liste_pieces
FROM commandes c
LEFT JOIN commande_items ci ON c.id_commande = ci.id_commande
LEFT JOIN pieces p          ON ci.id_piece   = p.id_piece
GROUP BY c.id_commande;
