<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/UrgenceService.php';
require_once __DIR__ . '/../observers/RendezVousObserver.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SHOW COLUMNS FROM rendezvous_digital LIKE 'urgence_score'");
$hasUrgence = (bool) ($stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false);

if (!$hasUrgence) {
    fwrite(STDERR, "[FAIL] urgence_score column missing. Run database migration first.\n");
    exit(1);
}

$observer = new RendezVousObserver($db);
$select = $db->query('SELECT * FROM rendezvous_digital');
if (!$select) {
    fwrite(STDERR, "[FAIL] Unable to read rendezvous_digital.\n");
    exit(1);
}

$update = $db->prepare('UPDATE rendezvous_digital SET urgence_score = :score, urgence_details = :details WHERE id_rdv = :id_rdv');
$count = 0;

while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
    $temoins = $row['temoins_panne'] ?? [];
    if (is_string($temoins) && trim($temoins) !== '') {
        $decoded = json_decode($temoins, true);
        $temoins = is_array($decoded) ? $decoded : [];
    }

    $data = [
        'id_creneau' => $row['id_creneau'],
        'id_vehicle' => $row['id_vehicle'],
        'type_intervention' => $row['type_intervention'] ?? '',
        'description_panne' => $row['description_panne'] ?? '',
        'circonstances_panne' => $row['circonstances_panne'] ?? '',
        'temoins_panne' => $temoins,
        'panne_data_json' => $row['panne_data_json'] ?? '',
        'photos_json' => $row['photos_json'] ?? '',
        'date_creation' => $row['date_creation'] ?? '',
    ];

    $urgence = $observer->computeUrgence($data, (int) $row['id_rdv']);
    $update->execute([
        ':score' => (int) $urgence['score'],
        ':details' => json_encode($urgence['details'], JSON_UNESCAPED_UNICODE),
        ':id_rdv' => (int) $row['id_rdv'],
    ]);

    $count++;
}

fwrite(STDOUT, "[OK] Recalculated urgence for {$count} rendez-vous\n");
