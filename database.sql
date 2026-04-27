-- =====================================================
-- Smart Garage - Base de données (table unique : user)
-- =====================================================

CREATE DATABASE IF NOT EXISTS garage1
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE garage1;

-- Table unique : user
-- post = 'admin' | 'client'
CREATE TABLE IF NOT EXISTS user (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(100)    NOT NULL,
    prenom       VARCHAR(100)    NOT NULL,
    email        VARCHAR(180)    NOT NULL UNIQUE,
    telephone    VARCHAR(20)     DEFAULT NULL,
    adresse      VARCHAR(255)    DEFAULT NULL,
    mot_de_passe VARCHAR(255)    NOT NULL,
    statut       ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
    post         ENUM('admin','client') NOT NULL DEFAULT 'client',
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email (email),
    INDEX idx_post  (post)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Admin par défaut ──────────────────────────────────────────────
-- Mot de passe : admin123  (bcrypt)
INSERT INTO user (nom, prenom, email, mot_de_passe, statut, post)
VALUES (
    'Admin',
    'Super',
    'admin@garage.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'actif',
    'admin'
);

-- ── Client de test ────────────────────────────────────────────────
-- Mot de passe : client123  (bcrypt)
INSERT INTO user (nom, prenom, email, telephone, adresse, mot_de_passe, statut, post)
VALUES (
    'Ben Ali',
    'Ahmed',
    'ahmed@email.com',
    '+216 20 123 456',
    'Tunis, Tunisie',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'actif',
    'client'
);
