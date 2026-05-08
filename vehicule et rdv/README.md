# Smart Garage - Presentation du projet

## 1. Vue d'ensemble

Smart Garage est une application web de gestion de garage automobile developpee en PHP, MySQL, HTML, CSS et JavaScript.

Le projet couvre deux espaces principaux :

- Front Office : partie client pour consulter les vehicules, ajouter un vehicule et prendre un rendez-vous.
- Back Office : partie administration pour gerer les vehicules, suivre les rendez-vous, consulter les statistiques et organiser le planning atelier.

L'objectif est de digitaliser un garage automobile en simplifiant trois operations importantes :

- l'enregistrement des vehicules ;
- la prise de rendez-vous en ligne ;
- le suivi des pannes et des interventions depuis l'administration.

---

## 2. Probleme traite

Dans un garage classique, la gestion des rendez-vous se fait souvent par telephone ou manuellement sur papier. Cela peut provoquer :

- des oublis de rendez-vous ;
- des erreurs dans les horaires ;
- un manque de visibilite sur les places disponibles ;
- une mauvaise organisation des interventions ;
- une perte d'informations sur la panne du client.

Smart Garage repond a ce probleme avec un systeme centralise qui permet au client de declarer sa panne et au garage de mieux preparer l'intervention.

---

## 3. Fonctionnalites principales

### Gestion des vehicules

- Ajouter un vehicule avec marque, modele, immatriculation, couleur, annee, kilometrage et carburant.
- Valider les champs obligatoires.
- Normaliser les immatriculations tunisiennes.
- Afficher les plaques au format visuel tunisien.
- Modifier ou supprimer un vehicule depuis le Back Office.
- Rechercher un vehicule dans la liste admin par ID, marque, modele, immatriculation, couleur, annee, kilometrage ou carburant.
- Proposer des marques automatiquement grace a une liste de suggestions.

### Calendrier et rendez-vous

- Generer automatiquement les creneaux atelier.
- Afficher les disponibilites par jour.
- Bloquer les jours passes, les dimanches et les jours feries.
- Afficher les creneaux disponibles selon la capacite restante.
- Gerer une capacite maximale par creneau.
- Appliquer une remise automatique de 15 % sur les heures creuses.
- Confirmer un rendez-vous via un assistant en plusieurs etapes.

### Declaration de panne

- Choisir le type de panne.
- Indiquer les circonstances de la panne.
- Decrire les symptomes observes.
- Selectionner des temoins de panne : voyant, bruit, fumee, fuite, vehicule immobilise.
- Joindre jusqu'a 5 photos de la panne.
- Limiter chaque photo a 10 Mo.
- Accepter les formats JPG, PNG et WEBP.
- Sauvegarder les informations de panne et les images avec le rendez-vous.

### Back Office

- Tableau de bord avec statistiques.
- Nombre total de vehicules.
- Kilometrage moyen.
- Repartition par marque et carburant.
- Nombre total de rendez-vous.
- Rendez-vous du jour et de la semaine.
- Suivi des rendez-vous par statut : En attente, Confirme, En cours, Termine, Annule.
- Calendrier atelier par semaine.
- Modification de la capacite d'un creneau.
- Creation manuelle d'un rendez-vous par l'administrateur.
- Liste des rendez-vous avec filtres.
- Export des rendez-vous en CSV et PDF.

---

## 4. Architecture du projet

Le projet suit une organisation proche du modele MVC.

```text
smart grage/
├── config/
│   └── Database.php
├── controllers/
│   ├── VehicleController.php
│   └── CalendrierController.php
├── database/
│   └── smart_garage.sql
├── helpers/
│   └── PlateHelper.php
├── models/
│   ├── Vehicle.php
│   ├── VehicleModel.php
│   ├── CreneauModel.php
│   └── RendezvousModel.php
├── views/
│   ├── back/
│   ├── front/
│   ├── css/
│   ├── js/
│   └── images/
└── index.php
```

### Role des dossiers

- `index.php` : routeur principal de l'application.
- `controllers/` : contient la logique principale et traite les actions utilisateur.
- `views/` : contient les pages affichees au client et a l'administrateur.
- `models/` : contient les objets metier.
- `database/` : contient le script SQL de creation de la base.
- `helpers/` : contient les fonctions reutilisables, par exemple le formatage des plaques.
- `config/` : contient la connexion PDO a MySQL.

---

## 5. Base de donnees

La base de donnees s'appelle `smart_garage`.

Elle contient trois tables principales :

### Table `vehicle`

Elle stocke les informations des vehicules :

- marque ;
- modele ;
- immatriculation ;
- couleur ;
- annee ;
- kilometrage ;
- carburant ;
- date d'ajout.

### Table `creneau_atelier`

Elle stocke les creneaux disponibles dans l'atelier :

- date et heure ;
- heure creuse ou non ;
- capacite maximale.

### Table `rendezvous_digital`

Elle stocke les rendez-vous :

- creneau choisi ;
- informations client ;
- vehicule lie ;
- type d'intervention ;
- description de panne ;
- circonstances ;
- temoins de panne ;
- donnees JSON de panne ;
- photos jointes ;
- remise appliquee ;
- statut du rendez-vous.

---

## 6. Technologies utilisees

- PHP : logique serveur et controleurs.
- MySQL : stockage des vehicules, creneaux et rendez-vous.
- PDO : connexion securisee a la base de donnees.
- HTML/CSS : structure et design des interfaces.
- JavaScript : interactions dynamiques, calendrier, upload et previews des photos.
- Bootstrap Icons : icones de l'interface.
- XAMPP : environnement local Apache + MySQL.

---

## 7. Installation et lancement

1. Placer le dossier du projet dans :

```text
C:\xampp\htdocs\smart grage
```

2. Demarrer Apache et MySQL depuis XAMPP.

3. Importer le fichier SQL :

```text
database/smart_garage.sql
```

4. Verifier la configuration de connexion dans :

```text
config/Database.php
```

Par defaut :

```php
$host = 'localhost';
$dbname = 'smart_garage';
$username = 'root';
$password = '';
```

5. Ouvrir l'application dans le navigateur :

```text
http://localhost/smart%20grage/index.php
```

---

## 8. Liens importants

### Front Office

```text
index.php?action=showVehicles
index.php?action=addVehicle
index.php?action=frontCalendar
```

### Back Office

```text
index.php?action=dashboard
index.php?action=manageVehicles
index.php?action=backCalendar
index.php?action=backRdvList
```

---

## 9. Scenario de demonstration devant le jury

### Scenario 1 : Ajouter un vehicule

1. Ouvrir la page d'ajout de vehicule.
2. Saisir une marque, un modele, une immatriculation tunisienne, la couleur, l'annee, le kilometrage et le carburant.
3. Montrer la validation des champs.
4. Enregistrer le vehicule.
5. Aller dans le Back Office et montrer que le vehicule apparait dans la liste.
6. Utiliser la barre de recherche pour retrouver le vehicule rapidement.

### Scenario 2 : Prendre un rendez-vous client

1. Ouvrir le calendrier cote client.
2. Choisir un jour disponible.
3. Choisir un creneau horaire.
4. Remplir la declaration de panne.
5. Ajouter une ou plusieurs photos de la panne.
6. Passer a la confirmation.
7. Valider le rendez-vous.
8. Montrer la page de confirmation.

### Scenario 3 : Gestion administrative

1. Aller dans le Dashboard.
2. Presenter les statistiques : vehicules, rendez-vous, carburants, marques.
3. Ouvrir le calendrier Back Office.
4. Montrer les rendez-vous par creneau.
5. Modifier le statut d'un rendez-vous.
6. Aller dans la liste des rendez-vous.
7. Utiliser les filtres.
8. Exporter les donnees en CSV ou PDF.

---

## 10. Discours pret a presenter au jury

Bonjour,

Je vais vous presenter mon projet intitule Smart Garage. C'est une application web destinee a digitaliser la gestion d'un garage automobile.

L'idee de depart est simple : dans un garage, la gestion des vehicules, des rendez-vous et des pannes peut devenir difficile lorsqu'elle est faite manuellement. Il peut y avoir des erreurs de planning, des pertes d'informations, ou un manque de preparation avant l'arrivee du client. Mon application propose donc une solution centralisee pour organiser ces operations.

Le projet est divise en deux parties. La premiere partie est le Front Office, destinee au client. Le client peut consulter les vehicules, ajouter un vehicule et surtout prendre un rendez-vous en ligne. Pour prendre un rendez-vous, il passe par un assistant en plusieurs etapes : il choisit le jour, le creneau horaire, puis il remplit une declaration de panne. Dans cette declaration, il peut choisir le type de panne, indiquer les circonstances, decrire les symptomes, selectionner les temoins de panne et ajouter des photos. Cela permet au garage d'avoir des informations claires avant meme que le vehicule arrive.

La deuxieme partie est le Back Office, destinee a l'administrateur du garage. L'administrateur peut gerer les vehicules, rechercher rapidement un vehicule, consulter les rendez-vous, suivre leur statut et visualiser le planning de l'atelier. Il dispose aussi d'un tableau de bord avec des statistiques comme le nombre de vehicules, le kilometrage moyen, les rendez-vous du jour et de la semaine, ainsi que la repartition par marque ou carburant.

Sur le plan technique, j'ai utilise PHP avec une organisation proche du modele MVC. Le fichier `index.php` joue le role de routeur. Les controleurs traitent les actions, les vues affichent les pages, et la base MySQL stocke les donnees. Pour la connexion a la base, j'ai utilise PDO, ce qui permet d'ecrire des requetes preparees et de mieux securiser l'application.

J'ai aussi ajoute plusieurs validations importantes. Par exemple, les champs obligatoires des vehicules sont verifies, les immatriculations sont normalisees, les rendez-vous ne peuvent pas etre pris sur un creneau complet, et les photos de panne sont limitees a cinq fichiers de 10 Mo maximum.

Un autre point important du projet est la gestion du calendrier. Les creneaux sont generes automatiquement pour les jours de travail. Le systeme evite les dimanches, les jours passes et les jours feries. Les heures creuses permettent aussi d'appliquer une remise automatique, ce qui rend le planning plus intelligent.

Pour conclure, Smart Garage est une application qui repond a un besoin concret : mieux organiser le travail d'un garage, reduire les erreurs et ameliorer la communication entre le client et l'administrateur. Ce projet m'a permis de travailler sur la base de donnees, la logique serveur, les interfaces utilisateur, la validation des formulaires, l'upload d'images et la gestion dynamique d'un calendrier.

Merci pour votre attention.

---

## 11. Points forts a mettre en avant

- Application complete avec Front Office et Back Office.
- Utilisation d'une architecture claire.
- Gestion dynamique des rendez-vous.
- Declaration de panne professionnelle.
- Upload d'images avec controle de format, taille et nombre.
- Recherche dans la liste des vehicules.
- Tableau de bord avec statistiques.
- Export CSV et PDF.
- Gestion des jours feries tunisiens.
- Affichage personnalise des plaques tunisiennes.

---

## 12. Ameliorations futures possibles

- Ajouter un systeme d'authentification pour proteger le Back Office.
- Ajouter des roles : administrateur, mecanicien, receptionniste.
- Envoyer une confirmation par email ou SMS au client.
- Ajouter un historique complet des interventions par vehicule.
- Ajouter une facture ou un devis apres diagnostic.
- Ajouter une recherche avancee avec pagination sur toutes les listes.
- Ajouter une interface mobile dediee.

---

## 13. Conclusion

Smart Garage est un projet web pratique et evolutif. Il montre comment une application peut aider un garage a passer d'une gestion manuelle a une gestion numerique plus claire, plus rapide et plus fiable.

Le projet met en avant des competences en PHP, MySQL, JavaScript, structuration MVC, validation de donnees, gestion de fichiers, creation d'interfaces et organisation d'un workflow metier complet.
