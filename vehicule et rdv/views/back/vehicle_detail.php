<?php
$pageTitle = 'Fiche Véhicule';
$action = 'vehicleDetail';
require_once __DIR__ . '/../../helpers/PlateHelper.php';

if (!function_exists('vehicleDetailNormalizeKey')) {
    function vehicleDetailNormalizeKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        $translit = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($translit !== false) {
            $value = $translit;
        }

        $value = preg_replace('/[^a-z0-9\s-]+/', '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    function vehicleDetailStatusClass(string $status): string
    {
        $map = [
            'en attente' => 'en-attente',
            'confirme' => 'confirme',
            'en cours' => 'en-cours',
            'termine' => 'termine',
            'annule' => 'annule',
        ];

        return $map[vehicleDetailNormalizeKey($status)] ?? 'en-attente';
    }

    function vehicleDetailStatusLabel(string $status): string
    {
        $map = [
            'en attente' => 'En attente',
            'confirme' => 'Confirmé',
            'en cours' => 'En cours',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
        ];

        $key = vehicleDetailNormalizeKey($status);
        return $map[$key] ?? ($status !== '' ? $status : '-');
    }

    function vehicleDetailUrgenceClass(int $score): string
    {
        if ($score >= 7) {
            return 'urgence-high';
        }

        if ($score >= 4) {
            return 'urgence-medium';
        }

        return 'urgence-low';
    }

    function vehicleDetailDate(?string $date, bool $withHour = true): string
    {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '-';
        }

        return date($withHour ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
    }

    function vehicleDetailJsonList($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

$vehicleName = trim((string) ($vehicle['marque'] ?? '') . ' ' . (string) ($vehicle['modele'] ?? ''));
$vehicleName = $vehicleName !== '' ? $vehicleName : 'Véhicule #' . (int) ($vehicle['id'] ?? 0);
$vehicleAge = '';
if (!empty($vehicle['annee'])) {
    $vehicleAge = max(0, (int) date('Y') - (int) $vehicle['annee']) . ' an(s)';
}

require __DIR__ . '/layout_header.php';
?>

<div class="detail-page-head">
    <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline btn-sg-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <div>
        <h1 class="page-title" style="margin-bottom:0.1rem;">Fiche véhicule</h1>
        <p class="page-subtitle" style="margin-bottom:0;"><?php echo htmlspecialchars($vehicleName); ?> - #<?php echo (int) $vehicle['id']; ?></p>
    </div>
    <div class="detail-head-actions">
        <a href="index.php?action=editVehicle&id=<?php echo (int) $vehicle['id']; ?>" class="btn-sg btn-sg-success">
            <i class="bi bi-pencil-square"></i> Modifier
        </a>
        <button type="button" class="btn-sg btn-sg-danger" onclick="confirmDelete('index.php?action=deleteVehicle&id=<?php echo (int) $vehicle['id']; ?>')">
            <i class="bi bi-trash3"></i> Supprimer
        </button>
    </div>
</div>

<div class="vehicle-detail-layout">
    <section class="vehicle-profile-panel">
        <div class="vehicle-profile-title">
            <span class="vehicle-profile-icon"><i class="bi bi-car-front"></i></span>
            <div>
                <h2><?php echo htmlspecialchars($vehicleName); ?></h2>
                <div class="vehicle-profile-plate"><?php echo formatPlate($vehicle['immatriculation'] ?? ''); ?></div>
            </div>
        </div>

        <div class="vehicle-fiche-grid">
            <div class="vehicle-fiche-item">
                <span>Marque</span>
                <strong><?php echo htmlspecialchars($vehicle['marque'] ?? '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Modèle</span>
                <strong><?php echo htmlspecialchars($vehicle['modele'] ?? '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Année</span>
                <strong><?php echo htmlspecialchars((string) ($vehicle['annee'] ?? '-')); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Âge</span>
                <strong><?php echo htmlspecialchars($vehicleAge !== '' ? $vehicleAge : '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Kilométrage</span>
                <strong><?php echo number_format((int) ($vehicle['kilometrage'] ?? 0), 0, ',', ' '); ?> km</strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Carburant</span>
                <strong><span class="badge-fuel <?php echo strtolower((string) ($vehicle['carburant'] ?? '')); ?>"><?php echo htmlspecialchars($vehicle['carburant'] ?? '-'); ?></span></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Couleur</span>
                <strong><?php echo htmlspecialchars($vehicle['couleur'] ?? '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Date d'ajout</span>
                <strong><?php echo vehicleDetailDate($vehicle['date_ajout'] ?? null, false); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Client proprietaire</span>
                <strong>
                    <?php if (!empty($ownerClient)): ?>
                        <a class="vehicle-table-link" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $ownerClient['id']; ?>">
                            <?php echo htmlspecialchars(trim(($ownerClient['prenom'] ?? '') . ' ' . ($ownerClient['nom'] ?? ''))); ?>
                        </a>
                    <?php else: ?>
                        Non assigne
                    <?php endif; ?>
                </strong>
            </div>
        </div>

        <div class="card mb-4" id="health-score-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">❤️ Score de santé du véhicule</h6>
                <span class="badge bg-secondary fs-6" id="health-badge">-- /100</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold" id="health-niveau">Calcul en cours...</span>
                        <span id="health-score-val" class="fw-bold">--/100</span>
                    </div>
                    <div class="progress" style="height: 18px; border-radius: 10px;">
                        <div id="health-bar" class="progress-bar progress-bar-striped" role="progressbar" style="width: 0%; transition: width 1s ease;"></div>
                    </div>
                </div>

                <div id="health-criteres" class="row g-2 mb-3"></div>

                <div id="health-recommandation" class="alert alert-light border mt-2" style="display:none">
                    <small>💡 <span id="health-recommandation-text"></span></small>
                </div>
            </div>
        </div>
    </section>

    <section class="vehicle-stats-panel">
        <h2>Résumé interventions</h2>
        <div class="vehicle-stats-grid">
            <div class="vehicle-stat-box">
                <span>Total</span>
                <strong><?php echo (int) ($historyStats['total'] ?? 0); ?></strong>
            </div>
            <div class="vehicle-stat-box">
                <span>Actives</span>
                <strong><?php echo (int) ($historyStats['active'] ?? 0); ?></strong>
            </div>
            <div class="vehicle-stat-box">
                <span>Terminées</span>
                <strong><?php echo (int) ($historyStats['done'] ?? 0); ?></strong>
            </div>
            <div class="vehicle-stat-box">
                <span>Urgentes</span>
                <strong><?php echo (int) ($historyStats['urgent'] ?? 0); ?></strong>
            </div>
        </div>
        <div class="vehicle-last-intervention">
            <span>Dernière intervention</span>
            <strong><?php echo vehicleDetailDate($historyStats['last_date'] ?? null); ?></strong>
        </div>
    </section>
</div>

<div class="sg-table-wrap vehicle-history-wrap">
    <div class="table-header">
        <h3><i class="bi bi-clock-history me-2"></i>Historique d'intervention</h3>
        <span style="color:var(--text-muted);font-size:0.85rem;">
            <?php echo count($history); ?> intervention<?php echo count($history) !== 1 ? 's' : ''; ?>
        </span>
    </div>

    <?php if (empty($history)): ?>
        <div class="empty-state vehicle-history-empty">
            <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
            <h3>Aucune intervention</h3>
            <p>Aucun rendez-vous n'est encore lié à ce véhicule.</p>
        </div>
    <?php else: ?>
        <table class="sg-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Intervention</th>
                    <th>Symptômes et témoins</th>
                    <th>Urgence</th>
                    <th>Statut</th>
                    <th>Remise</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): ?>
                    <?php
                    $score = (int) ($row['urgence_score'] ?? 0);
                    $temoins = vehicleDetailJsonList($row['temoins_panne'] ?? '');
                    $temoinsLabel = !empty($temoins) ? implode(', ', $temoins) : '-';
                    $photos = vehicleDetailJsonList($row['photos_json'] ?? '');
                    $statusRaw = (string) ($row['statut'] ?? '');
                    $statusClass = vehicleDetailStatusClass($statusRaw);
                    $statusLabel = vehicleDetailStatusLabel($statusRaw);
                    $date = $row['date_heure'] ?? $row['date_creation'] ?? null;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo vehicleDetailDate($date); ?></strong>
                            <span class="vehicle-history-meta">RDV #<?php echo (int) ($row['id_rdv'] ?? 0); ?></span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['type_intervention'] ?? '-'); ?></strong>
                            <span class="vehicle-history-meta"><?php echo htmlspecialchars($row['circonstances_panne'] ?? '-'); ?></span>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($row['description_panne'] ?? '-'); ?></div>
                            <span class="vehicle-history-meta">Témoins: <?php echo htmlspecialchars($temoinsLabel); ?></span>
                            <?php if (!empty($photos)): ?>
                                <span class="vehicle-history-meta">Photos: <?php echo count($photos); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="urgence-badge <?php echo vehicleDetailUrgenceClass($score); ?>">
                                <?php echo $score; ?>/10
                            </span>
                        </td>
                        <td><span class="status-badge status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                        <td><?php echo number_format((float) ($row['remise_eco_appliquee'] ?? 0), 2, ',', ' '); ?>%</td>
                    </tr>
                    <?php if (!empty($row['notes'])): ?>
                        <tr class="vehicle-history-note-row">
                            <td></td>
                            <td colspan="5"><strong>Notes:</strong> <?php echo htmlspecialchars($row['notes']); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
@keyframes pulse-red {
    0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
}

.pulse {
    animation: pulse-red 1.5s ease 3;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const healthCard = document.getElementById('health-score-card');
    if (!healthCard) {
        return;
    }

    const badge = document.getElementById('health-badge');
    const scoreVal = document.getElementById('health-score-val');
    const niveau = document.getElementById('health-niveau');
    const bar = document.getElementById('health-bar');
    const criteres = document.getElementById('health-criteres');
    const reco = document.getElementById('health-recommandation');
    const recoText = document.getElementById('health-recommandation-text');
    const vehicleId = <?php echo (int) ($vehicle['id'] ?? 0); ?>;

    if (!vehicleId) {
        niveau.textContent = 'Score indisponible';
        return;
    }

    fetch('api/vehicle-health.php?id_vehicle=' + vehicleId)
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (!data || typeof data.score === 'undefined') {
                niveau.textContent = 'Score indisponible';
                return;
            }

            const score = Math.max(0, Math.min(100, parseInt(data.score, 10) || 0));
            const couleur = data.couleur ? String(data.couleur) : 'secondary';

            badge.textContent = score + ' /100';
            badge.className = 'badge bg-' + couleur + ' fs-6';
            scoreVal.textContent = score + '/100';
            niveau.textContent = data.niveau ? String(data.niveau) : '—';

            bar.className = 'progress-bar progress-bar-striped bg-' + couleur;
            bar.style.width = score + '%';

            if (Array.isArray(data.criteres)) {
                const itemsHtml = data.criteres.map(function (critere) {
                    const points = parseInt(critere.points_perdus, 10) || 0;
                    const sur = parseInt(critere.sur, 10) || 0;
                    const pct = sur > 0 ? Math.min(100, Math.round((points / sur) * 100)) : 0;
                    const label = critere.label ? String(critere.label) : '';
                    const valeur = critere.valeur ? String(critere.valeur) : '';

                    return '<div class="col-md-4">'
                        + '<div class="p-2 rounded border bg-light">'
                        + '<div class="d-flex justify-content-between">'
                        + '<small class="text-muted">' + label + '</small>'
                        + '<small class="text-danger fw-bold">-' + points + ' pts</small>'
                        + '</div>'
                        + '<div class="fw-semibold small">' + valeur + '</div>'
                        + '<div class="progress mt-1" style="height:4px">'
                        + '<div class="progress-bar bg-danger" style="width:' + pct + '%"></div>'
                        + '</div>'
                        + '</div>'
                        + '</div>';
                }).join('');

                criteres.innerHTML = itemsHtml;
            }

            if (data.recommandation) {
                recoText.textContent = String(data.recommandation);
                reco.style.display = 'block';
            }

            if (data.alerte === true) {
                healthCard.classList.add('pulse');
            }
        })
        .catch(function () {
            niveau.textContent = 'Score indisponible';
        });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
