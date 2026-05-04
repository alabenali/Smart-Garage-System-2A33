CREATE TABLE IF NOT EXISTS telegram_alerts_log (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  id_piece      INT NOT NULL,
  type_alerte   ENUM('rupture','stock_faible') NOT NULL,
  stock_au_moment INT NOT NULL,
  message_id    BIGINT DEFAULT NULL COMMENT 'ID du message Telegram envoye',
  envoyee_le    DATETIME DEFAULT CURRENT_TIMESTAMP,
  resolue       TINYINT(1) DEFAULT 0,
  resolue_le    DATETIME DEFAULT NULL,
  FOREIGN KEY (id_piece) REFERENCES pieces(id_piece) ON DELETE CASCADE,
  INDEX idx_piece_type (id_piece, type_alerte, resolue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mettre à jour quelques pièces pour simuler les alertes (si les IDs existent)
UPDATE pieces SET quantite_stock = 0 WHERE id_piece = 1;
UPDATE pieces SET quantite_stock = seuil_alerte - 1 WHERE id_piece = 2;
UPDATE pieces SET quantite_stock = seuil_alerte - 2 WHERE id_piece = 3;
