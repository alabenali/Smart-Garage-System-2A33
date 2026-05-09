<?php $pageTitle = 'Dashboard'; $action = 'dashboard'; $extraJs = ['views/js/urgence_live.js']; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<h1 class="page-title">Dashboard</h1>
<p class="page-subtitle">Vue d'ensemble du garage – Statistiques en temps réel.</p>

<div class="stats-grid" style="margin-top:1.5rem;">
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
    <div class="stat-card">
        <div class="stat-icon cyan"><i class="bi bi-calendar-check"></i></div>
        <div class="stat-value"><?php echo $totalRdv; ?></div>
        <div class="stat-label">Total RDV</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-calendar-day"></i></div>
        <div class="stat-value"><?php echo $rdvStats['rdv_jour'] ?? 0; ?></div>
        <div class="stat-label">RDV Aujourd'hui</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="bi bi-calendar-week"></i></div>
        <div class="stat-value"><?php echo $rdvStats['rdv_semaine'] ?? 0; ?></div>
        <div class="stat-label">RDV Cette Semaine</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-value"><?php echo $rdvStats['rdv_attente'] ?? 0; ?></div>
        <div class="stat-label">En Attente Confirmation</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-car-front"></i></div>
        <div class="stat-value"><?php echo number_format((float) ($avgVehiclesPerClient ?? 0), 1, ',', ' '); ?></div>
        <div class="stat-label">Vehicules par client</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-card-checklist"></i></div>
        <div class="stat-value"><?php echo number_format((float) ($avgRdvPerClient ?? 0), 1, ',', ' '); ?></div>
        <div class="stat-label">RDV par client</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-activity"></i></div>
        <div class="stat-value"><?php echo number_format((float) ($avgUrgence ?? 0), 1, ',', ' '); ?>/10</div>
        <div class="stat-label">Score urgence moyen</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="stat-value"><?php echo count($problematicVehicles ?? []); ?></div>
        <div class="stat-label">Vehicules a surveiller</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-box-seam-fill"></i></div>
        <div class="stat-value"><?php echo (int) ($partsOrderStats['total_pieces'] ?? 0); ?></div>
        <div class="stat-label">Pi&egrave;ces total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-boxes"></i></div>
        <div class="stat-value"><?php echo number_format((int) ($partsOrderStats['total_stock'] ?? 0), 0, ',', ' '); ?></div>
        <div class="stat-label">Pi&egrave;ces en stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-cart3"></i></div>
        <div class="stat-value"><?php echo (int) ($partsOrderStats['total_commandes'] ?? 0); ?></div>
        <div class="stat-label">Commandes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="stat-value"><?php echo (int) ($partsOrderStats['alertes_stock'] ?? 0); ?></div>
        <div class="stat-label">Alertes stock</div>
    </div>
</div>

<div id="urgenceStreamConfig" data-urgence-stream="api/rendez-vous/stream"></div>

<div class="sg-table-wrap" id="urgentRdvPanel" data-urgents-url="api/rendez-vous/urgents" data-urgence-stream="api/rendez-vous/stream" style="margin-bottom:2rem;">
    <div class="table-header">
        <h3><i class="bi bi-exclamation-triangle me-2"></i>RDV urgents (score >= 7)</h3>
        <a href="index.php?action=backRdvList" class="btn-sg btn-sg-outline btn-sg-sm">Voir liste <i class="bi bi-arrow-right"></i></a>
    </div>
    <table class="sg-table">
        <thead>
            <tr><th>Date/Heure</th><th>Type panne</th><th>Statut</th><th>Urgence</th></tr>
        </thead>
        <tbody id="urgentRdvBody">
            <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Chargement...</td></tr>
        </tbody>
    </table>
</div>

<?php
$todayKey = date('Y-m-d');
$upcomingHolidays = [];
foreach (($holidays ?? []) as $holidayDate => $holidayName) {
    if ($holidayDate >= $todayKey) {
        $upcomingHolidays[$holidayDate] = $holidayName;
    }
}
ksort($upcomingHolidays);
$upcomingHolidays = array_slice($upcomingHolidays, 0, 8, true);
?>

<div class="sg-table-wrap" style="margin-bottom:2rem;">
    <div class="table-header">
        <h3><i class="bi bi-calendar-event me-2"></i>Jours fériés Tunisie (<?php echo date('Y'); ?>)</h3>
    </div>
    <table class="sg-table">
        <thead>
            <tr><th>Date</th><th>Jour férié</th></tr>
        </thead>
        <tbody>
            <?php foreach ($upcomingHolidays as $holidayDate => $holidayName): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($holidayDate))); ?></td>
                    <td><?php echo htmlspecialchars($holidayName); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($upcomingHolidays)): ?>
                <tr><td colspan="2" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun jour férié à venir.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem; margin-bottom:2rem;">
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-trophy me-2"></i>Clients les plus actifs</h3>
            <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline btn-sg-sm">Clients <i class="bi bi-arrow-right"></i></a>
        </div>
        <table class="sg-table">
            <thead><tr><th>Client</th><th>Email</th><th>RDV</th></tr></thead>
            <tbody>
                <?php foreach (($topActiveClients ?? []) as $client): ?>
                    <tr>
                        <td><a class="vehicle-table-link" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></a></td>
                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                        <td><span class="status-badge status-confirme"><?php echo (int) $client['rdv_total']; ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($topActiveClients)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune activite client.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-exclamation-triangle me-2"></i>Vehicules les plus problematiques</h3>
        </div>
        <table class="sg-table">
            <thead><tr><th>Vehicule</th><th>RDV</th><th>Urgence moy.</th></tr></thead>
            <tbody>
                <?php foreach (($problematicVehicles ?? []) as $vehicle): ?>
                    <?php $avg = round((float) ($vehicle['avg_urgence'] ?? 0), 1); ?>
                    <tr>
                        <td><a class="vehicle-table-link" href="index.php?action=vehicleDetail&id=<?php echo (int) $vehicle['id']; ?>"><?php echo htmlspecialchars(trim(($vehicle['marque'] ?? '') . ' ' . ($vehicle['modele'] ?? '') . ' ' . ($vehicle['immatriculation'] ?? ''))); ?></a></td>
                        <td><?php echo (int) ($vehicle['rdv_total'] ?? 0); ?></td>
                        <td><span class="urgence-badge <?php echo $avg >= 7 ? 'urgence-high' : ($avg >= 4 ? 'urgence-medium' : 'urgence-low'); ?>"><?php echo number_format($avg, 1, ',', ' '); ?>/10</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($problematicVehicles)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun historique vehicule.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Deux colonnes : Répartition par carburant + Répartition par statut RDV -->
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

    <!-- Répartition par Statut RDV -->
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-list-check me-2"></i>Répartition des RDV par Statut</h3>
            <a href="index.php?action=backRdvList" class="btn-sg btn-sg-outline btn-sg-sm">Voir tous <i class="bi bi-arrow-right"></i></a>
        </div>
        <table class="sg-table">
            <thead>
                <tr><th>Statut</th><th>Nombre</th><th>%</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rdvParStatut as $statut => $count): ?>
                    <?php $pct = $totalRdv > 0 ? round(($count / $totalRdv) * 100) : 0; ?>
                    <tr>
                        <td>
                            <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($statut)); ?>">
                                <?php echo htmlspecialchars($statut); ?>
                            </span>
                        </td>
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
                <?php if (empty($rdvParStatut)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun RDV</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Table des Marques de Véhicules -->
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
                    <td><?php echo formatPlate($v['immatriculation'] ?? ''); ?></td>
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
