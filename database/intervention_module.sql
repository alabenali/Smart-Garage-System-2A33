-- ============================================================
-- Smart Garage - Module Interventions + Devis + Messagerie
-- ============================================================
-- Compatible MySQL 8+ / MariaDB (InnoDB, UTF8MB4)
-- Ce script est idempotent: relancable sans casser l'existant.

USE smart_garage_system;

-- ============================================================
-- Table: diagnostic
-- ============================================================
CREATE TABLE IF NOT EXISTS diagnostic (
    id_diagnostic INT AUTO_INCREMENT PRIMARY KEY,
    id_vehicule INT NOT NULL,
    description_probleme TEXT,
    resultat TEXT,
    gravite ENUM('Faible', 'Moyen', 'Eleve') DEFAULT 'Faible',
    montant_estime DECIMAL(10,2) DEFAULT 0,
    status ENUM('en_attente', 'accepte', 'refuse', 'termine') DEFAULT 'en_attente',
    date_diagnostic DATETIME DEFAULT CURRENT_TIMESTAMP,
    media_path VARCHAR(255) DEFAULT NULL,
    media_type VARCHAR(100) DEFAULT NULL,
    CONSTRAINT fk_diagnostic_vehicle FOREIGN KEY (id_vehicule) REFERENCES vehicle(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: type_intervention
-- ============================================================
CREATE TABLE IF NOT EXISTS type_intervention (
    id_type INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: intervention
-- ============================================================
CREATE TABLE IF NOT EXISTS intervention (
    id_intervention INT AUTO_INCREMENT PRIMARY KEY,
    id_diagnostic INT NOT NULL UNIQUE,
    id_type INT NOT NULL,
    description_travail TEXT NOT NULL,
    statut ENUM('planifiee', 'en_cours', 'terminee') NOT NULL DEFAULT 'planifiee',
    cout_initial DECIMAL(10,2) NOT NULL,
    cout_final DECIMAL(10,2) DEFAULT NULL,

    -- Gestion du devis
    statut_devis ENUM('en_attente', 'accepte', 'refuse', 'en_negociation') NOT NULL DEFAULT 'en_attente',
    devis_pdf_path VARCHAR(255) DEFAULT NULL,
    date_envoi_devis DATETIME DEFAULT NULL,
    date_reponse_devis DATETIME DEFAULT NULL,

    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME DEFAULT NULL,

    CONSTRAINT fk_intervention_diagnostic FOREIGN KEY (id_diagnostic) REFERENCES diagnostic(id_diagnostic) ON DELETE CASCADE,
    CONSTRAINT fk_intervention_type FOREIGN KEY (id_type) REFERENCES type_intervention(id_type) ON DELETE RESTRICT,

    CHECK (cout_initial >= 0),
    CHECK (cout_final IS NULL OR cout_final >= 0),
    CHECK (cout_final IS NULL OR cout_final >= cout_initial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: message (chat client <-> admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS message (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_intervention INT NOT NULL,
    expediteur ENUM('client', 'admin') NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_intervention FOREIGN KEY (id_intervention) REFERENCES intervention(id_intervention) ON DELETE CASCADE,
    INDEX idx_message_intervention_date (id_intervention, date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Migration defensive pour base existante
-- ============================================================
ALTER TABLE intervention
    ADD COLUMN IF NOT EXISTS statut_devis ENUM('en_attente', 'accepte', 'refuse', 'en_negociation') NOT NULL DEFAULT 'en_attente',
    ADD COLUMN IF NOT EXISTS devis_pdf_path VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS date_envoi_devis DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS date_reponse_devis DATETIME DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_intervention_statut_devis ON intervention(statut_devis);
