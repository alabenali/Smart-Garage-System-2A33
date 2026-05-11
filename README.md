[README.md](https://github.com/user-attachments/files/27598754/README.md)
# 🚗 Smart Garage — Système de Gestion de Garage Intelligent

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Status](https://img.shields.io/badge/status-en%20développement-orange)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 📋 Table des matières

- [Description](#-description)
- [Fonctionnalités](#-fonctionnalités)
- [Architecture du projet](#-architecture-du-projet)
- [Modules](#-modules)
- [Installation](#-installation)
- [Utilisation](#-utilisation)
- [APIs](#-apis)
- [Design System](#-design-system)
- [Équipe](#-équipe)

---

## 📖 Description

**Smart Garage** est une application web de gestion complète pour garages automobiles.  
Elle centralise la gestion des clients, des véhicules, des rendez-vous, des réparations et des mécaniciens dans une interface moderne et unifiée.

---

## ✨ Fonctionnalités

- 📅 **Gestion des rendez-vous** — Prise en charge, planification et suivi
- 🚘 **Gestion des véhicules** — Fiche complète par véhicule et historique
- 👤 **Gestion des clients** — Base de données clients avec historique complet
- 🔧 **Gestion des réparations** — Suivi des interventions et des pièces utilisées
- 👨‍🔧 **Gestion des mécaniciens** — Profils, disponibilités et affectations
- 📦 **Gestion du stock** — Suivi des pièces et consommables
- 📊 **Tableau de bord** — Vue globale et statistiques en temps réel
- 🔐 **Authentification** — Gestion des rôles et accès sécurisés

---

## 🗂 Architecture du projet

```
smart-garage/
│
├── integration/                  # Dossier principal — tous les modules intégrés
│   ├── index.html                # Point d'entrée global
│   ├── assets/                   # Ressources globales partagées
│   │   ├── css/
│   │   │   └── global.css        # Design system commun
│   │   ├── js/
│   │   │   └── router.js         # Système de navigation global
│   │   └── images/
│   │
│   ├── dashboard/                # Module Tableau de bord
│   ├── client/                   # Module Clients
│   ├── vehicule/                 # Module Véhicules
│   ├── rendezvous/               # Module Rendez-vous
│   ├── reparation/               # Module Réparations
│   ├── stock/                    # Module Stock
│   ├── mecanicien/               # Module Mécaniciens ← nouveau
│   └── auth/                     # Module Authentification
│
├── backend/                      # API REST (si applicable)
│   ├── routes/
│   ├── controllers/
│   ├── models/
│   └── config/
│
├── database/                     # Scripts SQL / migrations
│
└── README.md
```

---

## 🧩 Modules

| Module | Description | Statut |
|--------|-------------|--------|
| `dashboard` | Vue d'ensemble et statistiques | ✅ Intégré |
| `auth` | Connexion, rôles, sécurité | ✅ Intégré |
| `client` | CRUD clients + historique | ✅ Intégré |
| `vehicule` | CRUD véhicules + fiches | ✅ Intégré |
| `rendezvous` | Calendrier et planification | ✅ Intégré |
| `reparation` | Suivi des interventions | ✅ Intégré |
| `stock` | Gestion des pièces | ✅ Intégré |
| `mecanicien` | Profils et affectations | ✅ Intégré |

---

## ⚙️ Installation

### Prérequis

- Navigateur moderne (Chrome, Firefox, Edge)
- Serveur local : **XAMPP**, **WAMP**, **Live Server** ou **Node.js**
- Base de données : **MySQL** (si backend activé)

### Lancement (Frontend uniquement)

```bash
# Cloner le projet
git clone https://github.com/votre-username/smart-garage.git

# Accéder au dossier
cd smart-garage

# Ouvrir avec Live Server ou directement dans le navigateur
open integration/index.html
```

### Lancement avec backend

```bash
# Installer les dépendances
npm install

# Configurer la base de données
# → Importer database/smart_garage.sql dans phpMyAdmin ou MySQL

# Démarrer le serveur
npm start
# ou
node backend/server.js
```

---

## 🚀 Utilisation

1. Ouvrir `integration/index.html` dans le navigateur
2. Se connecter avec les identifiants par défaut :
   - **Admin** : `admin` / `admin123`
   - **Mécanicien** : `meca` / `meca123`
3. Naviguer entre les modules via le menu latéral

---

## 🔌 APIs

> Les endpoints suivants sont disponibles si le backend est activé.

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/clients` | Liste des clients |
| `POST` | `/api/clients` | Créer un client |
| `PUT` | `/api/clients/:id` | Modifier un client |
| `DELETE` | `/api/clients/:id` | Supprimer un client |
| `GET` | `/api/vehicules` | Liste des véhicules |
| `GET` | `/api/rendezvous` | Liste des rendez-vous |
| `GET` | `/api/mecaniciens` | Liste des mécaniciens |
| `GET` | `/api/reparations` | Liste des réparations |
| `GET` | `/api/stock` | État du stock |

---

## 🎨 Design System

L'application utilise un design system unifié appliqué à tous les modules :

| Élément | Valeur |
|---------|--------|
| **Couleur primaire** | `#1a73e8` (Bleu) |
| **Couleur secondaire** | `#34a853` (Vert) |
| **Couleur danger** | `#ea4335` (Rouge) |
| **Font principale** | `Inter`, `Segoe UI`, sans-serif |
| **Border radius** | `8px` (cards), `4px` (buttons) |
| **Shadow** | `0 2px 8px rgba(0,0,0,0.1)` |

---

## 👥 Équipe

| Nom | Rôle |
|-----|------|
| *(Votre nom)* | Développeur Full Stack |

---

## 📄 Licence

Ce projet est sous licence **MIT** — voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

> **Smart Garage** — Gérez votre garage, simplement. 🔧
