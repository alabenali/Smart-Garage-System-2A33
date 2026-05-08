<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/LoyaltyService.php';

header('Content-Type: application/json; charset=utf-8');

function loyaltyJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function loyaltyBody(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function loyaltyRewards(): array
{
    return [
        1 => ['id' => 1, 'label' => 'Diagnostic offert', 'points' => 120],
        2 => ['id' => 2, 'label' => 'Vidange offerte', 'points' => 200],
        3 => ['id' => 3, 'label' => 'Pack contrôle sécurité', 'points' => 350],
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = isset($_GET['path']) ? trim((string) $_GET['path'], '/') : '';
if ($path === '') {
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $uri = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?') ?: '';
    $uri = str_replace('\\', '/', $uri);
    if ($script !== '' && strpos($uri, $script) === 0) {
        $path = trim(substr($uri, strlen($script)), '/');
    } elseif (preg_match('#/api/loyalty/?(.*)$#', $uri, $matches)) {
        $path = trim((string) ($matches[1] ?? ''), '/');
    }
}

try {
    $pdo = Database::getInstance()->getConnection();
    $loyalty = new LoyaltyService($pdo);
} catch (Throwable $e) {
    loyaltyJson(['success' => false, 'message' => 'Service fidélité indisponible'], 500);
}

if ($method === 'GET' && $path === '') {
    $email = isset($_GET['email']) ? (string) $_GET['email'] : '';
    $account = $loyalty->getAccountByEmail($email);
    if (!$account) {
        loyaltyJson(['success' => false, 'message' => 'Compte fidélité introuvable'], 404);
    }

    $loyaltyId = (int) $account['id'];
    loyaltyJson([
        'success' => true,
        'account' => $account,
        'progression' => $loyalty->getProgression($loyaltyId),
        'historique' => $loyalty->getHistorique($loyaltyId, 10),
    ]);
}

if ($method === 'POST' && $path === 'redeem') {
    $body = loyaltyBody();
    $loyaltyId = isset($body['loyalty_id']) ? (int) $body['loyalty_id'] : 0;
    $rewardId = isset($body['reward_id']) ? (int) $body['reward_id'] : 0;
    $rewards = loyaltyRewards();

    if ($loyaltyId <= 0 || !isset($rewards[$rewardId])) {
        loyaltyJson(['success' => false, 'message' => 'Récompense invalide'], 422);
    }

    $reward = $rewards[$rewardId];
    $ok = $loyalty->utiliserPoints($loyaltyId, (int) $reward['points'], 'Récompense: ' . $reward['label']);
    if (!$ok) {
        loyaltyJson(['success' => false, 'message' => 'Points insuffisants ou compte invalide'], 422);
    }

    loyaltyJson([
        'success' => true,
        'reward' => $reward,
        'progression' => $loyalty->getProgression($loyaltyId),
        'historique' => $loyalty->getHistorique($loyaltyId, 10),
    ]);
}

if ($method === 'GET' && $path === 'leaderboard') {
    loyaltyJson([
        'success' => true,
        'leaderboard' => $loyalty->getTopClients(10, true),
    ]);
}

loyaltyJson(['success' => false, 'message' => 'Endpoint introuvable'], 404);
