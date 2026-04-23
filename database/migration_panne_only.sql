-- Migration: formulaire front en mode declaration de panne uniquement
-- Execution: importer ce fichier dans la base smart_garage

USE smart_garage;

ALTER TABLE rendezvous_digital
    MODIFY nom_client VARCHAR(150) NULL,
    MODIFY prenom_client VARCHAR(150) NULL,
    MODIFY telephone_client VARCHAR(20) NULL;

ALTER TABLE rendezvous_digital
    ADD COLUMN circonstances_panne VARCHAR(100) NULL AFTER description_panne,
    ADD COLUMN temoins_panne TEXT NULL AFTER circonstances_panne,
    ADD COLUMN panne_data_json LONGTEXT NULL AFTER temoins_panne,
    ADD COLUMN photos_json LONGTEXT NULL AFTER panne_data_json;
