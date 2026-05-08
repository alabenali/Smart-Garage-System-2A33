<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

$baseUrl = getenv('SMART_GARAGE_BASE_URL') ?: 'http://localhost/smart%20grage';
$apiBase = rtrim($baseUrl, '/') . '/api/rendez-vous';

function httpRequest(string $method, string $url, array $payload = null): array
{
    $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $response !== false ? $response : ''];
    }

    $context = [
        'http' => [
            'method' => $method,
            'header' => $body !== null ? "Content-Type: application/json\r\n" : '',
            'content' => $body ?? '',
            'timeout' => 10,
        ],
    ];

    $response = @file_get_contents($url, false, stream_context_create($context));
    return [0, $response !== false ? $response : ''];
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
}

list($code, $body) = httpRequest('GET', $apiBase);
$decoded = json_decode($body, true);
assertTrue(is_array($decoded) && ($decoded['success'] ?? false) === true, 'GET /api/rendez-vous');

list($code, $body) = httpRequest('GET', $apiBase . '/urgents');
$decoded = json_decode($body, true);
assertTrue(is_array($decoded) && ($decoded['success'] ?? false) === true, 'GET /api/rendez-vous/urgents');

$db = Database::getInstance()->getConnection();
$sql = "SELECT
            c.id_creneau,
            c.capacite_max,
            COUNT(r.id_rdv) AS nb_rdv
        FROM creneau_atelier c
        LEFT JOIN rendezvous_digital r
            ON r.id_creneau = c.id_creneau
            AND r.statut IN ('En attente', 'Confirmé', 'En cours')
        WHERE c.date_heure >= NOW()
        GROUP BY c.id_creneau
        HAVING (c.capacite_max - COUNT(r.id_rdv)) > 0
        ORDER BY c.date_heure ASC
        LIMIT 1";

$slotStmt = $db->query($sql);
$slot = $slotStmt ? $slotStmt->fetch(PDO::FETCH_ASSOC) : null;

if (!$slot) {
    fwrite(STDOUT, "[SKIP] Aucun creneau disponible pour test POST/PUT\n");
    exit(0);
}

$payload = [
    'id_creneau' => (int) $slot['id_creneau'],
    'type_intervention' => 'Freinage',
    'description_panne' => 'Test urgence API',
    'circonstances_panne' => 'En roulant',
    'temoins_panne' => ['immobilise', 'fumee'],
    'statut' => 'En attente',
];

list($code, $body) = httpRequest('POST', $apiBase, $payload);
$decoded = json_decode($body, true);
assertTrue(is_array($decoded) && ($decoded['success'] ?? false) === true, 'POST /api/rendez-vous');

$rdvId = (int) ($decoded['data']['id'] ?? 0);
assertTrue($rdvId > 0, 'POST returns id');

$updatePayload = [
    'description_panne' => 'Test urgence API update',
    'statut' => 'Confirmé',
];

list($code, $body) = httpRequest('PUT', $apiBase . '/' . $rdvId, $updatePayload);
$decoded = json_decode($body, true);
assertTrue(is_array($decoded) && ($decoded['success'] ?? false) === true, 'PUT /api/rendez-vous/{id}');

fwrite(STDOUT, "[OK] API rendez-vous tests passed\n");
