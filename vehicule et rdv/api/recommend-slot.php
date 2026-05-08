<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

$sessionAvailable = false;
if (session_status() === PHP_SESSION_NONE) {
    $sessionAvailable = @session_start();
} else {
    $sessionAvailable = true;
}

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function isValidDateString(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt instanceof DateTime && $dt->format('Y-m-d') === $date;
}

function normalizeType(string $type): string
{
    $aliases = [
        'Électrique-Batterie' => 'Électronique',
        'Electrique-Batterie' => 'Électronique',
        'Boîte de vitesse' => 'Transmission',
        'Boite de vitesse' => 'Transmission',
        'Changement pneu' => 'Changement de pneu',
        'Autre' => 'Diagnostic général',
    ];

    return $aliases[$type] ?? $type;
}

function durationForType(string $type): array
{
    $durees = [
        'Vidange' => ['minutes' => 30, 'label' => '~30min'],
        'Freinage' => ['minutes' => 90, 'label' => '~1h30'],
        'Moteur' => ['minutes' => 180, 'label' => '~3h'],
        'Embrayage' => ['minutes' => 240, 'label' => '~4h'],
        'Électronique' => ['minutes' => 120, 'label' => '~2h'],
        'Climatisation' => ['minutes' => 90, 'label' => '~1h30'],
        'Transmission' => ['minutes' => 180, 'label' => '~3h'],
        'Carrosserie' => ['minutes' => 60, 'label' => '~1h'],
        'Diagnostic seul' => ['minutes' => 60, 'label' => '~1h'],
        'Diagnostic général' => ['minutes' => 60, 'label' => '~1h'],
        'Révision' => ['minutes' => 90, 'label' => '~1h30'],
        'Changement de pneu' => ['minutes' => 45, 'label' => '~45min'],
        'Pneumatiques' => ['minutes' => 45, 'label' => '~45min'],
        'Batterie' => ['minutes' => 45, 'label' => '~45min'],
        'Suspension-Direction' => ['minutes' => 120, 'label' => '~2h'],
    ];

    return $durees[$type] ?? ['minutes' => 60, 'label' => '~1h'];
}

function scoreSlot(array $slot, int $durationMinutes): int
{
    $capacityMax = max(0, (int) $slot['capacite_max']);
    $remaining = max(0, (int) $slot['capacite_restante']);
    $hour = (int) date('H', strtotime((string) $slot['date_heure']));
    $score = 0;

    if ($capacityMax > 0 && $remaining > 0) {
        $score += (int) round(min(1, $remaining / $capacityMax) * 40);
    }

    if ($durationMinutes > 120 && $hour < 10) {
        $score += 20;
    }

    if ((int) $slot['est_heure_creuse'] === 1) {
        $score += 15;
    }

    if ($durationMinutes <= 60 && $hour >= 15) {
        $score += 10;
    }

    if ($remaining === 1 && $durationMinutes > 120) {
        $score -= 30;
    }

    if ($remaining <= 0 || $capacityMax <= 0) {
        $score = 0;
    }

    return max(0, min(100, $score));
}

function reasonForSlot(array $slot, int $durationMinutes, string $durationLabel, bool $recommended): string
{
    $remaining = max(0, (int) $slot['capacite_restante']);
    $hour = (int) date('H', strtotime((string) $slot['date_heure']));

    if ($remaining <= 0) {
        return 'Créneau complet pour cette date';
    }

    if ($recommended) {
        return 'Créneau idéal — atelier disponible pour une intervention de ' . ltrim($durationLabel, '~');
    }

    if ($durationMinutes > 120 && $hour < 10) {
        return 'Bon choix pour une intervention longue';
    }

    if ((int) $slot['est_heure_creuse'] === 1) {
        return 'Heure creuse — remise possible';
    }

    if ($remaining === 1) {
        return 'Disponible, mais capacité restante limitée';
    }

    return 'Créneau disponible';
}

$sessionStep2 = $sessionAvailable ? ($_SESSION['step2']['type_intervention'] ?? null) : null;
$sessionStep1 = $sessionAvailable ? ($_SESSION['step1']['date_choisie'] ?? null) : null;

$typePanne = trim((string) ($_GET['type_panne'] ?? ($sessionStep2 ?? 'Diagnostic général')));
$date = trim((string) ($_GET['date'] ?? ($sessionStep1 ?? date('Y-m-d'))));

if (!isValidDateString($date)) {
    jsonResponse(['success' => false, 'message' => 'Date invalide'], 422);
}

$normalizedType = normalizeType($typePanne !== '' ? $typePanne : 'Diagnostic général');
$duration = durationForType($normalizedType);

try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT
              c.id_creneau,
              c.date_heure,
              c.est_heure_creuse,
              c.capacite_max,
              COUNT(r.id_rdv) AS reservations_count,
              (c.capacite_max - COUNT(r.id_rdv)) AS capacite_restante
            FROM creneau_atelier c
            LEFT JOIN rendezvous_digital r
              ON r.id_creneau = c.id_creneau
              AND r.statut IN ('En attente', 'Confirmé', 'En cours')
            WHERE DATE(c.date_heure) = :date
              AND c.date_heure >= NOW()
            GROUP BY c.id_creneau
            ORDER BY c.date_heure ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([':date' => $date]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Erreur lors du chargement des créneaux'], 500);
}

$slots = [];
foreach ($rows as $row) {
    $score = scoreSlot($row, (int) $duration['minutes']);
    $remaining = max(0, (int) $row['capacite_restante']);
    $slots[] = [
        'id_creneau' => (int) $row['id_creneau'],
        'date_heure' => (string) $row['date_heure'],
        'heure' => date('H:i', strtotime((string) $row['date_heure'])),
        'capacite_max' => (int) $row['capacite_max'],
        'reservations_count' => (int) $row['reservations_count'],
        'capacite_restante' => $remaining,
        'score' => $score,
        'recommande' => false,
        'raison' => '',
        'est_heure_creuse' => (bool) $row['est_heure_creuse'],
    ];
}

usort($slots, static function (array $a, array $b): int {
    $aAvailable = $a['capacite_restante'] > 0 ? 1 : 0;
    $bAvailable = $b['capacite_restante'] > 0 ? 1 : 0;

    if ($aAvailable !== $bAvailable) {
        return $bAvailable <=> $aAvailable;
    }

    if ($a['score'] !== $b['score']) {
        return $b['score'] <=> $a['score'];
    }

    return strcmp($a['heure'], $b['heure']);
});

$recommendedIndex = null;
foreach ($slots as $index => $slot) {
    if ($slot['capacite_restante'] > 0) {
        $recommendedIndex = $index;
        break;
    }
}

if ($recommendedIndex !== null) {
    $slots[$recommendedIndex]['recommande'] = true;
}

foreach ($slots as $index => $slot) {
    $slots[$index]['raison'] = reasonForSlot($slot, (int) $duration['minutes'], (string) $duration['label'], (bool) $slot['recommande']);
}

usort($slots, static function (array $a, array $b): int {
    if ($a['recommande'] !== $b['recommande']) {
        return $a['recommande'] ? -1 : 1;
    }

    $aAvailable = $a['capacite_restante'] > 0 ? 1 : 0;
    $bAvailable = $b['capacite_restante'] > 0 ? 1 : 0;

    if ($aAvailable !== $bAvailable) {
        return $bAvailable <=> $aAvailable;
    }

    if ($a['score'] !== $b['score']) {
        return $b['score'] <=> $a['score'];
    }

    return strcmp($a['heure'], $b['heure']);
});

jsonResponse([
    'success' => true,
    'type_panne' => $normalizedType,
    'duree_estimee_minutes' => (int) $duration['minutes'],
    'duree_label' => (string) $duration['label'],
    'slots' => $slots,
]);
