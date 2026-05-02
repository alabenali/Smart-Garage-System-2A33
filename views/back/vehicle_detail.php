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

<?php require __DIR__ . '/layout_footer.php'; ?>
