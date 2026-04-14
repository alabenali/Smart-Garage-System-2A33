<?php $pageTitle = 'Nos Véhicules'; $action = 'showVehicles'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Nos Véhicules</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo count($vehicles); ?> véhicule<?php echo count($vehicles) !== 1 ? 's' : ''; ?> enregistré<?php echo count($vehicles) !== 1 ? 's' : ''; ?>
        </p>
    </div>
    <a href="index.php?action=addVehicle" class="btn-sg btn-sg-primary">
        <i class="bi bi-plus-lg"></i> Ajouter
    </a>
</div>

<?php if (empty($vehicles)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">🚗</div>
        <h3>Aucun véhicule trouvé</h3>
        <p>Commencez par ajouter votre premier véhicule au garage.</p>
        <a href="index.php?action=addVehicle" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter un Véhicule
        </a>
    </div>
<?php else: ?>
    <div class="vehicle-grid">
        <?php foreach ($vehicles as $v): ?>
            <?php
                // Associer les noms de couleurs en français aux couleurs CSS pour la pastille
                $colorMap = [
                    'blanc' => '#f5f5f5', 'noir' => '#1a1a1a', 'gris' => '#9ca3af',
                    'rouge' => '#ef4444', 'bleu' => '#3b82f6', 'vert' => '#22c55e',
                    'jaune' => '#f59e0b', 'orange' => '#f97316', 'marron' => '#92400e',
                    'beige' => '#d4a574', 'violet' => '#8b5cf6', 'rose' => '#ec4899',
                ];
                $dotColor = $colorMap[strtolower($v['couleur'])] ?? '#6b7280';
                $fuelClass = strtolower($v['carburant']);
            ?>
            <div class="vehicle-card">
                <div class="vc-header">
                    <div>
                        <div class="vc-brand"><?php echo htmlspecialchars($v['marque']); ?></div>
                        <div class="vc-model"><?php echo htmlspecialchars($v['modele']); ?></div>
                    </div>
                    <span class="vc-plate"><?php echo htmlspecialchars($v['immatriculation']); ?></span>
                </div>

                <div class="vc-details">
                    <div class="vc-detail">
                        <span class="vc-detail-label">Couleur</span>
                        <span class="vc-detail-value badge-color">
                            <span class="color-dot" style="background:<?php echo $dotColor; ?>;"></span>
                            <?php echo htmlspecialchars($v['couleur']); ?>
                        </span>
                    </div>
                    <div class="vc-detail">
                        <span class="vc-detail-label">Année</span>
                        <span class="vc-detail-value"><?php echo $v['annee']; ?></span>
                    </div>
                    <div class="vc-detail">
                        <span class="vc-detail-label">Kilométrage</span>
                        <span class="vc-detail-value"><?php echo number_format($v['kilometrage'], 0, ',', ' '); ?> km</span>
                    </div>
                    <div class="vc-detail">
                        <span class="vc-detail-label">Carburant</span>
                        <span class="vc-detail-value">
                            <span class="badge-fuel <?php echo $fuelClass; ?>"><?php echo htmlspecialchars($v['carburant']); ?></span>
                        </span>
                    </div>
                </div>

                <div class="vc-footer">
                    <span class="vc-date"><i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y', strtotime($v['date_ajout'])); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
