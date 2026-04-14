<?php $pageTitle = 'Dashboard'; $action = 'dashboard'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<h1 class="page-title">Dashboard</h1>
<p class="page-subtitle">Vue d'ensemble du garage – Statistiques en temps réel.</p>

<!-- Cartes de statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-car-front-fill"></i></div>
        <div class="stat-value"><?php echo $totalVehicles; ?></div>
        <div class="stat-label">Véhicules Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-speedometer"></i></div>
        <div class="stat-value"><?php echo number_format($avgKm, 0, ',', ' '); ?></div>
        <div class="stat-label">Kilométrage Moyen</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-fuel-pump-fill"></i></div>
        <div class="stat-value"><?php echo count($fuelStats); ?></div>
        <div class="stat-label">Types de Carburant</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-building"></i></div>
        <div class="stat-value"><?php echo count($brandStats); ?></div>
        <div class="stat-label">Marques Différentes</div>
    </div>
</div>

<!-- Deux colonnes : Répartition par carburant + Répartition par marque -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:2.5rem;">

    <!-- Répartition par Carburant -->
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-fuel-pump me-2"></i>Répartition par Carburant</h3>
        </div>
        <table class="sg-table">
            <thead>
                <tr><th>Carburant</th><th>Nombre</th><th>%</th></tr>
            </thead>
            <tbody>
                <?php foreach ($fuelStats as $fuel => $count): ?>
                    <?php $pct = $totalVehicles > 0 ? round(($count / $totalVehicles) * 100) : 0; ?>
                    <tr>
                        <td><span class="badge-fuel <?php echo strtolower($fuel); ?>"><?php echo htmlspecialchars($fuel); ?></span></td>
                        <td><?php echo $count; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:6px;background:var(--bg-secondary);border-radius:3px;overflow:hidden;">
                                    <div style="width:<?php echo $pct; ?>%;height:100%;background:var(--accent);border-radius:3px;"></div>
                                </div>
                                <span style="font-size:0.8rem;color:var(--text-secondary);min-width:36px;"><?php echo $pct; ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($fuelStats)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune donnée</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Répartition par Marque -->
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-building me-2"></i>Répartition par Marque</h3>
        </div>
        <table class="sg-table">
            <thead>
                <tr><th>Marque</th><th>Nombre</th><th>%</th></tr>
            </thead>
            <tbody>
                <?php foreach ($brandStats as $brand => $count): ?>
                    <?php $pct = $totalVehicles > 0 ? round(($count / $totalVehicles) * 100) : 0; ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($brand); ?></td>
                        <td><?php echo $count; ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:6px;background:var(--bg-secondary);border-radius:3px;overflow:hidden;">
                                    <div style="width:<?php echo $pct; ?>%;height:100%;background:var(--success);border-radius:3px;"></div>
                                </div>
                                <span style="font-size:0.8rem;color:var(--text-secondary);min-width:36px;"><?php echo $pct; ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($brandStats)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune donnée</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Table des Véhicules Récents -->
<div class="sg-table-wrap">
    <div class="table-header">
        <h3><i class="bi bi-clock-history me-2"></i>Véhicules Récents</h3>
        <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline btn-sg-sm">Voir tout <i class="bi bi-arrow-right"></i></a>
    </div>
    <table class="sg-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Marque / Modèle</th>
                <th>Immatriculation</th>
                <th>Année</th>
                <th>Kilométrage</th>
                <th>Carburant</th>
                <th>Date d'ajout</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent = array_slice($vehicles, 0, 5);
            foreach ($recent as $v):
            ?>
                <tr>
                    <td style="color:var(--text-muted);">#<?php echo $v['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($v['marque']); ?></strong> <?php echo htmlspecialchars($v['modele']); ?></td>
                    <td><code style="color:var(--accent);background:var(--bg-secondary);padding:2px 8px;border-radius:4px;"><?php echo htmlspecialchars($v['immatriculation']); ?></code></td>
                    <td><?php echo $v['annee']; ?></td>
                    <td><?php echo number_format($v['kilometrage'], 0, ',', ' '); ?> km</td>
                    <td><span class="badge-fuel <?php echo strtolower($v['carburant']); ?>"><?php echo htmlspecialchars($v['carburant']); ?></span></td>
                    <td style="color:var(--text-secondary);"><?php echo date('d/m/Y H:i', strtotime($v['date_ajout'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($vehicles)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun véhicule enregistré</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
