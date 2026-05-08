# Smart Garage - Projet Final

## Description
Application web de gestion de garage avec interface client (frontoffice) et administration (backoffice).

## Structure du projet

```
projet_final/
├── config.php              # Configuration globale (BDD, reCAPTCHA)
├── migration.sql           # Script de mise à jour BDD
├── controllers/
│   ├── UserController.php  # Gestion utilisateurs (frontoffice)
│   └── AdminController.php # Gestion administrateurs (backoffice)
├── models/
│   └── User.php            # Modèle utilisateur
├── views/
│   ├── frontoffice/        # Interface client
│   │   ├── login.php, register.php, profile.php, dashboard.php
│   │   └── js/             # Validation frontend
│   └── backoffice/         # Interface admin
│       ├── admin_login.php, admin_dashboard.php, users_list.php
│       └── js/             # Validation frontend
├── assets/
│   └── avatars/            # Avatars prédéfinis (générés dynamiquement)
└── uploads/
    └── profile_pictures/   # Photos de profil uploadées
```

## Installation

### 1. Base de données
- Créer une base de données MySQL `garage1`
- Exécuter le script `migration.sql` pour ajouter les colonnes nécessaires

### 2. Configuration XAMPP
- Placer le projet dans `C:\xampp\htdocs\projet_final`
- Démarrer Apache et MySQL dans XAMPP Control Panel

### 3. Accès
- **Frontoffice**: http://localhost/integration/client/controllers/UserController.php?action=showLogin
- **Backoffice**: http://localhost/integration/client/controllers/AdminController.php?action=showLogin

## Configuration reCAPTCHA v2

### Activation
1. Aller sur https://www.google.com/recaptcha/admin
2. Créer un site (reCAPTCHA v2 avec case à cocher)
3. Copier la **Site Key** et la **Secret Key**
4. Modifier `config.php`:

```php
// config.php
define('RECAPTCHA_ENABLED', true);
define('RECAPTCHA_SITE_KEY', 'VOTRE_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'VOTRE_SECRET_KEY');
```

### Fonctionnalités
- reCAPTCHA activé sur les formulaires de connexion et inscription
- Validation côté client et serveur
- Désactivable via `RECAPTCHA_ENABLED = false`

## Fonctionnalités

### Front-office (Client)
- Inscription / Connexion avec reCAPTCHA
- Upload de photo de profil
- Tableau de bord personnel
- Modification du profil

### Back-office (Admin)
- Connexion sécurisée avec reCAPTCHA
- Gestion des utilisateurs (CRUD)
- Upload de photo de profil administrateur
- Tableau de bord administrateur

## Sécurité
- Protection CSRF sur les formulaires
- Validation des uploads (type MIME, taille max 2MB)
- Hachage des mots de passe avec password_hash()
- Protection XSS avec htmlspecialchars()