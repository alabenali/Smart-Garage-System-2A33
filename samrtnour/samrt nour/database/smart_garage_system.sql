-- ============================================
-- Smart Garage System - Pièces & Commandes
-- Tables: pieces, commandes
-- ============================================

CREATE DATABASE IF NOT EXISTS smart_garage_system
CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE smart_garage_system;

-- ============================================
-- Table: pieces
-- ============================================
CREATE TABLE IF NOT EXISTS pieces (
    id_piece      INT AUTO_INCREMENT PRIMARY KEY,
    reference     VARCHAR(50)  NOT NULL UNIQUE,
    nom           VARCHAR(150) NOT NULL,
    description   TEXT,
    categorie     VARCHAR(100) NOT NULL,
    marque        VARCHAR(100) NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    quantite_stock INT NOT NULL DEFAULT 0,
    seuil_alerte  INT NOT NULL DEFAULT 5,
    image         VARCHAR(255) DEFAULT NULL,
    date_ajout    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: commandes
-- ============================================
CREATE TABLE IF NOT EXISTS commandes (
    id_commande   INT AUTO_INCREMENT PRIMARY KEY,
    id_piece      INT NOT NULL,
    nom_client    VARCHAR(150) NOT NULL,
    prenom_client VARCHAR(150) NOT NULL,
    telephone     VARCHAR(20)  NOT NULL,
    quantite      INT NOT NULL DEFAULT 1,
    montant_total DECIMAL(10,2) NOT NULL,
    statut        VARCHAR(50)  NOT NULL DEFAULT 'En attente',
    payment_method VARCHAR(100) NOT NULL DEFAULT 'Paiement a la livraison',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'Non paye',
    payment_gateway_reference VARCHAR(255) DEFAULT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_piece) REFERENCES pieces(id_piece) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Sample Data – Pieces (for testing)
-- ============================================
INSERT INTO pieces (reference, nom, description, categorie, marque, prix_unitaire, quantite_stock, seuil_alerte) VALUES
('PLQ-BRK-001', 'Plaquette de frein avant', 'Plaquettes de frein haute performance pour essieu avant', 'Freinage', 'Bosch', 45.90, 25, 5),
('FLT-HUI-002', 'Filtre à huile', 'Filtre à huile compatible moteurs essence et diesel', 'Filtration', 'Mann-Filter', 12.50, 50, 10),
('BUG-ALL-003', 'Bougie d''allumage', 'Bougie d''allumage iridium longue durée', 'Allumage', 'NGK', 8.75, 100, 15),
('AMR-AVT-004', 'Amortisseur avant', 'Amortisseur à gaz pour essieu avant', 'Suspension', 'Monroe', 89.00, 8, 3),
('CRR-DST-005', 'Courroie de distribution', 'Kit courroie de distribution avec tendeur', 'Distribution', 'Gates', 125.50, 12, 4),
('BAT-12V-006', 'Batterie 12V 60Ah', 'Batterie auto sans entretien 12V 60Ah', 'Électricité', 'Varta', 95.00, 6, 2),
('DSQ-FRN-007', 'Disque de frein avant', 'Disque de frein ventilé haute résistance', 'Freinage', 'Brembo', 65.30, 15, 5),
('FLT-AIR-008', 'Filtre à air', 'Filtre à air haute performance', 'Filtration', 'K&N', 22.00, 35, 8),
('LMP-PHR-009', 'Lampe phare H7', 'Ampoule halogène H7 55W', 'Éclairage', 'Philips', 15.90, 40, 10),
('HUI-MTR-010', 'Huile moteur 5W-30', 'Huile moteur synthétique 5L', 'Lubrification', 'Total', 38.50, 20, 5);

-- ============================================
-- Sample Data – Commandes (for testing)
-- ============================================
INSERT INTO commandes (id_piece, nom_client, prenom_client, telephone, quantite, montant_total, statut) VALUES
(1, 'Ben Ahmed', 'Karim', '98 765 432', 2, 91.80, 'En attente'),
(3, 'Trabelsi', 'Sami', '55 123 456', 4, 35.00, 'Confirmée');
