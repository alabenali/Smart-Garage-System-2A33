<?php 
$pageTitle = 'Dashboard Diagnostique';
$action = 'dashboard';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <h1 class="page-title text-white">Dashboard Diagnostique</h1>
    <p class="page-subtitle text-muted">Aperçu rapide des diagnostics et devis.</p>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-dark text-white border-0 shadow rounded-4 p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-clipboard2-pulse text-primary fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small">Total Diagnostics</h6>
                        <h4 class="mb-0 fw-bold"><?php echo $stats['total']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white border-0 shadow rounded-4 p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-exclamation-triangle text-danger fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small">Diagnostics Urgents</h6>
                        <h4 class="mb-0 fw-bold"><?php echo $stats['urgent']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white border-0 shadow rounded-4 p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-clock-history text-warning fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small">Devis en attente</h6>
                        <h4 class="mb-0 fw-bold"><?php echo $stats['waiting']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white border-0 shadow rounded-4 p-3 h-100">
                <div class="d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3">
                        <i class="bi bi-check-circle text-success fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small">Devis acceptés</h6>
                        <h4 class="mb-0 fw-bold"><?php echo $stats['completed']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart and Quick Actions -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card bg-dark text-white border-0 shadow rounded-4 p-4 mb-4">
                <h5 class="mb-4"><i class="bi bi-graph-up me-2"></i>Analyse par Gravité</h5>
                <canvas id="graviteChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card bg-dark text-white border-0 shadow rounded-4 p-4 mb-4">
                <h5 class="mb-4"><i class="bi bi-info-circle me-2"></i>Actions Rapides</h5>
                <div class="d-grid gap-2">
                    <a href="index.php?action=diagnostics" class="btn btn-primary py-2 rounded-3">
                        <i class="bi bi-plus-circle me-2"></i>Nouveau Diagnostic
                    </a>
                    <a href="index.php?action=manageVehicles" class="btn btn-outline-light py-2 rounded-3">
                        <i class="bi bi-car-front me-2"></i>Gérer les Véhicules
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('graviteChart').getContext('2d');
    const labels = ['Faible', 'Moyen', 'Élevé'];
    const graviteStats = <?php echo json_encode($diagController->diagnosticModel->getGraviteStats()); ?>;
    
    const countMap = { 'Faible': 0, 'Moyen': 0, 'Élevé': 0 };
    graviteStats.forEach(s => countMap[s.gravite] = s.count);
    
    const data = labels.map(l => countMap[l]);
    const colors = ['#198754', '#ffc107', '#dc3545'];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nombre de diagnostics',
                data: data,
                backgroundColor: colors,
                borderRadius: 8
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } },
                x: { grid: { display: false }, ticks: { color: '#888' } }
            }
        }
    });
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>

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
