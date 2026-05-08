<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function scoreLevel(int $score): array
{
    if ($score >= 80) {
        return ['niveau' => 'Excellent'];
    }
    if ($score >= 60) {
        return ['niveau' => 'Bon'];
    }
    if ($score >= 40) {
        return ['niveau' => 'Moyen'];
    }
    if ($score >= 20) {
        return ['niveau' => 'Faible'];
    }

    return ['niveau' => 'Critique'];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$idVehicle = isset($_GET['id_vehicle']) ? (int) $_GET['id_vehicle'] : 0;
if ($idVehicle <= 0) {
    jsonResponse(['error' => 'id_vehicle invalide'], 400);
}

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare('SELECT id, annee, kilometrage FROM vehicle WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $idVehicle]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    jsonResponse(['error' => 'Vehicule introuvable'], 404);
}

$currentYear = (int) date('Y');
$annee = isset($vehicle['annee']) ? (int) $vehicle['annee'] : 0;
$kilometrage = isset($vehicle['kilometrage']) ? (int) $vehicle['kilometrage'] : 0;
if ($kilometrage < 0) {
    $kilometrage = 0;
}

$age = $annee > 0 ? max(0, $currentYear - $annee) : 0;

$pointsAge = min($age * 3, 30);
$pointsKmTotal = min((int) floor($kilometrage / 5000), 25);

$kmAnnuel = $age > 0 ? ($kilometrage / $age) : $kilometrage;
$pointsKmAnnuel = 0;
if ($kmAnnuel > 25000) {
    $pointsKmAnnuel = 15;
} elseif ($kmAnnuel > 18000) {
    $pointsKmAnnuel = 10;
} elseif ($kmAnnuel > 12000) {
    $pointsKmAnnuel = 5;
}

$stmt = $pdo->prepare('SELECT type_intervention, COUNT(*) AS total FROM rendezvous_digital WHERE id_vehicle = :id_vehicle GROUP BY type_intervention');
$stmt->execute([':id_vehicle' => $idVehicle]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$severeTypes = ['Moteur', 'Transmission', 'Embrayage'];
$nbPannesGraves = 0;
$nbTypesDistincts = 0;

foreach ($rows as $row) {
    $type = trim((string) ($row['type_intervention'] ?? ''));
    if ($type === '') {
        continue;
    }

    $nbTypesDistincts++;
    if (in_array($type, $severeTypes, true)) {
        $nbPannesGraves += (int) ($row['total'] ?? 0);
    }
}

$pointsPannes = min($nbPannesGraves * 10, 20);
$pointsDiversite = min($nbTypesDistincts * 2, 10);

$score = 100 - ($pointsAge + $pointsKmTotal + $pointsKmAnnuel + $pointsPannes + $pointsDiversite);
$score = max(0, min(100, $score));

$level = scoreLevel($score);
$alerte = $score < 30;

jsonResponse([
    'id_vehicle' => $idVehicle,
    'score' => $score,
    'niveau' => $level['niveau'],
    'alerte' => $alerte,
]);
