<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/TelegramService.php';
require_once __DIR__ . '/../models/StockAlertModel.php';

$config = require __DIR__ . '/../config/telegram.php';
$expectedToken = $config['webhook_token'];
$providedToken = $_GET['token'] ?? '';

// Test mode (manuel)
if (isset($_GET['test']) && $_GET['test'] === '1') {
    $telegram = new TelegramService();
    $res = $telegram->sendMessage("🔔 Ceci est un test de configuration du webhook Telegram.");
    echo $res ? "Test envoyé avec succès." : "Erreur lors de l'envoi.";
    exit;
}

if ($providedToken !== $expectedToken) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Telegram envoie les données en JSON
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(200); // Toujours 200 pour Telegram
    exit;
}

if (isset($update['callback_query'])) {
    $callbackQuery = $update['callback_query'];
    $callbackData = $callbackQuery['data'];
    $callbackQueryId = $callbackQuery['id'];
    $messageId = $callbackQuery['message']['message_id'] ?? null;
    
    $telegram = new TelegramService();
    
    try {
        $db = Database::getInstance()->getConnection();
        $alertModel = new StockAlertModel($db);

        // Parser callback_data (ex: "commande_12")
        $parts = explode('_', $callbackData);
        $action = $parts[0];
        $idPiece = isset($parts[1]) ? (int)$parts[1] : 0;

        if ($idPiece > 0) {
            $piece = $alertModel->getPieceById($idPiece);
            $nomPiece = $piece ? htmlspecialchars($piece['nom']) : "Pièce inconnue";

            if ($action === 'commande') {
                $alertModel->marquerResolue($idPiece, 'rupture');
                $alertModel->marquerResolue($idPiece, 'stock_faible');
                $telegram->answerCallbackQuery($callbackQueryId, "✅ Pièce marquée comme commandée !");
                $telegram->sendMessage("🛒 Commande fournisseur en cours pour : <b>{$nomPiece}</b>");
                if ($messageId) {
                    $telegram->editMessageReplyMarkup($messageId); // Supprime les boutons
                }
            } elseif ($action === 'details') {
                if ($piece) {
                    $stock = (int)$piece['quantite_stock'];
                    $prix = number_format((float)$piece['prix_unitaire'], 2);
                    $msg = "Détails {$nomPiece} :\nStock : {$stock}\nPrix : {$prix} DT";
                    $telegram->answerCallbackQuery($callbackQueryId, $msg);
                } else {
                    $telegram->answerCallbackQuery($callbackQueryId, "Pièce introuvable.");
                }
            } elseif ($action === 'ignorer') {
                $alertModel->marquerResolue($idPiece, 'rupture');
                $alertModel->marquerResolue($idPiece, 'stock_faible');
                $telegram->answerCallbackQuery($callbackQueryId, "🔕 Alerte ignorée");
                if ($messageId) {
                    $telegram->editMessageReplyMarkup($messageId); // Supprime les boutons
                }
            } else {
                $telegram->answerCallbackQuery($callbackQueryId, "Action non reconnue.");
            }
        } else {
            $telegram->answerCallbackQuery($callbackQueryId, "Erreur : ID invalide.");
        }
    } catch (Throwable $e) {
        $telegram->answerCallbackQuery($callbackQueryId, "Erreur interne.");
        error_log("Webhook Telegram Erreur : " . $e->getMessage());
    }
}

http_response_code(200);
