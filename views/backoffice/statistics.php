<?php
// views/backoffice/statistics.php

require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['admin_id'])) { 
    header('Location: admin_login.php'); 
    exit; 
}

// Préparer les données mensuelles pour le graphique
$months = [];
$monthCounts = [];
$monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[$m] = 0;
}
foreach ($monthlyStats as $stat) {
    if (isset($months[$stat['month']])) {
        $months[$stat['month']] = (int) $stat['count'];
    }
}
foreach ($months as $m => $count) {
    $monthLabels[] = date('M Y', strtotime($m));
    $monthCounts[] = $count;
}

// Préparer les données par jour de la semaine
$dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
$dayCounts = array_fill(1, 7, 0);
foreach ($dayStats as $stat) {
    $dayCounts[(int)$stat['day_num']] = (int) $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Smart Garage Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 12px;
            padding: 25px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stat-card .number {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-card .label {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-container h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.1rem;
        }
        .chart-container canvas {
            max-height: 250px;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-container h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            color: #555;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
    <?php
    $adminPic = $_SESSION['admin_profile_pic'] ?? null;
    $adminPicUrl = null;
    if ($adminPic) {
        $sp = __DIR__ . '/../../' . $adminPic;
        if (file_exists($sp)) $adminPicUrl = '/projet_final/' . $adminPic;
    }
    ?>
    <div style="text-align:center;padding:15px 0;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:10px;">
        <?php if ($adminPicUrl): ?>
            <img src="<?= htmlspecialchars($adminPicUrl) ?>" alt="Admin" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #00E5FF;margin-bottom:8px;">
        <?php else: ?>
            <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:white;margin:0 auto 8px;border:3px solid #00E5FF;">
                <?= strtoupper(substr($_SESSION['admin_nom'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div style="color:#ccc;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['admin_nom']) ?></div>
    </div>
    <nav>
        <ul>
            <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=showStatistics" class="active"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </nav>
</aside>

<main class="main">
    <div class="top-bar">
        <h1><i class="fas fa-chart-bar" style="color:#00E5FF;"></i> Statistiques Clients</h1>
        <span class="admin-badge"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($_SESSION['admin_nom']) ?></span>
    </div>

    <!-- Cartes de statistiques principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-users"></i></div>
            <div class="number"><?= $totalClients ?></div>
            <div class="label">Total Clients</div>
        </div>
        <div class="stat-card green">
            <div class="icon"><i class="fas fa-user-check"></i></div>
            <div class="number"><?= $activeClients ?></div>
            <div class="label">Clients Actifs</div>
        </div>
        <div class="stat-card orange">
            <div class="icon"><i class="fas fa-user-times"></i></div>
            <div class="number"><?= $inactiveClients ?></div>
            <div class="label">Clients Inactifs</div>
        </div>
        <div class="stat-card purple">
            <div class="icon"><i class="fas fa-envelope-check"></i></div>
            <div class="number"><?= $verifiedClients ?></div>
            <div class="label">Emails Vérifiés</div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="charts-row">
        <div class="chart-container">
            <h3><i class="fas fa-calendar-alt"></i> Inscriptions par mois (12 derniers mois)</h3>
            <canvas id="monthlyChart"></canvas>
        </div>
        <div class="chart-container">
            <h3><i class="fas fa-calendar-week"></i> Inscriptions par jour (30 derniers jours)</h3>
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <!-- Répartition par statut -->
    <div class="charts-row">
        <div class="chart-container">
            <h3><i class="fas fa-chart-pie"></i> Répartition par statut</h3>
            <canvas id="statusChart"></canvas>
        </div>
        <div class="table-container">
            <h3><i class="fas fa-info-circle"></i> Détails supplémentaires</h3>
            <table>
                <tr>
                    <td><i class="fas fa-user"></i> Clients avec photo de profil</td>
                    <td><strong><?= $clientsWithPhoto ?></strong></td>
                </tr>
                <tr>
                    <td><i class="fas fa-envelope"></i> Clients avec email vérifié</td>
                    <td><strong><?= $verifiedClients ?></strong></td>
                </tr>
                <tr>
                    <td><i class="fas fa-percentage"></i> Taux d'activité</td>
                    <td><strong><?= $totalClients > 0 ? round(($activeClients / $totalClients) * 100, 1) : 0 ?>%</strong></td>
                </tr>
                <tr>
                    <td><i class="fas fa-percentage"></i> Taux de vérification email</td>
                    <td><strong><?= $totalClients > 0 ? round(($verifiedClients / $totalClients) * 100, 1) : 0 ?>%</strong></td>
                </tr>
            </table>
        </div>
    </div>
</main>

<script>
    // Graphique mensuel
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [{
                label: 'Inscriptions',
                data: <?= json_encode($monthCounts) ?>,
                borderColor: '#00E5FF',
                backgroundColor: 'rgba(0, 229, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#00E5FF',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Graphique quotidien
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dayNames) ?>,
            datasets: [{
                label: 'Inscriptions',
                data: <?= json_encode(array_values($dayCounts)) ?>,
                backgroundColor: ['#ff6384', '#36a2eb', '#4bc0c0', '#ff9f40', '#9966ff', '#ffcd56', '#c9cbcf'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Graphique circulaire (statut)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Actifs', 'Inactifs'],
            datasets: [{
                data: [<?= $activeClients ?>, <?= $inactiveClients ?>],
                backgroundColor: ['#38ef7d', '#ff6b6b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { 
                legend: { position: 'bottom' }
            }
        }
    });
</script>
</body>
</html>