<?php
$pageTitle = 'Mes Interventions';
$action = 'client_interventions';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title text-white mb-1">Mes Interventions</h1>
            <p class="text-muted mb-0">Consultez vos devis et suivez le statut de vos interventions.</p>
        </div>
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="action" value="client_interventions">
            <select name="vehicle_id" class="form-select bg-dark text-white border-secondary" style="min-width:220px;">
                <option value="">Tous les vehicules</option>
                <?php foreach (($vehicles ?? []) as $v): ?>
                    <option value="<?php echo (int)$v['id']; ?>" <?php echo ((int)$vehicleId === (int)$v['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)$v['immatriculation']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-light">Filtrer</button>
        </form>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Impossible de charger l'intervention demandee.</div>
    <?php endif; ?>

    <div class="card bg-dark border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">
            Liste des interventions
        </div>

        <?php if (empty($interventions)): ?>
            <div class="card-body text-center text-muted py-5">Aucune intervention disponible.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">#ID</th>
                            <th>Vehicule</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Devis</th>
                            <th>Cout initial</th>
                            <th>Cout final</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interventions as $inter): ?>
                            <tr>
                                <td class="ps-4">#<?php echo (int)$inter['id_intervention']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars((string)($inter['immatriculation'] ?? '-')); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(trim((string)($inter['vehicle_marque'] ?? '') . ' ' . (string)($inter['vehicle_modele'] ?? ''))); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars((string)($inter['type_nom'] ?? '-')); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars((string)($inter['statut'] ?? 'planifiee')); ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars((string)($inter['statut_devis'] ?? 'en_attente')); ?></span></td>
                                <td><?php echo number_format((float)($inter['cout_initial'] ?? 0), 2, ',', ' '); ?> DT</td>
                                <td>
                                    <?php if (isset($inter['cout_final']) && $inter['cout_final'] !== null): ?>
                                        <?php echo number_format((float)$inter['cout_final'], 2, ',', ' '); ?> DT
                                    <?php else: ?>
                                        <span class="text-muted">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end">
                                        <a href="index.php?action=intervention_detail&id=<?php echo (int)$inter['id_intervention']; ?>&vehicle_id=<?php echo (int)($inter['id_vehicule'] ?? 0); ?>" class="btn btn-sm btn-primary">Details</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
