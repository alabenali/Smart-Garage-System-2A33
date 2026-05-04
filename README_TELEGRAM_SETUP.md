# Guide de mise en place des Alertes Telegram

Suivez ces 5 étapes pour activer les notifications Telegram en temps réel pour la gestion de votre stock.

## Étape 1 — Créer le bot Telegram
1. Ouvrez l'application Telegram et cherchez **@BotFather**.
2. Envoyez la commande `/newbot`.
3. Suivez les instructions pour donner un nom et un pseudo à votre bot (ex: `SmartGarage_StockBot`).
4. **Copiez le TOKEN** fourni (ex: `8650376157:AAGqZq...`).

## Étape 2 — Récupérer son Chat ID (Admin)
1. Cherchez le bot **@userinfobot** sur Telegram.
2. Démarrez une discussion avec lui.
3. Il vous renverra votre `Id` (ex: `6672388992`). **Copiez ce numéro**.
4. N'oubliez pas d'envoyer un premier message à votre propre bot créé à l'étape 1 (ex: `/start`) pour l'autoriser à vous écrire !

## Étape 3 — Remplir le fichier `.env`
À la racine du projet `smart nour`, créez ou modifiez le fichier `.env` et ajoutez-y les variables suivantes :

```env
TELEGRAM_BOT_TOKEN=8650376157:AAGqZq89HQzttYHlFxISxXjOqvBWQoypd-M
TELEGRAM_ADMIN_CHAT_ID=6672388992
TELEGRAM_WEBHOOK_TOKEN=mon-secret-unique-12345
APP_URL=https://votre-domaine.com/samrtnour/samrt%20nour
```
*(Remplacez les valeurs par les vôtres. Si vous testez en local avec Ngrok, mettez l'URL Ngrok HTTPS dans `APP_URL` car Telegram exige le HTTPS).*

## Étape 4 — Enregistrer le webhook
Le webhook permet au bot Telegram d'envoyer des informations à votre serveur (par exemple quand vous cliquez sur un bouton interactif "✅ Marquer comme commandé").
Dans votre navigateur, accédez à cette adresse (une seule fois suffit) pour lier Telegram à votre code :

```
https://api.telegram.org/bot<VOTRE_TOKEN>/setWebhook?url=https://votre-domaine.com/samrtnour/samrt%20nour/webhook/telegram_webhook.php?token=mon-secret-unique-12345
```
Vous devriez voir un message `{"ok":true,"result":true,"description":"Webhook was set"}`.

## Étape 5 — Configurer la tâche Cron (Optionnel mais recommandé)
Pour vérifier régulièrement le stock indépendamment des actions humaines (ex: vérifications globales), configurez un Cron.

Si vous êtes sous Linux (cPanel/Serveur) :
```bash
*/30 * * * * php /chemin/vers/samrtnour/samrt nour/cron/check_stock_alerts.php
```

Si vous êtes sous Windows (XAMPP), utilisez le Planificateur de tâches pour lancer `check_stock_alerts.php`.

## Tester immédiatement !
Passez une commande qui met une pièce à 0 de stock, ou testez manuellement la configuration en visitant :
`https://votre-domaine.com/samrtnour/samrt%20nour/webhook/telegram_webhook.php?test=1`
