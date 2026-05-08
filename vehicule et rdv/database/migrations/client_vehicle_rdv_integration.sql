-- Smart Garage - integration Client / Vehicule / RDV
-- Migration non destructive pour bases existantes.

USE smart_garage;

ALTER TABLE vehicle
    ADD COLUMN IF NOT EXISTS id_client INT NULL AFTER id;

ALTER TABLE rendezvous_digital
    ADD COLUMN IF NOT EXISTS id_client INT NULL AFTER id_creneau;

ALTER TABLE vehicle
    ADD INDEX IF NOT EXISTS idx_vehicle_id_client (id_client);

ALTER TABLE rendezvous_digital
    ADD INDEX IF NOT EXISTS idx_rdv_id_client (id_client),
    ADD INDEX IF NOT EXISTS idx_rdv_id_vehicle (id_vehicle);

-- Les FK vers la base client `garage1` sont volontairement laissees optionnelles :
-- selon l'installation MariaDB/XAMPP, les contraintes cross-database peuvent etre refusees
-- si les engines/collations ne sont pas strictement identiques. La validation metier est
-- assuree par services/RdvService.php avant toute creation de RDV.
--
-- Option production si les deux tables sont compatibles :
-- ALTER TABLE `smart_garage`.`vehicle`
--   ADD CONSTRAINT `fk_vehicle_client`
--   FOREIGN KEY (`id_client`) REFERENCES `garage1`.`user`(`id`)
--   ON DELETE SET NULL ON UPDATE CASCADE;
--
-- ALTER TABLE `smart_garage`.`rendezvous_digital`
--   ADD CONSTRAINT `fk_rdv_client`
--   FOREIGN KEY (`id_client`) REFERENCES `garage1`.`user`(`id`)
--   ON DELETE SET NULL ON UPDATE CASCADE;
