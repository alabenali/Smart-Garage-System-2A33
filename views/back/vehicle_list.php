<?php $pageTitle = 'Gestion des Véhicules'; $action = 'manageVehicles'; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Gestion des Véhicules</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo count($vehicles); ?> véhicule<?php echo count($vehicles) !== 1 ? 's' : ''; ?> dans le système
        </p>
    </div>
    <a href="index.php?action=addVehicle" class="btn-sg btn-sg-primary">
        <i class="bi bi-plus-lg"></i> Ajouter un Véhicule
    </a>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
<?php endif; ?>

<?php if (empty($vehicles)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">📋</div>
        <h3>Aucun véhicule trouvé</h3>
        <p>Aucun véhicule n'est enregistré dans le système.</p>
        <a href="index.php?action=addVehicle" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter un Véhicule
        </a>
    </div>
<?php else: ?>
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-list-ul me-2"></i>Liste Complète</h3>
            <span style="color:var(--text-muted);font-size:0.85rem;"><?php echo count($vehicles); ?> résultats</span>
        </div>
        <table class="sg-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Marque</th>
                    <th>Modèle</th>
                    <th>Immatriculation</th>
                    <th>Couleur</th>
                    <th>Année</th>
                    <th>Km</th>
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
                    <tr>
                        <td style="color:var(--text-muted);">#<?php echo $v['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($v['marque']); ?></strong></td>
                        <td><?php echo htmlspecialchars($v['modele']); ?></td>
                        <td><?php echo formatPlate($v['immatriculation'] ?? ''); ?></td>
                        <td>
                            <span class="badge-color">
                                <span class="color-dot" style="background:<?php echo $dotColor; ?>;"></span>
                                <?php echo htmlspecialchars($v['couleur']); ?>
                            </span>
                        </td>
                        <td><?php echo $v['annee']; ?></td>
                        <td><?php echo number_format($v['kilometrage'], 0, ',', ' '); ?></td>
                        <td><span class="badge-fuel <?php echo strtolower($v['carburant']); ?>"><?php echo htmlspecialchars($v['carburant']); ?></span></td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;"><?php echo date('d/m/Y', strtotime($v['date_ajout'])); ?></td>
                        <td>
                            <div class="btn-group-actions">
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

<?php require __DIR__ . '/layout_footer.php'; ?>
