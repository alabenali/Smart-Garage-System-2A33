-- ============================================
-- Migration: Support pour types multiples
-- Convertir id_type de INT à JSON
-- ============================================

USE smart_garage_system;

-- 1. Créer un nouveau champ temporaire pour les types JSON
ALTER TABLE intervention 
ADD COLUMN id_type_json JSON DEFAULT NULL AFTER id_type;

-- 2. Copier les données existantes vers le nouveau champ (convertir en JSON)
UPDATE intervention 
SET id_type_json = JSON_ARRAY(id_type) 
WHERE id_type IS NOT NULL;

-- 3. Supprimer la clé étrangère sur l'ancien champ (essayer tous les noms possibles)
-- Si l'erreur persiste, vérifier avec: SHOW CREATE TABLE intervention;
ALTER TABLE intervention 
DROP FOREIGN KEY fk_intervention_type;

-- 4. Supprimer l'ancien champ
ALTER TABLE intervention 
DROP COLUMN id_type;

-- 5. Renommer le nouveau champ
ALTER TABLE intervention 
CHANGE COLUMN id_type_json id_type JSON DEFAULT NULL;

-- Vérification
SELECT * FROM intervention LIMIT 5;
