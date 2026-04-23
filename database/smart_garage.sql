-- ============================================
-- Système Smart Garage - Schéma de la base de données
-- ============================================

CREATE DATABASE IF NOT EXISTS smart_garage
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE smart_garage;

-- ============================================
-- Table : vehicle
-- ============================================
CREATE TABLE IF NOT EXISTS vehicle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(20) NOT NULL UNIQUE,
    couleur VARCHAR(50) NOT NULL,
    annee INT NOT NULL,
    kilometrage INT NOT NULL,
    carburant ENUM('Essence','Diesel','Hybride','Electrique','GPL') NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table : creneau_atelier
-- ============================================
CREATE TABLE IF NOT EXISTS creneau_atelier (
    id_creneau INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    est_heure_creuse TINYINT(1) NOT NULL DEFAULT 0,
    capacite_max INT NOT NULL DEFAULT 3,
    UNIQUE KEY uq_creneau_datetime (date_heure)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table : rendezvous_digital
-- ============================================
CREATE TABLE IF NOT EXISTS rendezvous_digital (
    id_rdv INT AUTO_INCREMENT PRIMARY KEY,
    id_creneau INT NOT NULL,
    nom_client VARCHAR(150) NULL,
    prenom_client VARCHAR(150) NULL,
    telephone_client VARCHAR(20) NULL,
    email_client VARCHAR(255) NULL,
    id_vehicle INT NULL,
    type_intervention VARCHAR(255) NOT NULL,
    description_panne TEXT NULL,
    circonstances_panne VARCHAR(100) NULL,
    temoins_panne TEXT NULL,
    panne_data_json LONGTEXT NULL,
    photos_json LONGTEXT NULL,
    remise_eco_appliquee DECIMAL(5,2) NOT NULL DEFAULT 0,
    statut ENUM('En attente','Confirmé','En cours','Terminé','Annulé') NOT NULL DEFAULT 'En attente',
    notes TEXT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rdv_creneau FOREIGN KEY (id_creneau) REFERENCES creneau_atelier(id_creneau) ON DELETE CASCADE,
    CONSTRAINT fk_rdv_vehicle FOREIGN KEY (id_vehicle) REFERENCES vehicle(id) ON DELETE SET NULL,
    INDEX idx_rdv_statut (statut),
    INDEX idx_rdv_creation (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Mise a niveau schema (bases existantes)
-- ============================================
ALTER TABLE rendezvous_digital
    MODIFY nom_client VARCHAR(150) NULL,
    MODIFY prenom_client VARCHAR(150) NULL,
    MODIFY telephone_client VARCHAR(20) NULL;

ALTER TABLE rendezvous_digital
    ADD COLUMN IF NOT EXISTS circonstances_panne VARCHAR(100) NULL AFTER description_panne;

ALTER TABLE rendezvous_digital
    ADD COLUMN IF NOT EXISTS temoins_panne TEXT NULL AFTER circonstances_panne;

ALTER TABLE rendezvous_digital
    ADD COLUMN IF NOT EXISTS panne_data_json LONGTEXT NULL AFTER temoins_panne;

ALTER TABLE rendezvous_digital
    ADD COLUMN IF NOT EXISTS photos_json LONGTEXT NULL AFTER panne_data_json;

-- ============================================
-- Données d'exemple (optionnel – pour tests)
-- ============================================
INSERT INTO vehicle (marque, modele, immatriculation, couleur, annee, kilometrage, carburant) VALUES
('Peugeot', '208', '123 TU 4567', 'Blanc', 2021, 35000, 'Essence'),
('Renault', 'Clio', '789 TU 1234', 'Noir', 2019, 68000, 'Diesel'),
('Volkswagen', 'Golf', '456 TU 7890', 'Gris', 2020, 42000, 'Essence'),
('BMW', 'Serie 3', '321 TU 6543', 'Bleu', 2022, 15000, 'Hybride'),
('Toyota', 'Yaris', '654 TU 3210', 'Rouge', 2018, 95000, 'Essence');
