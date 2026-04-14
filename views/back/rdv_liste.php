<?php
$pageTitle = 'Liste des RDV';
$action = 'backRdvList';
$extraCss = ['views/css/calendrier.css'];
$extraJs = ['views/js/calendrier_back.js'];
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

<div class="sg-form-wrap rdv-filter-wrap">
    <form method="GET" class="rdv-filter-grid">
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
            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Nom, téléphone, immatriculation">
        </div>
        <div class="sg-form-actions">
            <button class="btn-sg btn-sg-primary" type="submit"><i class="bi bi-funnel"></i> Filtrer</button>
            <a class="btn-sg btn-sg-outline" href="index.php?action=backRdvList">Réinitialiser</a>
            <a class="btn-sg btn-sg-success" href="index.php?action=backRdvExportCsv&status=<?php echo urlencode($filters['status'] ?? ''); ?>&date=<?php echo urlencode($filters['date'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>
        </div>
    </form>
</div>

<div class="sg-table-wrap">
    <table class="sg-table">
        <thead>
            <tr>
                <th>Date/Heure</th>
                <th>Client</th>
                <th>Téléphone</th>
                <th>Véhicule</th>
                <th>Intervention</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rdvs)): ?>
                <tr><td colspan="6" style="text-align:center;">Aucun rendez-vous.</td></tr>
            <?php else: ?>
                <?php foreach ($rdvs as $row): ?>
                    <?php
                    $statusMap = [
                        'En attente' => 'en-attente',
                        'Confirmé' => 'confirme',
                        'En cours' => 'en-cours',
                        'Terminé' => 'termine',
                        'Annulé' => 'annule',
                    ];
                    $statusClass = $statusMap[$row['statut']] ?? 'en-attente';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['date_heure']))); ?></td>
                        <td><?php echo htmlspecialchars($row['prenom_client'] . ' ' . $row['nom_client']); ?></td>
                        <td><?php echo htmlspecialchars($row['telephone_client']); ?></td>
                        <td><?php echo htmlspecialchars(($row['immatriculation'] ?? '-') . ' - ' . ($row['marque'] ?? '-') . ' ' . ($row['modele'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($row['type_intervention']); ?></td>
                        <td><span class="status-badge status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['statut']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination-wrap">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $url = 'index.php?' . http_build_query(array_merge($queryBase, ['page' => $i])); ?>
            <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo $url; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
