-- ============================================
-- Migration : Système de Garanties Pièces
-- Compatible MySQL 8.0+ / MariaDB 10.6+
-- ============================================

-- 1. Ajouter la durée de garantie par défaut sur les pièces
ALTER TABLE pieces ADD COLUMN garantie_mois INT DEFAULT 1 COMMENT 'Duree garantie en mois, 0 = pas de garantie';

-- 2. Table principale des garanties
CREATE TABLE IF NOT EXISTS garanties (
    id_garantie         INT AUTO_INCREMENT PRIMARY KEY,
    id_commande         INT NOT NULL,
    id_piece            INT NOT NULL,
    id_client           INT DEFAULT NULL,
    date_pose           DATE NOT NULL,
    duree_mois          INT NOT NULL DEFAULT 1,
    date_expiration     DATE GENERATED ALWAYS AS (DATE_ADD(date_pose, INTERVAL duree_mois MONTH)) STORED,
    kilometrage_pose    INT DEFAULT NULL,
    technicien          VARCHAR(100) DEFAULT NULL,
    statut              ENUM('active','expiree','remplacee') DEFAULT 'active',
    alerte_30j_envoyee  TINYINT(1) DEFAULT 0,
    alerte_7j_envoyee   TINYINT(1) DEFAULT 0,
    alerte_expir_envoyee TINYINT(1) DEFAULT 0,
    notes               TEXT DEFAULT NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expiration_statut (date_expiration, statut),
    INDEX idx_client (id_client),
    FOREIGN KEY (id_commande) REFERENCES commandes(id_commande) ON DELETE CASCADE,
    FOREIGN KEY (id_piece)    REFERENCES pieces(id_piece)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Vue détaillée des garanties
CREATE OR REPLACE VIEW vue_garanties AS
SELECT
    g.id_garantie,
    g.id_commande,
    g.id_piece,
    g.id_client,
    g.date_pose,
    g.date_expiration,
    g.duree_mois,
    g.statut,
    g.kilometrage_pose,
    g.technicien,
    g.alerte_30j_envoyee,
    g.alerte_7j_envoyee,
    g.alerte_expir_envoyee,
    g.notes,
    g.created_at,
    DATEDIFF(g.date_expiration, CURDATE()) AS jours_restants,
    p.nom        AS nom_piece,
    p.reference  AS ref_piece,
    p.marque     AS marque_piece,
    p.categorie  AS categorie_piece,
    c.nom_client,
    c.prenom_client,
    CONCAT(COALESCE(c.prenom_client,''), ' ', COALESCE(c.nom_client,'')) AS nom_complet,
    c.telephone,
    c.telephone AS email
FROM garanties g
INNER JOIN pieces p    ON p.id_piece = g.id_piece
INNER JOIN commandes c ON c.id_commande = g.id_commande;

-- 4. Vue des alertes à envoyer
CREATE OR REPLACE VIEW vue_alertes_a_envoyer AS
SELECT
    vg.*,
    CASE
        WHEN vg.jours_restants <= 0 AND vg.alerte_expir_envoyee = 0
            THEN 'EXPIREE'
        WHEN vg.jours_restants <= 7 AND vg.jours_restants > 0 AND vg.alerte_7j_envoyee = 0
            THEN 'ALERTE_7J'
        WHEN vg.jours_restants <= 30 AND vg.jours_restants > 7 AND vg.alerte_30j_envoyee = 0
            THEN 'ALERTE_30J'
    END AS type_alerte
FROM vue_garanties vg
WHERE vg.statut = 'active'
  AND (
      (vg.jours_restants <= 30 AND vg.jours_restants > 7 AND vg.alerte_30j_envoyee = 0)
   OR (vg.jours_restants <= 7  AND vg.jours_restants > 0 AND vg.alerte_7j_envoyee = 0)
   OR (vg.jours_restants <= 0  AND vg.alerte_expir_envoyee = 0)
  );

-- 5. Durées de garantie réalistes par catégorie de pièce
UPDATE pieces SET garantie_mois = 1 WHERE LOWER(nom) LIKE '%plaquette%' OR LOWER(nom) LIKE '%disque%';
UPDATE pieces SET garantie_mois = 1 WHERE LOWER(nom) LIKE '%batterie%';
UPDATE pieces SET garantie_mois = 0  WHERE LOWER(nom) LIKE '%filtre%' OR LOWER(nom) LIKE '%huile%' OR LOWER(nom) LIKE '%liquide%';
UPDATE pieces SET garantie_mois = 1 WHERE LOWER(nom) LIKE '%amortisseur%';
UPDATE pieces SET garantie_mois = 1 WHERE LOWER(nom) LIKE '%distribution%' OR LOWER(nom) LIKE '%kit%';
UPDATE pieces SET garantie_mois = 1  WHERE LOWER(nom) LIKE '%bougie%';
