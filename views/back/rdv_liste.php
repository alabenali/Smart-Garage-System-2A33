<?php
$pageTitle = 'Liste des RDV';
$action = 'backRdvList';
$extraCss = ['views/css/calendrier.css'];
$extraJs = ['views/js/calendrier_back.js', 'views/js/urgence_live.js'];
require __DIR__ . '/layout_header.php';

$queryBase = [
    'action' => 'backRdvList',
    'status' => $filters['status'] ?? '',
    'date' => $filters['date'] ?? '',
    'search' => $filters['search'] ?? '',
];
?>

<h1 class="page-title">Liste des rendez-vous</h1>
<p class="page-subtitle">Filtrez, recherchez et exportez les rendez-vous du garage.</p>

<div id="urgenceStreamConfig" data-urgence-stream="api/rendez-vous/stream"></div>

<div class="sg-form-wrap rdv-filter-wrap">
    <form method="GET" action="index.php" class="rdv-filter-grid">
        <input type="hidden" name="action" value="backRdvList">
        <div class="sg-form-group">
            <label>Statut</label>
            <select name="status">
                <option value="">Tous</option>
                <?php foreach (['En attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo (($filters['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo $status; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group">
            <label>Date</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($filters['date'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Recherche</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Type de panne, symptôme, circonstances">
        </div>
        <div class="sg-form-actions">
            <button class="btn-sg btn-sg-primary" type="submit"><i class="bi bi-funnel"></i> Filtrer</button>
            <a class="btn-sg btn-sg-outline" href="index.php?action=backRdvList">Réinitialiser</a>
            <a class="btn-sg btn-sg-success" href="index.php?action=backRdvExportCsv&status=<?php echo urlencode($filters['status'] ?? ''); ?>&date=<?php echo urlencode($filters['date'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>
            <a class="btn-sg btn-sg-outline" href="index.php?action=backRdvExportPdf&status=<?php echo urlencode($filters['status'] ?? ''); ?>&date=<?php echo urlencode($filters['date'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
        </div>
    </form>
</div>

<div class="sg-table-wrap">
    <table class="sg-table">
        <thead>
            <tr>
                <th>Date/Heure</th>
                <th>Type panne</th>
                <th>Circonstances</th>
                <th>Symptômes</th>
                <th>Témoins</th>
                <th>Urgence</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody id="rdvListBody">
            <?php if (empty($rdvs)): ?>
                <tr><td colspan="7" style="text-align:center;">Aucun rendez-vous.</td></tr>
            <?php else: ?>
                <?php foreach ($rdvs as $row): ?>
                    <?php
                    $statusMap = [
                        'en attente' => 'en-attente',
                        'confirme' => 'confirme',
                        'en cours' => 'en-cours',
                        'termine' => 'termine',
                        'annule' => 'annule',
                    ];
                    $statusLabelMap = [
                        'en attente' => 'En attente',
                        'confirme' => 'Confirmé',
                        'en cours' => 'En cours',
                        'termine' => 'Terminé',
                        'annule' => 'Annulé',
                    ];

                    $statusRaw = trim((string) ($row['statut'] ?? ''));
                    $statusKey = mb_strtolower($statusRaw, 'UTF-8');
                    $statusKey = strtr($statusKey, [
                        'à' => 'a', 'â' => 'a', 'ä' => 'a',
                        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                        'î' => 'i', 'ï' => 'i',
                        'ô' => 'o', 'ö' => 'o',
                        'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                        'ç' => 'c',
                    ]);

                    $statusClass = $statusMap[$statusKey] ?? 'en-attente';
                    $statusLabel = $statusLabelMap[$statusKey] ?? ($statusRaw !== '' ? $statusRaw : 'En attente');
                    $rdvId = (int) ($row['id_rdv'] ?? 0);
                    $temoins = json_decode((string) ($row['temoins_panne'] ?? ''), true);
                    $temoinsLabel = is_array($temoins) && !empty($temoins) ? implode(', ', $temoins) : '-';
                    $urgenceScore = (int) ($row['urgence_score'] ?? 0);
                    $urgenceDetails = json_decode((string) ($row['urgence_details'] ?? ''), true);
                    $urgenceDetails = is_array($urgenceDetails) ? $urgenceDetails : [];
                    $urgenceParts = [];
                    foreach ($urgenceDetails as $key => $value) {
                        $urgenceParts[] = $key . ':' . (string) $value;
                    }
                    $urgenceDetailsLabel = !empty($urgenceParts) ? implode(', ', $urgenceParts) : '-';
                    if ($urgenceScore >= 7) {
                        $urgenceClass = 'urgence-high';
                        $urgenceLabel = 'Critique';
                    } elseif ($urgenceScore >= 4) {
                        $urgenceClass = 'urgence-medium';
                        $urgenceLabel = 'Elevee';
                    } else {
                        $urgenceClass = 'urgence-low';
                        $urgenceLabel = 'Faible';
                    }
                    $photos = json_decode((string) ($row['photos_json'] ?? ''), true);
                    $photos = is_array($photos) ? $photos : [];

                    if (empty($photos)) {
                        $diskPhotos = glob(__DIR__ . '/../images/pannes/rdv_' . $rdvId . '_*');
                        if (is_array($diskPhotos) && !empty($diskPhotos)) {
                            $photos = array_map(static function ($absPath) {
                                return ['path' => 'views/images/pannes/' . basename((string) $absPath)];
                            }, $diskPhotos);
                        }
                    }
                    ?>
                    <tr class="rdv-summary-row <?php echo $urgenceScore >= 7 ? 'rdv-urgent' : ''; ?>" data-rdv-detail-id="rdv-detail-<?php echo $rdvId; ?>" data-urgence-score="<?php echo $urgenceScore; ?>">
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['date_heure']))); ?></td>
                        <td><?php echo htmlspecialchars($row['type_intervention']); ?></td>
                        <td><?php echo htmlspecialchars($row['circonstances_panne'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['description_panne'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($temoinsLabel); ?></td>
                        <td>
                            <span class="urgence-badge <?php echo $urgenceClass; ?>" title="<?php echo htmlspecialchars($urgenceLabel); ?>">
                                <?php echo $urgenceScore; ?>/10
                            </span>
                        </td>
                        <td><span class="status-badge status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                    </tr>
                    <tr id="rdv-detail-<?php echo $rdvId; ?>" class="rdv-detail-row" style="display:none;">
                        <td colspan="7">
                            <div class="rdv-list-detail-box">
                                <div><strong>Type de panne:</strong> <?php echo htmlspecialchars($row['type_intervention'] ?? '-'); ?></div>
                                <div><strong>Circonstances:</strong> <?php echo htmlspecialchars($row['circonstances_panne'] ?? '-'); ?></div>
                                <div><strong>Symptômes:</strong> <?php echo htmlspecialchars($row['description_panne'] ?? '-'); ?></div>
                                <div><strong>Témoins:</strong> <?php echo htmlspecialchars($temoinsLabel); ?></div>
                                <div><strong>Urgence:</strong> <?php echo $urgenceScore; ?>/10 (<?php echo htmlspecialchars($urgenceDetailsLabel); ?>)</div>
                                <div><strong>Images de panne:</strong></div>

                                <?php if (empty($photos)): ?>
                                    <div class="rdv-images-empty">Aucune image</div>
                                <?php else: ?>
                                    <div class="rdv-images-grid">
                                        <?php foreach ($photos as $photo): ?>
                                            <?php $imgPath = isset($photo['path']) ? (string) $photo['path'] : ''; ?>
                                            <?php if ($imgPath === '') { continue; } ?>
                                            <a href="<?php echo htmlspecialchars($imgPath); ?>" target="_blank" rel="noopener noreferrer" class="rdv-image-link">
                                                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Image panne RDV <?php echo $rdvId; ?>" class="rdv-image-thumb">
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('rdvListBody');
    if (!tableBody) {
        return;
    }

    tableBody.addEventListener('click', function (event) {
        const row = event.target.closest('.rdv-summary-row[data-rdv-detail-id]');
        if (!row || !tableBody.contains(row)) {
            return;
        }

        const detailId = row.getAttribute('data-rdv-detail-id');
        if (!detailId) {
            return;
        }

        const detailRow = document.getElementById(detailId);
        if (!detailRow) {
            return;
        }

        const isOpen = detailRow.style.display !== 'none';
        detailRow.style.display = isOpen ? 'none' : 'table-row';
        row.classList.toggle('is-open', !isOpen);
    });
});
</script>

<?php if ($totalPages > 1): ?>
    <div class="pagination-wrap">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $url = 'index.php?' . http_build_query(array_merge($queryBase, ['page' => $i])); ?>
            <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo $url; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
