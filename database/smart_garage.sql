-- ============================================
-- Smart Garage System - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS smart_garage_system
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE smart_garage_system;

-- ============================================
-- Table: vehicle
-- ============================================
CREATE TABLE IF NOT EXISTS vehicle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(20) NOT NULL,
    couleur VARCHAR(50) NOT NULL,
    annee INT NOT NULL,
    kilometrage INT NOT NULL,
    carburant VARCHAR(50) NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Sample Data (optional – for testing)
-- ============================================
INSERT INTO vehicle (marque, modele, immatriculation, couleur, annee, kilometrage, carburant) VALUES
('Peugeot', '208', '123 TU 4567', 'Blanc', 2021, 35000, 'Essence'),
('Renault', 'Clio', '789 TU 1234', 'Noir', 2019, 68000, 'Diesel'),
('Volkswagen', 'Golf', '456 TU 7890', 'Gris', 2020, 42000, 'Essence'),
('BMW', 'Serie 3', '321 TU 6543', 'Bleu', 2022, 15000, 'Hybride'),
('Toyota', 'Yaris', '654 TU 3210', 'Rouge', 2018, 95000, 'Essence');

-- ============================================
-- Table: diagnostic
-- ============================================
CREATE TABLE IF NOT EXISTS diagnostic (
    id_diagnostic INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_vehicule INT(11) NOT NULL,
    description_probleme TEXT,
    resultat TEXT,
    gravite ENUM('Faible', 'Moyen', 'Élevé') DEFAULT 'Faible',
    montant_estime FLOAT DEFAULT 0,
    status ENUM('en_attente', 'accepte', 'refuse', 'termine') DEFAULT 'en_attente',
    date_diagnostic DATE DEFAULT CURRENT_DATE,
    media_path VARCHAR(255) DEFAULT NULL,
    media_type VARCHAR(50) DEFAULT NULL,
    CONSTRAINT fk_vehicle FOREIGN KEY (id_vehicule) REFERENCES vehicle(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: type_intervention
-- ============================================
CREATE TABLE IF NOT EXISTS type_intervention (
    id_type INT(11) AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: intervention
-- ============================================
CREATE TABLE IF NOT EXISTS intervention (
    id_intervention INT AUTO_INCREMENT PRIMARY KEY,
    id_diagnostic INT NOT NULL UNIQUE,
    id_type INT NOT NULL,
    description_travail TEXT NOT NULL,
    statut ENUM('planifiée', 'en_cours', 'terminée') DEFAULT 'planifiée',
    cout_initial DECIMAL(10, 2),
    cout_final DECIMAL(10, 2),
    statut_devis ENUM('en_attente', 'accepte', 'refuse', 'en_negociation') DEFAULT 'en_attente',
    devis_pdf_path VARCHAR(255) DEFAULT NULL,
    date_envoi_devis DATETIME DEFAULT NULL,
    date_reponse_devis DATETIME DEFAULT NULL,
    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME,
    -- 🔗 Clés étrangères
    FOREIGN KEY (id_diagnostic) REFERENCES diagnostic(id_diagnostic) ON DELETE CASCADE,
    FOREIGN KEY (id_type) REFERENCES type_intervention(id_type) ON DELETE RESTRICT,
    -- ✅ Contraintes d'intégrité métier
    CHECK (cout_initial IS NULL OR cout_initial >= 0),
    CHECK (cout_final IS NULL OR cout_final >= 0),
    CHECK (cout_final IS NULL OR cout_initial IS NULL OR cout_final >= cout_initial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: message (chat intervention)
-- ============================================
CREATE TABLE IF NOT EXISTS message (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_intervention INT NOT NULL,
    expediteur ENUM('client', 'admin') NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_intervention) REFERENCES intervention(id_intervention) ON DELETE CASCADE,
    INDEX idx_message_intervention_date (id_intervention, date_envoi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Sample Data: Type Interventions
-- ============================================
INSERT INTO type_intervention (nom, description) VALUES
('Révision', 'Entretien régulier du véhicule'),
('Remplacement pièce', 'Remplacement de composants défectueux'),
('Réparation moteur', 'Réparation des éléments du moteur'),
('Diagnostic électrique', 'Diagnostic complet du système électrique'),
('Changement pneus', 'Changement et équilibrage des pneus'),
('Réparation freinage', 'Entretien et réparation du système de freinage');
