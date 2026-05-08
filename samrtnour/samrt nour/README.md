# Smart Garage System (samrtnour)

Application PHP (sans framework) de gestion des pieces automobiles et des commandes clients, organisee avec une architecture propre et modulaire.

## Objectif

Cette refonte applique des principes de clean architecture:

- separation des responsabilites (SRP),
- controleurs specialises,
- modeles specialises,
- validation centralisee et reutilisable,
- routeur central unique via index.php.

## Arborescence

samrtnour/
- index.php
- README.md
- .env
- .env.example
- .gitignore
- config/
  - Database.php
- models/
  - Piece.php
  - Commande.php
- controllers/
  - PieceController.php
  - CommandeController.php
- views/
  - front/
  - back/
- views/assets/
- database/
  - smart_garage_system.sql

## Separation des responsabilites

### Routeur central (index.php)

- Initialise la session avec session_start().
- Lit action depuis l'URL.
- Redirige chaque action vers le bon controleur.

### Modele Piece (models/Piece.php)

Responsable uniquement des operations CRUD sur la table pieces:

- getAll()
- getById($id)
- insert(array $data)
- update($id, array $data)
- delete($id)

### Modele Commande (models/Commande.php)

Responsable uniquement des operations CRUD sur la table commandes:

- getAll()
- create(array $data)
- delete($id)

La methode create() utilise une transaction PDO:

1. SELECT ... FOR UPDATE pour verrouiller le stock.
2. Verification du stock.
3. Insertion de la commande.
4. Decrementation du stock.
5. COMMIT ou ROLLBACK en cas d'echec.

### Controleur Piece (controllers/PieceController.php)

Responsable uniquement des actions pieces:

- showCatalogue()
- dashboard()
- managePieces()
- addPiece()
- updatePiece()
- confirmDeletePiece()
- deletePiece()

Note: dashboard() utilise Piece et Commande pour les statistiques.

### Controleur Commande (controllers/CommandeController.php)

Responsable uniquement des actions commandes:

- orderPiece()
- manageCommandes()
- deleteCommande()

Important: la verification metier du stock reste dans orderPiece().

### Helper de validation (controllers/ValidationHelper.php)

Validation pure (sans acces DB) via methodes statiques:

- validatePiece($data)
- validateCommande($data)

Chaque methode retourne:

[
  'errors' => [],
  'sanitized' => []
]

## Base de donnees

Le schema repose sur les tables:

- pieces
- commandes

Le fichier SQL doit etre nomme:

- database/smart_garage_system.sql

## Routage

- showCatalogue -> PieceController::showCatalogue()
- orderPiece -> CommandeController::orderPiece()
- dashboard -> PieceController::dashboard()
- managePieces -> PieceController::managePieces()
- addPiece -> PieceController::addPiece()
- editPiece -> PieceController::updatePiece()
- confirmDeletePiece -> PieceController::confirmDeletePiece()
- deletePiece -> PieceController::deletePiece()
- manageCommandes -> CommandeController::manageCommandes()
- deleteCommande -> CommandeController::deleteCommande()
- default -> PieceController::showCatalogue()

## Demarrage

1. Configurer .env.
2. Importer database/smart_garage_system.sql dans MariaDB.
3. (Option PDF) Installer Dompdf hors du dossier du projet et definir SMART_GARAGE_VENDOR_AUTOLOAD vers le fichier autoload.php externe.
4. Ouvrir index.php dans le navigateur.
