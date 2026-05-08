# Module de Calendrier Numérique - Smart Garage

## Vue d'ensemble

Ce module implémente un système complet de gestion de rendez-vous ("calendrier RDV") pour l'application Smart Garage. Il permet aux clients de réserver des créneaux horaires en ligne et aux administrateurs de gérer ces réservations via un tableau de bord intuitif.

**Architecte :** MVC (Model-View-Controller)  
**Base de données :** MySQL avec PDO  
**Frontend :** Vanilla JavaScript + CSS responsive  
**Accès client :** `index.php?action=frontCalendar`  
**Accès admin :** `index.php?action=backCalendar`

---

## Architecture Générale

```
┌─────────────────────────────────────────────────┐
│              INDEX.PHP (Router)                 │
│  (Dirige les requêtes vers CalendrierController)│
└────────────────────┬────────────────────────────┘
                     │
        ┌────────────┴─────────────┐
        │                          │
    FRONT (Client)          BACK (Admin)
        │                          │
        ▼                          ▼
┌──────────────────┐       ┌──────────────────┐
│Front Wizard:     │       │Admin Dashboard:  │
│1. Sélect. jour  │       │- Grille semaine  │
│2. Sélect. heure │       │- Gestion statuts │
│3. Formulaire    │       │- Liste RDV       │
│4. Confirmation  │       │- Export CSV      │
└──────────────────┘       └──────────────────┘
```

---

## 1. Base de Données

### Nouvelles tables créées :

#### **Table : `creneau_atelier`**
Stocke les **créneaux horaires disponibles** (intervalles d'1h : 8h-9h, 9h-10h, etc.)

```sql
CREATE TABLE creneau_atelier (
  id_creneau INT PRIMARY KEY AUTO_INCREMENT,
  date_creneau DATE NOT NULL,
  heure_debut TIME NOT NULL,           -- ex: 08:00:00
  heure_fin TIME NOT NULL,             -- ex: 09:00:00
  capacite INT DEFAULT 3,              -- max RDV par créneau
  est_heure_creuse TINYINT DEFAULT 0,  -- 1 si 13h-15h (heures creuses)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Logique :** 
- Chaque créneau peut accueillir 3 rendez-vous max
- Les **heures creuses** (13h-15h) bénéficient d'une **réduction 15%** automatique

#### **Table : `rendezvous_digital`**
Stocke les **rendez-vous booked** par les clients

```sql
CREATE TABLE rendezvous_digital (
  id_rdv INT PRIMARY KEY AUTO_INCREMENT,
  id_vehicle INT NOT NULL,
  id_client INT NOT NULL,
  id_creneau INT NOT NULL,
  type_intervention ENUM('Révision', 'Réparation', 'Freinage', 'Bougies', 
                         'Autre') NOT NULL,
  description TEXT,
  statut ENUM('En attente', 'Confirmé', 'En cours', 'Terminé', 
              'Annulé') DEFAULT 'En attente',
  remise_eco_appliquee DECIMAL(5,2) DEFAULT 0,  -- % de réduction heures creuses
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (id_vehicle) REFERENCES document(id_vehicle),
  FOREIGN KEY (id_creneau) REFERENCES creneau_atelier(id_creneau)
);
```

**Logique :**
- Lie un créneau à un véhicule et un client
- Enregistre le type d'intervention et une description
- Suit l'historique des statuts (En attente → Confirmé → Terminé)
- Applique automatiquement la réduction eco si heure creuse

#### **Table extension : `document` (véhicules)**
Ajout de colonnes pour support du calendrier
```sql
-- id_vehicle, marque, modele, immatriculation (existaient déjà)
```

---

## 2. Architecture Modèle (Models)

### **CreneauModel.php**
Gère les **créneaux horaires**

**Responsabilités principales :**
```php
// Crée les créneaux d'un mois automatiquement (lun-sam, 8h-17h)
ensureMonthSlots($month, $year)
  → Crée 48 créneaux par semaine (6 jours × 8h)

// Récupère les slots disponibles d'un jour
getDaySlots($date)
  → Retourne : [id, heure_debut, heure_fin, capacite, nb_rdv_actuels, places_libres]

// Récupère la disponibilité du mois (pour affichage client)
getMonthAvailability()
  → Retourne : tous les jours + indicateurs (rouge/orange/vert)

// Données pour la grille d'admin (semaine)
getWeekGridCounts($weekStart, $weekEnd)
  → Retourne : activité par jour×heure pour affichage grille
```

**Logique clé :**
```javascript
// Détection heures creuses
if (heure >= 13 && heure < 15) {
  est_heure_creuse = 1  // Remise 15% appliquée au RDV
}
```

---

### **RendezvousModel.php**
Gère les **rendez-vous** (cycle de vie complet)

**Responsabilités principales :**
```php
// Création avec validation + vérif capacité
create($data)
  → Vérifie places_libres > 0
  → Sauvegarde RDV + applique remise si heure creuse
  → Retourne id_rdv

// Mise à jour statut (transition d'état)
updateStatus($idRdv, $newStatus)
  → En attente → Confirmé (client arrivé)
  → Confirmé → En cours (réparation débute)
  → En cours → Terminé (réparation finie)
  → N'importe quel → Annulé (annulation)

// Recherche paginée avec filtres
getFiltered($filters, $limit, $offset)
  → Filtre par : statut, date, nom client, téléphone, immatriculation
  → Retourne : 10 RDV par page pour liste admin

// Statistiques pour dashboard
getQuickStats($dayStart, $dayEnd, $weekStart, $weekEnd)
  → Compte : RDV du jour, semaine, en attente, taux remplissage %
```

**Logique de validation :**
```php
// Avant création :
1. Vérifier capacité disponible du créneau
2. Vérifier phone = format +33 701 234 567
3. Vérifier date_rdv >= date_du_jour
4. Vérifier type_intervention dans ENUM valides
```

---

### **VehicleModel.php**
Gère les **véhicules** (find-or-create pattern)

**Responsabilités principales :**
```php
// Cherche véhicule par immatriculation (normalisée)
findByImmatriculation($plate)
  → Normalise : UPPER + trim
  → Retourne id_vehicle ou null

// Crée ou retrouve véhicule (idempotent)
findOrCreate($data)
  → SI véhicule existe : retourne son id
  → SINON : crée + retourne nouvel id
  → Permet réutilisation véhicule d'un client à l'autre
```

**Logique clé :**
```php
// Normalisation immatriculation
$plate = strtoupper(trim($plate));
$plate = preg_replace('/\s+/', ' ', $plate);  // Collapse espaces

// Recherche insensible à la casse + espaces extra
```

---

## 3. Architecture Contrôleur

### **CalendrierController.php**
Orchestrateur central (502 lignes, 9 actions publiques)

```
┌─────────────────────────────────────────────┐
│        CalendrierController                 │
│                                             │
│  FRONT (côté client) :                      │
│  ├─ frontCalendar()      : Affiche wizard   │
│  ├─ frontCreate()        : Sauvegarde RDV  │
│  ├─ frontConfirmation()  : Page confirmation│
│  ├─ apiDaySlots()        : AJAX slots/jour │
│                                             │
│  BACK (côté admin) :                        │
│  ├─ backCalendar()       : Grille semaine  │
│  ├─ backSlotDetails()    : AJAX modal      │
│  ├─ backUpdateStatus()   : AJAX maj statut │
│  ├─ backList()           : Liste paginée   │
│  ├─ backCreateManual()   : Création manuelle│
│  ├─ backExportCsv()      : CSV export      │
│                                             │
└─────────────────────────────────────────────┘
```

**Pattern utilisé :**
```php
public function frontCalendar() {
  // 1. Calculer mois actuel
  // 2. Appeler CreneauModel::getMonthAvailability()
  // 3. Passer $slots à la vue
  // 4. Inclure assets CSS/JS spécifiques
  $this->view('front/calendrier.php', [
    'slots' => $slots,
    'extraCss' => 'assets/css/calendrier.css',
    'extraJs' => 'assets/js/calendrier_front.js'
  ]);
}
```

---

## 4. Flux de Données - Côté Client (Front)

### **Workflow de réservation (4 étapes) :**

```
ÉTAPE 1 : Sélection du jour
  ├─ Client voit grille 7×6 (mois)
  ├─ Code couleur : 🟢 places libres / 🟠 peu places / 🔴 complet / ⚪ fermé
  └─ Click jour → load Step 2

      ↓

ÉTAPE 2 : Sélection de l'heure
  ├─ AJAX frontEnd.js : loadSlots(date)
  │  └─ GET index.php?action=apiDaySlots&date=2026-04-15
  │     ├─ Response JSON :
  │     │  [{id:1, heure:"08:00", places:3, etat:"libre"}
  │     │   {id:2, heure:"09:00", places:1, etat:"presque_complet"}]
  ├─ Affiche boutons horaires + indicateurs capacité
  └─ Click heure → select slot → load Step 3

      ↓

ÉTAPE 3 : Formulaire client
  ├─ Champs :
  │  ├─ Nom client (txt)
  │  ├─ Téléphone (txt) → validation regex
  │  ├─ Immatriculation (txt) → auto-recherche véhicule
  │  ├─ Si NOT found → crée nouveau véhicule
  │  ├─ Type intervention (select)
  │  └─ Description (textarea)
  ├─ Validation client-side JS (phone, immat, intervention)
  └─ Click "Autre"? → "Suivant" activé → load Step 4

      ↓

ÉTAPE 4 : Récapitulatif
  ├─ Lecture seule (readonly)
  ├─ Affiche :
  │  ├─ Date + heure (formaté)
  │  ├─ Client nom/téléphone
  │  ├─ Véhicule marque/modèle/immatriculation
  │  ├─ Type intervention
  │  └─ Remise eco [15% si heures creuses]
  └─ Click "Confirmer" → POST frontCreate()
     └─ Redirect confirmation.php
```

**Fichiers impliqués :**
- Vue : [views/front/calendrier.php](views/front/calendrier.php) (252 lignes)
- JS : [assets/js/calendrier_front.js](assets/js/calendrier_front.js) (213 lignes)
- CSS : [assets/css/calendrier.css](assets/css/calendrier.css) (394 lignes)

---

## 5. Flux de Données - Côté Admin (Back)

### **Tableau de bord de gestion :**

```
Dashboard Layout
├─ TOP STAT CARDS
│  ├─ 📊 Rendez-vous du jour : 5
│  ├─ 📅 Rendez-vous semaine : 32
│  ├─ ⏳ En attente confirmations : 8
│  └─ 📈 Taux remplissage semaine : 87%
│
├─ GRILLE SEMAINE (6 lignes × 10 colonnes)
│  ├─ Lignes = jours (lun, mar, mer, jeu, ven, sam)
│  ├─ Colonnes = horaires (8h, 9h, ..., 16h, 17h)
│  ├─ Cellules = nombre RDV / capacité
│  │  └─ Couleur : vert (libre) / orange (1-2) / rouge (complet)
│  ├─ Click cellule → AJAX backSlotDetails()
│  │  └─ Charge modal latéral avec détail créneau
│  │
│  └─ MODAL DÉTAIL CRÉNEAU
│     ├─ Horaire du créneau
│     ├─ Liste RDVs (si existe)
│     │  ├─ Client nom/téléphone
│     │  ├─ Véhicule immat
│     │  ├─ Intervention type
│     │  ├─ Statut actuel (badge)
│     │  └─ Boutons action : "En cours" / "Terminé" / "Annulé"
│     │     └─ AJAX updateStatus() → badge update
│     └─ Formulaire mise à jour capacité
│        ├─ Input capacité (ex: 0 pour bloquer créneau)
│        └─ Bouton "Mettre à jour"
│
└─ BOTTOM SECTION
   ├─ Formulaire création manuelle RDV
   │  ├─ Date + Heure (ou ID créneau)
   │  ├─ Client nom/téléphone/immatriculation
   │  ├─ Type intervention
   │  └─ Bouton "Créer"
   │
   └─ Navigation liens
      ├─ "Voir liste RDV"  → backList()
      └─ "Exporter CSV"    → backExportCsv()
```

**Fichiers impliqués :**
- Vue grille : [views/back/calendrier_admin.php](views/back/calendrier_admin.php) (180 lignes)
- Vue liste : [views/back/rdv_liste.php](views/back/rdv_liste.php) (99 lignes)
- Vue modal : [views/back/rdv_detail_modal.php](views/back/rdv_detail_modal.php) (42 lignes)
- JS : [assets/js/calendrier_back.js](assets/js/calendrier_back.js) (80 lignes)

---

## 6. Sécurité et Validation

### **Côté Serveur (PHP):**

| Risque | Mitigation |
|--------|-----------|
| **Injection SQL** | Requêtes PDO préparées (paramètres bindés) |
| **XSS (HTML injection)** | `htmlspecialchars()` sur toutes données utilisateur en sortie |
| **CSRF** | Sessions PHP native (PHPSESSID) |
| **DoS** | Pagination (10 RDV/page max), limite capacité créneaux |
| **Modification statut invalide** | Whitelist ENUM (En attente, Confirmé, En cours, Terminé, Annulé) |

**Validation des inputs :**
```php
// Exemple : créationRendezvous
$phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
if (!preg_match('/^\+?33\d{8,9}$/', $phone)) {
  throw new Exception("Téléphone invalide");
}

$intervention = $_POST['type_intervention'];
if (!in_array($intervention, ['Révision', 'Réparation', 'Freinage', 'Bougies', 'Autre'])) {
  throw new Exception("Type intervention invalide");
}

// Vérifier capacité créneau
$nbActuels = RendezvousModel::countActiveByCreneau($id_creneau);
if ($nbActuels >= $capacity) {
  throw new Exception("Créneau complet");
}
```

### **Côté Client (JavaScript):**

Validation **préalable** avant envoi serveur (UX améliorée) :
```javascript
function validateStepThree() {
  let errors = [];
  
  if (!nomClient || nomClient.length < 2)
    errors.push("Nom invalide");
  
  if (!phoneRegex.test(telephone))
    errors.push("Téléphone invalide");
  
  if (!immatriculation || immatriculation.length < 3)
    errors.push("Immatriculation invalide");
  
  if (errors.length > 0) {
    showErrors(errors);
    return false;
  }
  return true;
}
```

---

## 7. Intégration au Routing Principal

### **index.php (router)**

```php
// Nouveau routage calendrier
$action = $_GET['action'] ?? 'home';

switch ($action) {
  case 'frontCalendar':
    require_once 'controllers/CalendrierController.php';
    (new CalendrierController())->frontCalendar();
    break;
    
  case 'frontCreate':
    (new CalendrierController())->frontCreate();
    break;
    
  case 'backCalendar':
    // Vérifier authentification admin...
    (new CalendrierController())->backCalendar();
    break;
    
  case 'apiDaySlots':  // AJAX
    header('Content-Type: application/json');
    (new CalendrierController())->apiDaySlots();
    break;
    
  case 'backSlotDetails':  // AJAX
    header('Content-Type: text/html');
    (new CalendrierController())->backSlotDetails();
    break;
    
  // ... autres actions
}
```

**Injection assets personnalisés dans layout :**
```php
// controllers/BaseController.php
protected function view($file, $data = []) {
  extract($data);
  $extraCss = $extraCss ?? '';
  $extraJs = $extraJs ?? '';
  
  include "views/back/layout_header.php";  // Charge $extraCss ici
  include "views/$file";
  include "views/back/layout_footer.php";  // Charge $extraJs ici
}
```

---

## 8. Interface Utilisateur (UX/UI)

### **Théming :**
```css
/* Variables CSS héritées du projet */
:root {
  --bg-primary: #0A192F;        /* Fond très sombre */
  --bg-card: #112A45;
  --accent: #00E5FF;            /* Cyan */
  --text-primary: #E0E6ED;
  --border-color: #1E3A5F;
}

/* Calendrier spécifique */
.day-cell.available { background: #1B4D2F; }    /* Vert foncé */
.day-cell.limited   { background: #4D4D1B; }    /* Jaune foncé */
.day-cell.full      { background: #4D1B1B; }    /* Rouge foncé */

.status-badge.confirme  { background: #00A878; }
.status-badge.termine   { background: #0066AA; }
.status-badge.annule    { background: #CC3333; }
```

### **Responsive Design :**
```css
/* Desktop (1000px+) */
.admin-calendar-wrap { display: grid; grid-template-columns: 1fr 350px; }

/* Tablet (768px - 1000px) */
@media (max-width: 1000px) {
  .admin-calendar-wrap { display: block; }
  .slot-sidebar { width: 100%; margin-top: 2rem; }
}

/* Mobile (< 768px) */
@media (max-width: 768px) {
  .week-grid-table { font-size: 12px; }
  .grid-cell { padding: 4px; }
}
```

---

## 9. Points Techniques Clés

### **Pattern : Auto-création des créneaux**
```php
// Ne pas attendre que l'admin crée les créneaux manuellement
// → Lors du chargement du mois, si créneaux n'existent pas :
function frontCalendar() {
  $month = date('n');
  $year = date('Y');
  
  CreneauModel::ensureMonthSlots($month, $year);  // ← Auto-crée !
  $slots = CreneauModel::getMonthAvailability();
}
```
✅ **Avantage :** Pas de dépendance admin, expérience seamless

### **Pattern : Find-or-Create pour véhicules**
```php
// Si client entre immatriculation "AA-123-BB" :
// 1. Chercher véhicule existant (même client ou autre)
// 2. Si existe → réutiliser
// 3. Si n'existe pas → créer automatiquement
$id_vehicle = VehicleModel::findOrCreate([
  'immatriculation' => 'AA-123-BB',
  'marque' => 'Peugeot',
  'modele' => '308'
]);
```
✅ **Avantage :** Pas de saisie redondante, données cohérentes

### **Pattern : Remise automatique heures creuses**
```php
// Si créneau entre 13h et 15h (heure creuse)
// → Appliquer automatiquement 15% remise
if ($slot['est_heure_creuse']) {
  $remise = 15;  // %
  $rdv['remise_eco_appliquee'] = $remise;
}
```
✅ **Avantage :** Encourage réservations hors-pic, optimise taux remplissage

### **Pattern : AJAX sans page reload**
```javascript
// Admin click horaire créneau
gridCell.addEventListener('click', function() {
  loadSlotDetails(idCreneau);  // ← AJAX
  // Modal se remplit sans rechargement page
});

function loadSlotDetails(idCreneau) {
  fetch('?action=backSlotDetails&id=' + idCreneau)
    .then(r => r.text())
    .then(html => {
      sidebarContent.innerHTML = html;  // ← DOM update
    });
}
```
✅ **Avantage :** UX fluide, pas de flicker page reload

---

## 10. Flux d'Erreur et Gestion d'Exceptions

```
CLIENT SUBMITS FORM
    ↓
PHP frontCreate()
    ├─ Valide inputs (phone, immat, etc.)
    │  └─ ❌ ERREUR ?
    │     └─ Session['errors'] = message
    │     └─ Redirect calendrier.php
    │        └─ Affiche $_SESSION['errors'] en haut page
    │
    ├─ Vérifie capacité créneau
    │  └─ ❌ ERREUR (complet) ?
    │     └─ Idem error handling
    │
    ├─ Crée véhicule si nécessaire
    ├─ Crée rendez-vous
    │  └─ ✅ SUCCESS
    │     └─ Redirect confirmation.php
    │        └─ Affiche "RDV confirmé!"
    │
    └─ Fin
```

**Codes statut HTTP utilisés :**
- `200` : Succès
- `400` : Erreur validation (client)
- `409` : Conflit (créneau plein)
- `500` : Erreur serveur (DB exception)

---

## 11. Résumé des Fichiers Créés/Modifiés

| Fichier | Lignes | Rôle |
|---------|--------|------|
| **Contrôleur** |
| CalendrierController.php | 502 | Orchestrateur, 9 actions |
| **Modèles** |
| CreneauModel.php | 173 | Gestion créneaux |
| RendezvousModel.php | 342 | Gestion RDV + cycles |
| VehicleModel.php | 61 | Find-or-create véhicules |
| **Vues Front** |
| views/front/calendrier.php | 252 | Wizard 4 étapes |
| views/front/confirmation.php | 28 | Page de succès |
| **Vues Back** |
| views/back/calendrier_admin.php | 180 | Grille semaine + stats |
| views/back/rdv_liste.php | 99 | Liste paginée filtrée |
| views/back/rdv_detail_modal.php | 42 | Modal créneau détail (AJAX) |
| **Frontend** |
| assets/js/calendrier_front.js | 213 | Wizard interactif |
| assets/js/calendrier_back.js | 80 | AJAX admin interactions |
| assets/css/calendrier.css | 394 | Styling responsive |
| **Base de Données** |
| database/smart_garage.sql | ++ | 3 nouvelles tables + FK |
| **Intégration** |
| index.php | +60 | Routes calendrier |

**Total :** ~2700 lignes de code et configuration ✅

---

## 12. Utilisation

### **Pour le client (Front) :**
```
1. Ouvrir dans navigateur :
   http://localhost/smart%20grage/index.php?action=frontCalendar

2. Workflow :
   □ Cliquer un jour dans la grille
   □ Sélectionner une heure
   □ Remplir infos client + véhicule
   □ Valider récapitulatif
   → Page confirmation avec n° RDV
```

### **Pour l'admin (Back) :**
```
1. Dashboard principal :
   http://localhost/smart%20grage/index.php?action=backCalendar
   
2. Actions disponibles :
   □ Cliquer cellule grille → voir détails créneau
   □ Cliquer status badge → changer statut (AJAX)
   □ Formulaire bas → créer RDV manuel
   □ Lien "Voir liste RDV" → rdv_liste.php
   □ Bouton "Exporter CSV" → télécharger liste

3. Gestion avancée :
   http://localhost/smart%20grage/index.php?action=backRdvList
   
   □ Filtrer par statut / date / nom / immat
   □ Paginer les résultats (10 par page)
   □ Exporter en CSV pour traitement Excel
```

---

## Conclusion

Ce module **calendrier RDV** démontre :
- ✅ **Architecture MVC propre** (separation of concerns)
- ✅ **Validation rouste** (serveur + client)
- ✅ **Sécurité SQL** (PDO prepared statements)
- ✅ **UX fluide** (pas de rechargement, AJAX)
- ✅ **Données normalité** (find-or-create, immats)
- ✅ **Responsive design** (desktop/tablet/mobile)
- ✅ **Métier intelligente** (auto-créneaux, remise heures creuses)

**Pour poser des questions ou accéder à l'interface :** Consultez les URL d'accès dans la section "Utilisation".

