# samrtnour (bridge)

Ce dossier sert de **point d’entrée stable** après collage du module `samrt nour/` (avec espace) dans ce projet.

Objectifs :
- Rendre l’accès à `samrt nour/` simple via `http://localhost/integration/samrtnour/`.
- Fournir un petit endpoint de vérification (`api/status.php`) sans toucher aux modules existants.

## Accès

- Hub : `http://localhost/integration/samrtnour/`
- Module interne : `http://localhost/integration/samrtnour/samrt%20nour/`
- Status JSON : `http://localhost/integration/samrtnour/api/status.php`

## Validation rapide

- Lint wrapper + scripts :
  - `C:\xampp\php\php.exe -l samrtnour\index.php`
  - `C:\xampp\php\php.exe -l samrtnour\api\status.php`
  - `C:\xampp\php\php.exe samrtnour\scripts\smoke_status.php`

## Notes

- Le bridge est **additif** : il ne modifie pas les controllers/APIs existants de `client/` ni de `vehicule et rdv/`.
- Si tu veux une intégration métier (ex: créer un RDV depuis samrt nour), il faut définir précisément les cas d’usage pour éviter toute régression.
