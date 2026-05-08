<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

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
        return ['niveau' => 'Excellent', 'couleur' => 'success'];
    }
    if ($score >= 60) {
        return ['niveau' => 'Bon', 'couleur' => 'primary'];
    }
    if ($score >= 40) {
        return ['niveau' => 'Moyen', 'couleur' => 'warning'];
    }
    if ($score >= 20) {
        return ['niveau' => 'Faible', 'couleur' => 'danger'];
    }

    return ['niveau' => 'Critique', 'couleur' => 'dark'];
}

function scoreRecommendation(int $score): string
{
    if ($score >= 80) {
        return "Véhicule en excellente santé. Continuer l'entretien préventif régulier.";
    }
    if ($score >= 60) {
        return "Véhicule en bon état. Maintenir les révisions périodiques et surveiller les consommables.";
    }
    if ($score >= 40) {
        return "Véhicule en état correct. Surveiller le kilométrage et planifier une révision générale.";
    }
    if ($score >= 20) {
        return "Véhicule fragilisé. Prévoir un diagnostic complet et des interventions ciblées.";
    }

    return 'Véhicule en état critique. Intervention prioritaire recommandée.';
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
$stmt = $pdo->prepare('SELECT id, marque, modele, annee, kilometrage, carburant FROM vehicle WHERE id = :id LIMIT 1');
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

$ageLabel = $annee > 0
    ? $annee . ' (' . $age . ' ' . ($age > 1 ? 'ans' : 'an') . ')'
    : '-';

$kmLabel = number_format($kilometrage, 0, ',', ' ') . ' km';
$kmAnnuelLabel = number_format((int) round($kmAnnuel), 0, ',', ' ') . ' km/an';
$pannesLabel = $nbPannesGraves . ' panne' . ($nbPannesGraves !== 1 ? 's' : '');
$diversiteLabel = $nbTypesDistincts . ' type' . ($nbTypesDistincts !== 1 ? 's' : '')
    . ' différent' . ($nbTypesDistincts !== 1 ? 's' : '');

$criteres = [
    [
        'label' => 'Âge du véhicule',
        'valeur' => $ageLabel,
        'points_perdus' => $pointsAge,
        'sur' => 30,
    ],
    [
        'label' => 'Kilométrage total',
        'valeur' => $kmLabel,
        'points_perdus' => $pointsKmTotal,
        'sur' => 25,
    ],
    [
        'label' => 'Kilométrage annuel moyen',
        'valeur' => $kmAnnuelLabel,
        'points_perdus' => $pointsKmAnnuel,
        'sur' => 15,
    ],
    [
        'label' => 'Pannes moteur',
        'valeur' => $pannesLabel,
        'points_perdus' => $pointsPannes,
        'sur' => 20,
    ],
    [
        'label' => 'Diversité des pannes',
        'valeur' => $diversiteLabel,
        'points_perdus' => $pointsDiversite,
        'sur' => 10,
    ],
];

jsonResponse([
    'score' => $score,
    'niveau' => $level['niveau'],
    'couleur' => $level['couleur'],
    'criteres' => $criteres,
    'recommandation' => scoreRecommendation($score),
    'alerte' => $alerte,
]);
