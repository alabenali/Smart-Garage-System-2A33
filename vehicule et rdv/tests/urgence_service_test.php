<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/UrgenceService.php';

function assertEquals($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "[FAIL] {$message} | expected: {$expected} got: {$actual}\n");
        exit(1);
    }
}

$service = new UrgenceService();

$result = $service->calculerUrgence(['vehicule immobilise', 'fumee'], 'Freinage', 160000);
assertEquals(10, $result['score'], 'score clamp max');
assertEquals(5, $result['details']['vehicule immobilise'] ?? null, 'immobilise weight');
assertEquals(4, $result['details']['fumee'] ?? null, 'fumee weight');
assertEquals(3, $result['details']['type_freinage'] ?? null, 'type freinage weight');
assertEquals(1, $result['details']['km'] ?? null, 'km weight');

$result = $service->calculerUrgence([], '', 0);
assertEquals(0, $result['score'], 'empty inputs');

$detected = $service->detecterTemoinsDepuisTexte('voyant moteur allume, fumee visible, bruit anormal moteur');
assertEquals(true, in_array('voyant moteur allume', $detected, true), 'detect voyant moteur from text');
assertEquals(true, in_array('fumee', $detected, true), 'detect fumee from text');
assertEquals(true, in_array('bruit anormal', $detected, true), 'detect bruit from text');

$result = $service->calculerUrgence($detected, 'Moteur', 0);
assertEquals(10, $result['score'], 'description keywords score clamp max');

$context = [
    'date_creation' => date('Y-m-d H:i:s', time() - 50 * 3600),
    'capacite_max' => 2,
    'nb_actifs' => 2,
    'historique_count' => 2,
];

$bonus = $service->calculerScoreAvance($context);
assertEquals(3, $bonus['score'], 'advanced score');

fwrite(STDOUT, "[OK] UrgenceService tests passed\n");
