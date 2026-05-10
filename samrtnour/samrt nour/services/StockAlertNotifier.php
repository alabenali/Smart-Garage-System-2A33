<?php

require_once __DIR__ . '/TelegramService.php';
require_once __DIR__ . '/../models/StockAlertModel.php';

class StockAlertNotifier
{
    private $telegram;
    private $alerts;

    public function __construct(PDO $pdo)
    {
        $this->telegram = new TelegramService();
        $this->alerts = new StockAlertModel($pdo);
    }

    public function notifyPieceIfNeeded(int $idPiece): bool
    {
        $piece = $this->alerts->getPieceById($idPiece);
        if (!$piece) {
            return false;
        }

        return $this->notifyIfNeeded($piece);
    }

    public function notifyPiecesIfNeeded(array $pieceIds): int
    {
        $sent = 0;
        foreach (array_unique(array_map('intval', $pieceIds)) as $idPiece) {
            if ($idPiece > 0 && $this->notifyPieceIfNeeded($idPiece)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function notifyIfNeeded(array $piece): bool
    {
        $idPiece = (int) ($piece['id_piece'] ?? 0);
        if ($idPiece <= 0) {
            return false;
        }

        $stock = (int) ($piece['quantite_stock'] ?? 0);
        $seuil = (int) ($piece['seuil_alerte'] ?? 0);
        $type = null;

        if ($stock <= 0) {
            $type = 'rupture';
        } elseif ($stock <= $seuil) {
            $type = 'stock_faible';
        }

        if ($type === null || $this->alerts->alerteDejaEnvoyee($idPiece, $type)) {
            return false;
        }

        $response = $this->telegram->sendStockAlert($piece, $type);
        if (!$this->isSuccessfulResponse($response)) {
            error_log('StockAlertNotifier: alerte non envoyee pour piece #' . $idPiece . ' (' . $type . ').');
            return false;
        }

        $this->alerts->logAlerteEnvoyee(
            $idPiece,
            $type,
            $stock,
            (int) $response['result']['message_id']
        );

        return true;
    }

    private function isSuccessfulResponse($response): bool
    {
        return is_array($response)
            && (($response['ok'] ?? false) === true)
            && isset($response['result']['message_id']);
    }
}
