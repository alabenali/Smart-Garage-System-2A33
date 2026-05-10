<?php $pageTitle = 'Dashboard'; $action = 'dashboard'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<h1 class="page-title">Dashboard – Pièces</h1>
<p class="page-subtitle">Vue d'ensemble du stock de pièces – Statistiques en temps réel.</p>

<!-- Cartes de statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-box-seam-fill"></i></div>
        <div class="stat-value"><?php echo $totalPieces; ?></div>
        <div class="stat-label">Références Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-boxes"></i></div>
        <div class="stat-value"><?php echo number_format($totalStock, 0, ',', ' '); ?></div>
        <div class="stat-label">Pièces en Stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-value"><?php echo number_format($totalValue, 0, ',', ' '); ?></div>
        <div class="stat-label">Valeur Stock (DT)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="stat-value"><?php echo $alertCount; ?></div>
        <div class="stat-label">Alertes Stock</div>
    </div>
</div>

<!-- Deux colonnes : répartition par catégorie + répartition par marque -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:2.5rem;">

    <!-- Répartition par catégorie -->
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-tags me-2"></i>Répartition par Catégorie</h3>
        </div>
        <table class="sg-table">
            <thead>
                <tr><th>Catégorie</th><th>Nombre</th><th>%</th></tr>
            </thead>
            <tbody>
                <?php foreach ($categoryStats as $cat => $count): ?>
                    <?php $pct = $totalPieces > 0 ? round(($count / $totalPieces) * 100) : 0; ?>
                    <tr>
                        <td><span class="badge-category"><?php echo htmlspecialchars($cat); ?></span></td>
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
                <?php if (empty($categoryStats)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune donnée</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Répartition par marque -->
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
                    <?php $pct = $totalPieces > 0 ? round(($count / $totalPieces) * 100) : 0; ?>
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

<!-- Alertes de stock bas -->
<?php
$alertPieces = array_filter($pieces, function($p) { return $p['quantite_stock'] <= $p['seuil_alerte']; });
?>
<?php if (!empty($alertPieces)): ?>
<div class="sg-table-wrap" style="margin-bottom:2.5rem;">
    <div class="table-header">
        <h3><i class="bi bi-exclamation-triangle me-2" style="color:var(--warning);"></i>Alertes Stock Bas</h3>
        <span style="color:var(--danger);font-size:0.85rem;font-weight:600;"><?php echo count($alertPieces); ?> alerte<?php echo count($alertPieces) > 1 ? 's' : ''; ?></span>
    </div>
    <table class="sg-table">
        <thead>
            <tr>
                <th>Référence</th>
                <th>Nom</th>
                <th>Stock</th>
                <th>Seuil</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alertPieces as $p): ?>
                <tr>
                    <td><code style="color:var(--accent);background:var(--bg-secondary);padding:2px 8px;border-radius:4px;"><?php echo htmlspecialchars($p['reference']); ?></code></td>
                    <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                    <td style="color:var(--danger);font-weight:700;"><?php echo $p['quantite_stock']; ?></td>
                    <td style="color:var(--text-secondary);"><?php echo $p['seuil_alerte']; ?></td>
                    <td>
                        <?php if ($p['quantite_stock'] <= 0): ?>
                            <span class="badge-stock out-of-stock">Rupture</span>
                        <?php else: ?>
                            <span class="badge-stock low-stock">Stock faible</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Tableau des pièces récentes -->
<div class="sg-table-wrap">
    <div class="table-header">
        <h3><i class="bi bi-clock-history me-2"></i>Pièces Récentes</h3>
        <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline btn-sg-sm">Voir tout <i class="bi bi-arrow-right"></i></a>
    </div>
    <table class="sg-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Référence</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Date d'ajout</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent = array_slice($pieces, 0, 5);
            foreach ($recent as $p):
            ?>
                <tr>
                    <td style="color:var(--text-muted);">#<?php echo $p['id_piece']; ?></td>
                    <td><code style="color:var(--accent);background:var(--bg-secondary);padding:2px 8px;border-radius:4px;"><?php echo htmlspecialchars($p['reference']); ?></code></td>
                    <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong> <span style="color:var(--text-muted);font-size:0.82rem;"><?php echo htmlspecialchars($p['marque']); ?></span></td>
                    <td><span class="badge-category"><?php echo htmlspecialchars($p['categorie']); ?></span></td>
                    <td class="badge-price"><?php echo number_format($p['prix_unitaire'], 2, ',', ' '); ?> DT</td>
                    <td>
                        <?php
                        if ($p['quantite_stock'] <= 0) {
                            echo '<span class="badge-stock out-of-stock">0</span>';
                        } elseif ($p['quantite_stock'] <= $p['seuil_alerte']) {
                            echo '<span class="badge-stock low-stock">' . $p['quantite_stock'] . '</span>';
                        } else {
                            echo '<span class="badge-stock in-stock">' . $p['quantite_stock'] . '</span>';
                        }
                        ?>
                    </td>
                    <td style="color:var(--text-secondary);"><?php echo date('d/m/Y H:i', strtotime($p['date_ajout'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pieces)): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune pièce enregistrée</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
