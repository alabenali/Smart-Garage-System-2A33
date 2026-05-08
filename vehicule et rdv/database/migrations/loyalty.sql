-- Programme fidelite Smart Garage
-- A executer une fois sur la base smart_garage.

CREATE TABLE IF NOT EXISTS loyalty_account (
  id INT PRIMARY KEY AUTO_INCREMENT,
  client_nom VARCHAR(150) NOT NULL,
  client_prenom VARCHAR(150) NOT NULL,
  client_email VARCHAR(255) UNIQUE NOT NULL,
  client_telephone VARCHAR(20),
  points_total INT NOT NULL DEFAULT 0,
  points_utilises INT NOT NULL DEFAULT 0,
  points_restants INT GENERATED ALWAYS AS (points_total - points_utilises) STORED,
  palier_actuel ENUM('Bronze','Argent','Or','Platinum') NOT NULL DEFAULT 'Bronze',
  date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
  derniere_activite DATETIME,
  INDEX idx_loyalty_palier (palier_actuel),
  INDEX idx_loyalty_derniere_activite (derniere_activite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS loyalty_transactions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  loyalty_id INT NOT NULL,
  id_rdv INT,
  type ENUM('gain','utilisation','expiration','bonus') NOT NULL,
  points INT NOT NULL,
  description VARCHAR(255),
  date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_loyalty_transactions_account
    FOREIGN KEY (loyalty_id) REFERENCES loyalty_account(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_loyalty_transactions_rdv
    FOREIGN KEY (id_rdv) REFERENCES rendezvous_digital(id_rdv)
    ON DELETE SET NULL,
  INDEX idx_loyalty_transactions_account_date (loyalty_id, date_transaction),
  INDEX idx_loyalty_transactions_rdv_type (id_rdv, type),
  UNIQUE KEY uq_loyalty_transaction_rdv_type (id_rdv, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS loyalty_paliers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom ENUM('Bronze','Argent','Or','Platinum') UNIQUE,
  points_requis INT NOT NULL,
  couleur_hex VARCHAR(7),
  icone VARCHAR(10),
  avantage_desc VARCHAR(255),
  remise_pct INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO loyalty_paliers (id, nom, points_requis, couleur_hex, icone, avantage_desc, remise_pct) VALUES
(1,'Bronze',0,'#CD7F32','🥉','Acces au programme fidelite',0),
(2,'Argent',100,'#C0C0C0','🥈','5% de remise sur chaque intervention',5),
(3,'Or',300,'#FFD700','🥇','10% de remise + vidange offerte/an',10),
(4,'Platinum',600,'#E5E4E2','💎','15% de remise + priorite creneaux',15)
ON DUPLICATE KEY UPDATE
  points_requis = VALUES(points_requis),
  couleur_hex = VALUES(couleur_hex),
  icone = VALUES(icone),
  avantage_desc = VALUES(avantage_desc),
  remise_pct = VALUES(remise_pct);
