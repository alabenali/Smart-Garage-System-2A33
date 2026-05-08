<?php $pageTitle = 'Gestion des Véhicules'; $action = 'manageVehicles'; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Gestion des Véhicules</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo (int) ($totalVehicles ?? count($vehicles)); ?> véhicule<?php echo (int) ($totalVehicles ?? count($vehicles)) !== 1 ? 's' : ''; ?> dans le système
        </p>
    </div>
    <a href="index.php?action=addVehicleBack" class="btn-sg btn-sg-primary">
        <i class="bi bi-plus-lg"></i> Ajouter un Véhicule
    </a>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="sg-form-wrap" style="margin-bottom:1.25rem;">
    <form method="GET" action="index.php" style="display:grid; grid-template-columns:minmax(0, 1fr) auto auto; gap:0.75rem; align-items:end;">
        <input type="hidden" name="action" value="manageVehicles">
        <div class="sg-form-group">
            <label for="vehicleSearch">Recherche</label>
            <input
                type="search"
                id="vehicleSearch"
                name="search"
                value="<?php echo htmlspecialchars($search ?? ''); ?>"
                placeholder="ID, marque, modèle, immatriculation, couleur, année, carburant..."
            >
        </div>
        <button type="submit" class="btn-sg btn-sg-primary">
            <i class="bi bi-search"></i> Rechercher
        </button>
        <?php if (!empty($search)): ?>
            <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline">
                <i class="bi bi-x-lg"></i> Réinitialiser
            </a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($vehicles)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon"><i class="bi bi-search"></i></div>
        <?php if (!empty($search)): ?>
            <h3>Aucun résultat</h3>
            <p>Aucun véhicule ne correspond à « <?php echo htmlspecialchars($search); ?> ».</p>
            <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline">
                <i class="bi bi-arrow-counterclockwise"></i> Voir toute la liste
            </a>
        <?php else: ?>
            <h3>Aucun véhicule trouvé</h3>
            <p>Aucun véhicule n'est enregistré dans le système.</p>
            <a href="index.php?action=addVehicleBack" class="btn-sg btn-sg-primary">
                <i class="bi bi-plus-lg"></i> Ajouter un Véhicule
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-list-ul me-2"></i>Liste Complète</h3>
            <span style="color:var(--text-muted);font-size:0.85rem;">
                <?php echo count($vehicles); ?> résultat<?php echo count($vehicles) !== 1 ? 's' : ''; ?>
                <?php if (!empty($search)): ?>
                    sur <?php echo (int) ($totalVehicles ?? count($vehicles)); ?>
                <?php endif; ?>
            </span>
        </div>
        <table class="sg-table" id="vehiclesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Marque</th>
                    <th>Modèle</th>
                    <th>Immatriculation</th>
                    <th>Client</th>
                    <th>Couleur</th>
                    <th>Année</th>
                    <th>Km</th>
                    <th>Santé</th>
                    <th>Carburant</th>
                    <th>Date d'ajout</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                    <?php
                        $colorMap = [
                            'blanc' => '#f5f5f5', 'noir' => '#1a1a1a', 'gris' => '#9ca3af',
                            'rouge' => '#ef4444', 'bleu' => '#3b82f6', 'vert' => '#22c55e',
                            'jaune' => '#f59e0b', 'orange' => '#f97316', 'marron' => '#92400e',
                            'beige' => '#d4a574', 'violet' => '#8b5cf6', 'rose' => '#ec4899',
                        ];
                        $dotColor = $colorMap[strtolower($v['couleur'])] ?? '#6b7280';
                    ?>
                    <?php $detailUrl = 'index.php?action=vehicleDetail&id=' . (int) $v['id']; ?>
                    <tr class="vehicle-click-row" data-href="<?php echo htmlspecialchars($detailUrl); ?>" tabindex="0" title="Voir la fiche et l'historique">
                        <td style="color:var(--text-muted);">#<?php echo $v['id']; ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($detailUrl); ?>" class="vehicle-table-link">
                                <strong><?php echo htmlspecialchars($v['marque']); ?></strong>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($v['modele']); ?></td>
                        <td><?php echo formatPlate($v['immatriculation'] ?? ''); ?></td>
                        <td>
                            <?php $owner = !empty($v['id_client']) ? ($clientById[(int) $v['id_client']] ?? null) : null; ?>
                            <?php if ($owner): ?>
                                <a class="vehicle-table-link" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $v['id_client']; ?>">
                                    <?php echo htmlspecialchars(trim(($owner['prenom'] ?? '') . ' ' . ($owner['nom'] ?? ''))); ?>
                                </a>
                                <div style="color:var(--text-muted);font-size:0.78rem;"><?php echo htmlspecialchars($owner['email'] ?? ''); ?></div>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">Non assigne</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-color">
                                <span class="color-dot" style="background:<?php echo $dotColor; ?>;"></span>
                                <?php echo htmlspecialchars($v['couleur']); ?>
                            </span>
                        </td>
                        <td><?php echo $v['annee']; ?></td>
                        <td><?php echo number_format($v['kilometrage'], 0, ',', ' '); ?></td>
                        <td>
                            <span class="badge bg-secondary vehicle-health-badge" data-vehicle-id="<?php echo (int) $v['id']; ?>">--</span>
                        </td>
                        <td><span class="badge-fuel <?php echo strtolower($v['carburant']); ?>"><?php echo htmlspecialchars($v['carburant']); ?></span></td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;"><?php echo date('d/m/Y', strtotime($v['date_ajout'])); ?></td>
                        <td>
                            <div class="btn-group-actions">
                                <a href="<?php echo htmlspecialchars($detailUrl); ?>" class="btn-sg btn-sg-outline btn-sg-sm" title="Voir fiche">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="index.php?action=editVehicle&id=<?php echo $v['id']; ?>" class="btn-sg btn-sg-success btn-sg-sm" title="Modifier">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <button type="button" class="btn-sg btn-sg-danger btn-sg-sm"
                                        onclick="confirmDelete('index.php?action=deleteVehicle&id=<?php echo $v['id']; ?>')"
                                        title="Supprimer">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('vehiclesTable');
    if (!table) {
        return;
    }

    function scoreToColor(score) {
        if (score >= 80) {
            return 'success';
        }
        if (score >= 60) {
            return 'primary';
        }
        if (score >= 40) {
            return 'warning';
        }
        if (score >= 20) {
            return 'danger';
        }
        return 'dark';
    }

    function openVehicle(row) {
        const href = row ? row.getAttribute('data-href') : '';
        if (href) {
            window.location.href = href;
        }
    }

    table.addEventListener('click', function (event) {
        if (event.target.closest('a, button, input, select, textarea')) {
            return;
        }

        openVehicle(event.target.closest('.vehicle-click-row[data-href]'));
    });

    table.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const row = event.target.closest('.vehicle-click-row[data-href]');
        if (!row) {
            return;
        }

        event.preventDefault();
        openVehicle(row);
    });

    const badges = table.querySelectorAll('.vehicle-health-badge[data-vehicle-id]');
    badges.forEach(function (badge) {
        const id = badge.getAttribute('data-vehicle-id');
        if (!id) {
            return;
        }

        fetch('api/vehicle-health-export.php?id_vehicle=' + encodeURIComponent(id))
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data || typeof data.score === 'undefined') {
                    badge.textContent = '--';
                    return;
                }

                const score = parseInt(data.score, 10) || 0;
                const niveau = data.niveau ? String(data.niveau) : '';
                const color = scoreToColor(score);

                badge.className = 'badge bg-' + color + ' vehicle-health-badge';
                badge.textContent = score + '/100 ' + niveau;
            })
            .catch(function () {
                badge.textContent = '--';
            });
    });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
