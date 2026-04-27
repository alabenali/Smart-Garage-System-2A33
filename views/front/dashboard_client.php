<?php
$pageTitle = 'Dashboard Client';
$action = 'client_dashboard';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title text-white mb-1">Espace Client</h1>
            <p class="text-muted mb-0">Suivez vos devis, interventions et echanges avec le garage.</p>
        </div>
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="action" value="client_dashboard">
            <select name="vehicle_id" class="form-select bg-dark text-white border-secondary" style="min-width: 220px;">
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

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon blue"><i class="bi bi-list-check"></i></div>
                        <div>
                            <div class="text-muted small">Total Interventions</div>
                            <div class="h3 mb-0"><?php echo (int)($statsClient['total'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon orange"><i class="bi bi-wrench-adjustable-circle"></i></div>
                        <div>
                            <div class="text-muted small">En cours</div>
                            <div class="h3 mb-0"><?php echo (int)($statsClient['en_cours'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="text-muted small">Terminees</div>
                            <div class="h3 mb-0"><?php echo (int)($statsClient['terminees'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon purple"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="text-muted small">Devis en attente</div>
                            <div class="h3 mb-0"><?php echo (int)($statsClient['en_attente_devis'] ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-0 shadow-sm rounded-4">
        <div class="card-header bg-secondary bg-opacity-10 border-0 py-3 d-flex justify-content-between align-items-center">
            <span>Dernieres interventions</span>
            <a href="index.php?action=client_interventions<?php echo $vehicleId > 0 ? '&vehicle_id=' . (int)$vehicleId : ''; ?>" class="btn btn-sm btn-outline-light">
                Voir toutes mes interventions
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($latestInterventions)): ?>
                <div class="p-4 text-center text-muted">Aucune intervention disponible.</div>
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
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestInterventions as $inter): ?>
                                <tr>
                                    <td class="ps-4">#<?php echo (int)$inter['id_intervention']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string)($inter['immatriculation'] ?? '-')); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(trim((string)($inter['vehicle_marque'] ?? '') . ' ' . (string)($inter['vehicle_modele'] ?? ''))); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)($inter['type_nom'] ?? '-')); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars((string)($inter['statut'] ?? '-')); ?></span></td>
                                    <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars((string)($inter['statut_devis'] ?? 'en_attente')); ?></span></td>
                                    <td><?php echo number_format((float)($inter['cout_initial'] ?? 0), 2, ',', ' '); ?> DT</td>
                                    <td class="text-end pe-4">
                                        <a href="index.php?action=intervention_detail&id=<?php echo (int)$inter['id_intervention']; ?>&vehicle_id=<?php echo (int)($inter['id_vehicule'] ?? 0); ?>" class="btn btn-sm btn-primary">Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
