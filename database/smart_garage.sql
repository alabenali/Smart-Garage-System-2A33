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
    status ENUM('en attente', 'terminé') DEFAULT 'en attente',
    date_diagnostic DATE DEFAULT CURRENT_DATE,
    CONSTRAINT fk_vehicle FOREIGN KEY (id_vehicule) REFERENCES vehicle(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
