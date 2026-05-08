# Integration Client / Vehicule / RDV

## Migration SQL

Executer le script suivant apres avoir importe les bases `garage1` et `smart_garage` :

```text
database/migrations/client_vehicle_rdv_integration.sql
```

La migration ajoute :

- `vehicle.id_client`
- `rendezvous_digital.id_client`
- index relationnels
- FK vers `garage1.user(id)` avec `ON DELETE SET NULL`

La colonne historique `rendezvous_digital.id_vehicle` reste conservee pour compatibilite. Les APIs exposent aussi l alias metier `id_vehicule`.

## Routes API

```text
GET  api/clients/vehicles.php?id_client=12
GET  api/clients/rdv.php?id_client=12
GET  api/vehicules/rdv.php?id_vehicule=8
POST api/rendez-vous
```

Routes compatibles via le routeur principal :

```text
index.php?action=apiClientVehicles&id_client=12
index.php?action=apiClientRdv&id_client=12
index.php?action=apiVehicleRdv&id_vehicule=8
```

## Exemple POST RDV

```json
{
  "id_client": 12,
  "id_vehicule": 8,
  "id_creneau": 31,
  "type_intervention": "Freinage",
  "description_panne": "Pedale de frein molle et voyant frein allume.",
  "circonstances_panne": "En roulant",
  "temoins_panne": ["voyant frein allume", "freinage spongieux"],
  "statut": "En attente",
  "notes": "Client prioritaire"
}
```

Si `id_client` ou `id_vehicule` est fourni, le controleur verifie :

- existence du client dans `garage1.user`
- existence du vehicule dans `smart_garage.vehicle`
- appartenance du vehicule au client

En cas d erreur, la reponse est un `422` avec un message clair.

## Exemple GET client avec vehicules

```json
{
  "success": true,
  "data": {
    "id_client": 12,
    "nom": "Ben Ali",
    "prenom": "Sami",
    "email": "sami@example.com",
    "vehicles": [
      {
        "id_vehicule": 8,
        "id_client": 12,
        "marque": "Peugeot",
        "modele": "208",
        "immatriculation": "123TU4567"
      }
    ]
  }
}
```

## Exemple GET vehicule avec RDV

```json
{
  "success": true,
  "data": {
    "id_vehicule": 8,
    "id_client": 12,
    "marque": "Peugeot",
    "modele": "208",
    "rendez_vous": [
      {
        "id_rdv": 55,
        "id_client": 12,
        "id_vehicule": 8,
        "type_intervention": "Freinage",
        "statut": "En attente",
        "urgence_score": 8
      }
    ]
  }
}
```

## Scoring urgence enrichi

Le scoring existant reste actif. Il est enrichi par :

- historique RDV client
- annulations client
- frequence de pannes par vehicule
- score sante vehicule base sur kilometrage, age et historiques urgents

La configuration se trouve dans `config/urgence.php`.

## Integrations preservees

- Telegram : notification admin apres creation RDV.
- PHPMailer : confirmation client si `SMTP_PASS` est configure dans `config/constants.php` ou `config/secrets.php`.
- Export PDF/CSV : flux existants conserves.
- CRON : aucun changement de contrat sur les tables RDV existantes.
