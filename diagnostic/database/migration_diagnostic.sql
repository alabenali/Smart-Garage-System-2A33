USE smart_garage;

CREATE TABLE IF NOT EXISTS diagnostic (
    id_diagnostic INT AUTO_INCREMENT PRIMARY KEY,
    id_client INT NULL,
    id_vehicle INT NULL,
    id_vehicule INT NULL,
    id_rdv INT NULL,
    type_diagnostic VARCHAR(100) NULL,
    description_probleme TEXT NULL,
    resultat TEXT NULL,
    gravite VARCHAR(50) NULL DEFAULT 'Faible',
    montant_estime DECIMAL(10,2) DEFAULT 0.00,
    statut VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    status VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    date_diagnostic DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    media_path VARCHAR(255) NULL,
    media_type VARCHAR(100) NULL,
    CONSTRAINT fk_diagnostic_vehicle
        FOREIGN KEY (id_vehicle) REFERENCES vehicule(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_diagnostic_rdv
        FOREIGN KEY (id_rdv) REFERENCES rendezvous_digital(id_rdv)
        ON DELETE SET NULL,
    INDEX idx_diagnostic_client_vehicle_rdv_statut (id_client, id_vehicle, id_rdv, statut),
    INDEX idx_diagnostic_vehicule (id_vehicule),
    INDEX idx_diagnostic_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE diagnostic
    ADD COLUMN IF NOT EXISTS id_client INT NULL AFTER id_diagnostic,
    ADD COLUMN IF NOT EXISTS id_vehicle INT NULL AFTER id_client,
    ADD COLUMN IF NOT EXISTS id_vehicule INT NULL AFTER id_vehicle,
    ADD COLUMN IF NOT EXISTS id_rdv INT NULL AFTER id_vehicule,
    ADD COLUMN IF NOT EXISTS type_diagnostic VARCHAR(100) NULL AFTER id_rdv,
    ADD COLUMN IF NOT EXISTS description_probleme TEXT NULL AFTER type_diagnostic,
    ADD COLUMN IF NOT EXISTS resultat TEXT NULL AFTER description_probleme,
    ADD COLUMN IF NOT EXISTS gravite VARCHAR(50) NULL DEFAULT 'Faible' AFTER resultat,
    ADD COLUMN IF NOT EXISTS montant_estime DECIMAL(10,2) DEFAULT 0.00 AFTER gravite,
    ADD COLUMN IF NOT EXISTS statut VARCHAR(50) NOT NULL DEFAULT 'en_attente' AFTER montant_estime,
    ADD COLUMN IF NOT EXISTS status VARCHAR(50) NOT NULL DEFAULT 'en_attente' AFTER statut,
    ADD COLUMN IF NOT EXISTS date_diagnostic DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status,
    ADD COLUMN IF NOT EXISTS media_path VARCHAR(255) NULL AFTER date_diagnostic,
    ADD COLUMN IF NOT EXISTS media_type VARCHAR(100) NULL AFTER media_path;

UPDATE diagnostic
SET id_vehicle = COALESCE(id_vehicle, id_vehicule),
    id_vehicule = COALESCE(id_vehicule, id_vehicle),
    statut = COALESCE(NULLIF(statut, ''), NULLIF(status, ''), 'en_attente'),
    status = COALESCE(NULLIF(status, ''), NULLIF(statut, ''), 'en_attente');

CREATE TABLE IF NOT EXISTS type_intervention (
    id_type INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE type_intervention
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER nom,
    ADD COLUMN IF NOT EXISTS prix DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER description,
    ADD COLUMN IF NOT EXISTS date_creation DATETIME DEFAULT CURRENT_TIMESTAMP AFTER prix;

INSERT IGNORE INTO type_intervention (nom, description, prix) VALUES
    ('Diagnostic general', 'Diagnostic complet du vehicule', 60.00),
    ('Diagnostic electrique', 'Controle du systeme electrique et electronique', 90.00),
    ('Moteur', 'Intervention mecanique moteur', 120.00),
    ('Freinage', 'Controle et reparation du systeme de freinage', 80.00),
    ('Climatisation', 'Controle et reparation climatisation', 70.00);

CREATE TABLE IF NOT EXISTS intervention (
    id_intervention INT AUTO_INCREMENT PRIMARY KEY,
    id_diagnostic INT NOT NULL,
    id_type INT NOT NULL,
    description_travail TEXT NOT NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'planifiee',
    cout_initial DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cout_final DECIMAL(10,2) DEFAULT NULL,
    statut_devis VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    devis_pdf_path VARCHAR(255) DEFAULT NULL,
    date_envoi_devis DATETIME DEFAULT NULL,
    date_reponse_devis DATETIME DEFAULT NULL,
    type_prices TEXT DEFAULT NULL,
    type_total DECIMAL(10,2) DEFAULT 0.00,
    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME DEFAULT NULL,
    CONSTRAINT fk_intervention_diagnostic
        FOREIGN KEY (id_diagnostic) REFERENCES diagnostic(id_diagnostic)
        ON DELETE CASCADE,
    CONSTRAINT fk_intervention_type
        FOREIGN KEY (id_type) REFERENCES type_intervention(id_type)
        ON DELETE RESTRICT,
    UNIQUE KEY uq_intervention_diagnostic (id_diagnostic),
    INDEX idx_intervention_statut_devis (statut_devis),
    INDEX idx_intervention_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE intervention
    ADD COLUMN IF NOT EXISTS statut_devis VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    ADD COLUMN IF NOT EXISTS devis_pdf_path VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS date_envoi_devis DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS date_reponse_devis DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS type_prices TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS type_total DECIMAL(10,2) DEFAULT 0.00;

CREATE TABLE IF NOT EXISTS message (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_intervention INT NOT NULL,
    expediteur ENUM('client', 'admin') NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_intervention_date (id_intervention, date_envoi),
    CONSTRAINT fk_message_intervention
        FOREIGN KEY (id_intervention) REFERENCES intervention(id_intervention)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
