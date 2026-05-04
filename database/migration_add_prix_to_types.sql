-- ============================================
-- Migration: Ajouter colonne PRIX aux types d'intervention
-- Date: Mai 2026
-- ============================================

USE smart_garage_system;

-- Ajouter la colonne prix si elle n'existe pas
ALTER TABLE type_intervention 
ADD COLUMN IF NOT EXISTS prix DECIMAL(10, 2) DEFAULT 0.00;

-- Exemples de prix pour les types existants (à adapter selon vos besoins)
UPDATE type_intervention SET prix = 150.00 WHERE nom LIKE '%révision%' OR nom LIKE '%Revision%';
UPDATE type_intervention SET prix = 80.00 WHERE nom LIKE '%frein%' OR nom LIKE '%Frein%';
UPDATE type_intervention SET prix = 120.00 WHERE nom LIKE '%pneu%' OR nom LIKE '%Pneu%';
UPDATE type_intervention SET prix = 200.00 WHERE nom LIKE '%climat%' OR nom LIKE '%Climat%';
UPDATE type_intervention SET prix = 90.00 WHERE nom LIKE '%diagnostic%' OR nom LIKE '%Diagnostic%';

-- Afficher le résultat
SELECT id_type, nom, description, prix FROM type_intervention ORDER BY nom;
