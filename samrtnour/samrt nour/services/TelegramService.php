<?php

class TelegramService
{
    private $_botToken;
    private $_chatId;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/telegram.php';
        $this->_botToken = $config['bot_token'];
        $this->_chatId = $config['admin_chat_id'];
    }

    public function sendMessage(string $text, array $replyMarkup = [])
    {
        if (empty($this->_botToken) || empty($this->_chatId)) {
            error_log('TelegramService: Token ou Chat ID manquant.');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->_botToken}/sendMessage";
        
        $postFields = [
            'chat_id' => $this->_chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if (!empty($replyMarkup)) {
            $postFields['reply_markup'] = json_encode($replyMarkup);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("TelegramService erreur: $error");
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            error_log('TelegramService: reponse invalide: ' . $response);
            return false;
        }

        if (($decoded['ok'] ?? false) !== true) {
            $description = isset($decoded['description']) ? (string) $decoded['description'] : 'erreur inconnue';
            error_log('TelegramService: envoi refuse par Telegram: ' . $description);
        }

        return $decoded;
    }

    public function sendStockAlert(array $piece, string $type)
    {
        $datetime = date('d/m/Y H:i:s');
        $prix = number_format((float) $piece['prix_unitaire'], 2, ',', ' ');
        $seuil = (int) $piece['seuil_alerte'];
        $nom = htmlspecialchars($piece['nom']);
        $ref = htmlspecialchars($piece['reference']);
        $marque = htmlspecialchars($piece['marque']);
        $categorie = htmlspecialchars($piece['categorie']);
        $stock = (int) $piece['quantite_stock'];

        if ($type === 'rupture') {
            $text = "🚨 <b>RUPTURE DE STOCK</b>\n";
            $text .= "━━━━━━━━━━━━━━━━━━━━\n";
            $text .= "📦 Pièce : {$nom}\n";
            $text .= "🏷️ Réf : {$ref}\n";
            $text .= "🏭 Marque : {$marque}\n";
            $text .= "📂 Catégorie : {$categorie}\n";
            $text .= "📉 Stock actuel : <b>0 unité</b>\n";
            $text .= "⚠️ Seuil d'alerte : {$seuil} unités\n";
            $text .= "💰 Prix unitaire : {$prix} DT\n";
            $text .= "🕐 Détecté le : {$datetime}";
        } else {
            $text = "⚠️ <b>STOCK FAIBLE</b>\n";
            $text .= "━━━━━━━━━━━━━━━━━━━━\n";
            $text .= "📦 Pièce : {$nom}\n";
            $text .= "🏷️ Réf : {$ref}\n";
            $text .= "🏭 Marque : {$marque}\n";
            $text .= "📂 Catégorie : {$categorie}\n";
            $text .= "📊 Stock actuel : <b>{$stock} unité(s)</b>\n";
            $text .= "⚠️ Seuil d'alerte : {$seuil} unités\n";
            $text .= "💰 Prix unitaire : {$prix} DT\n";
            $text .= "🕐 Détecté le : {$datetime}";
        }

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Marquer comme commandé', 'callback_data' => "commande_{$piece['id_piece']}"]
                ],
                [
                    ['text' => '📋 Voir détails', 'callback_data' => "details_{$piece['id_piece']}"],
                    ['text' => '🔕 Ignorer cette alerte', 'callback_data' => "ignorer_{$piece['id_piece']}"]
                ]
            ]
        ];

        return $this->sendMessage($text, $replyMarkup);
    }

    public function sendDailySummary(array $stats)
    {
        $date = date('d/m/Y');
        $montant = number_format((float) $stats['valeur_totale'], 2, ',', ' ');
        $ruptures = (int) $stats['nb_ruptures'];
        $faibles = (int) $stats['nb_faible'];
        $ok = (int) $stats['nb_ok'];

        $text = "📊 <b>Rapport stock quotidien</b>\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "🔴 Ruptures : {$ruptures} pièce(s)\n";
        $text .= "🟠 Stock faible : {$faibles} pièce(s)\n";
        $text .= "🟢 Stock OK : {$ok} pièce(s)\n";
        $text .= "💰 Valeur totale stock : {$montant} DT\n";
        $text .= "📅 {$date}\n\n";

        if ($ruptures > 0 && !empty($stats['pieces_critiques'])) {
            $text .= "<b>⚠️ PIÈCES CRITIQUES :</b>\n";
            foreach ($stats['pieces_critiques'] as $p) {
                $text .= "• {$p['nom']} (Réf: {$p['reference']})\n";
            }
        }

        $this->sendMessage($text);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text)
    {
        if (empty($this->_botToken)) return;

        $url = "https://api.telegram.org/bot{$this->_botToken}/answerCallbackQuery";
        $postFields = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => true
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }

    public function editMessageReplyMarkup(int $messageId, array $newMarkup = [])
    {
        if (empty($this->_botToken) || empty($this->_chatId)) return;

        $url = "https://api.telegram.org/bot{$this->_botToken}/editMessageReplyMarkup";
        $postFields = [
            'chat_id' => $this->_chatId,
            'message_id' => $messageId,
        ];

        if (!empty($newMarkup)) {
            $postFields['reply_markup'] = json_encode($newMarkup);
        } else {
            $postFields['reply_markup'] = json_encode(['inline_keyboard' => []]);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
    }
}
