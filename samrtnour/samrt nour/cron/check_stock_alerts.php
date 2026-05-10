<?php
// Toutes les 30 minutes : */30 * * * * php /var/www/garage/cron/check_stock_alerts.php

define('CRON_MODE', true);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/TelegramService.php';
require_once __DIR__ . '/../models/StockAlertModel.php';

$logFile = __DIR__ . '/../logs/telegram_stock_' . date('Y-m') . '.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function logMessage($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

try {
    $db = Database::getInstance()->getConnection();
    $alertModel = new StockAlertModel($db);
    $telegram = new TelegramService();

    $ruptures = $alertModel->getPiecesEnRupture();
    $faibles = $alertModel->getPiecesStockFaible();
    
    $cptEnvoyees = 0;
    $cptErreurs = 0;

    // 1. Alertes de rupture
    foreach ($ruptures as $piece) {
        if (!$alertModel->alerteDejaEnvoyee($piece['id_piece'], 'rupture')) {
            $res = $telegram->sendStockAlert($piece, 'rupture');
            if ($res && isset($res['result']['message_id'])) {
                $alertModel->logAlerteEnvoyee($piece['id_piece'], 'rupture', $piece['quantite_stock'], $res['result']['message_id']);
                $cptEnvoyees++;
            } else {
                $cptErreurs++;
            }
            sleep(1); // Anti-spam Telegram
        }
    }

    // 2. Alertes stock faible
    foreach ($faibles as $piece) {
        if (!$alertModel->alerteDejaEnvoyee($piece['id_piece'], 'stock_faible')) {
            $res = $telegram->sendStockAlert($piece, 'stock_faible');
            if ($res && isset($res['result']['message_id'])) {
                $alertModel->logAlerteEnvoyee($piece['id_piece'], 'stock_faible', $piece['quantite_stock'], $res['result']['message_id']);
                $cptEnvoyees++;
            } else {
                $cptErreurs++;
            }
            sleep(1); // Anti-spam Telegram
        }
    }

    // 3. Résolution automatique
    $nonResolues = $alertModel->getAlertesNonResolues();
    foreach ($nonResolues as $alerte) {
        $piece = $alertModel->getPieceById($alerte['id_piece']);
        if ($piece) {
            $stock = (int)$piece['quantite_stock'];
            $seuil = (int)$piece['seuil_alerte'];
            $doResolve = false;

            if ($alerte['type_alerte'] === 'rupture' && $stock > 0) {
                $doResolve = true;
            } elseif ($alerte['type_alerte'] === 'stock_faible' && $stock > $seuil) {
                $doResolve = true;
            }

            if ($doResolve) {
                $alertModel->marquerResolue($alerte['id_piece'], $alerte['type_alerte']);
                $telegram->sendMessage("✅ <b>Stock rétabli</b>\nPièce : {$piece['nom']} ({$piece['reference']})\nStock actuel : {$stock} unité(s)");
                
                // On peut aussi enlever les boutons de l'ancien message si on avait son ID
                if (!empty($alerte['message_id'])) {
                    $telegram->editMessageReplyMarkup($alerte['message_id']);
                }
                sleep(1);
            }
        }
    }

    logMessage("ruptures=" . count($ruptures) . " | faibles=" . count($faibles) . " | envoyees=" . $cptEnvoyees . " | erreurs=" . $cptErreurs);

} catch (Throwable $e) {
    logMessage("ERREUR CRITIQUE: " . $e->getMessage());
}
