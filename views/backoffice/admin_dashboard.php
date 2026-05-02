<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }

$db          = Database::getConnection();
$total       = (int)$db->query("SELECT COUNT(*) FROM user WHERE post='client'")->fetchColumn();
$actifs      = (int)$db->query("SELECT COUNT(*) FROM user WHERE post='client' AND statut='actif'")->fetchColumn();
$bloques     = $total - $actifs;
$newThisMonth= (int)$db->query("SELECT COUNT(*) FROM user WHERE post='client' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();

// Registrations by month (last 6 months)
$byMonth = $db->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') as mois, DATE_FORMAT(created_at,'%Y-%m') as ym, COUNT(*) as total
    FROM user WHERE post='client' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym ASC
")->fetchAll();

$adminPic    = $_SESSION['admin_profile_pic'] ?? null;
$adminPicUrl = null;
if ($adminPic) { $sp = __DIR__.'/../../'.$adminPic; if(file_exists($sp)) $adminPicUrl='/projet_final/'.$adminPic; }
$tauxActivite = $total > 0 ? round($actifs/$total*100) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard - Smart Garage Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
/* ── Stat Cards ── */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:24px; }
.kpi-card { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:20px; display:flex; align-items:center; gap:16px; transition:transform 0.2s,border 0.2s; }
.kpi-card:hover { transform:translateY(-3px); }
.kpi-icon { width:50px; height:50px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.kpi-val  { font-size:2rem; font-weight:800; line-height:1; }
.kpi-lbl  { font-size:0.72rem; color:#888; margin-top:4px; }
.kpi-blue  { border-color:rgba(0,229,255,0.2); } .kpi-blue  .kpi-icon { background:rgba(0,229,255,0.15); color:#00E5FF; } .kpi-blue  .kpi-val { color:#00E5FF; }
.kpi-green { border-color:rgba(0,230,118,0.2); } .kpi-green .kpi-icon { background:rgba(0,230,118,0.15); color:#00e676; } .kpi-green .kpi-val { color:#00e676; }
.kpi-red   { border-color:rgba(255,82,82,0.2);  } .kpi-red   .kpi-icon { background:rgba(255,82,82,0.15);  color:#ff5252; } .kpi-red   .kpi-val { color:#ff5252; }
.kpi-purple{ border-color:rgba(167,139,250,0.2);} .kpi-purple.kpi-icon { background:rgba(167,139,250,0.15); color:#a78bfa; } .kpi-purple .kpi-val { color:#a78bfa; }

/* ── Charts Grid ── */
.charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
@media(max-width:900px){.charts-grid{grid-template-columns:1fr;}}
.chart-box { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:22px; }
.chart-box h3 { margin:0 0 18px; color:#ccc; font-size:0.9rem; display:flex; align-items:center; gap:8px; }
.chart-box h3 i { color:#00E5FF; }
.chart-full { grid-column:1/-1; }

/* ── Progress bars ── */
.progress-item { margin-bottom:14px; }
.progress-label { display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.8rem; color:#aaa; }
.progress-track { height:8px; background:rgba(255,255,255,0.06); border-radius:6px; overflow:hidden; }
.progress-fill  { height:100%; border-radius:6px; transition:width 1.2s cubic-bezier(0.4,0,0.2,1); width:0; }

/* ── Quick actions ── */
.quick-row { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
.qa-btn { flex:1; min-width:160px; padding:14px 18px; border-radius:14px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.04); color:#ccc; text-decoration:none; display:flex; align-items:center; gap:10px; font-size:0.88rem; transition:all 0.2s; }
.qa-btn:hover { background:rgba(0,229,255,0.1); border-color:rgba(0,229,255,0.3); color:#00E5FF; transform:translateY(-2px); }
.qa-btn i { font-size:1.1rem; }
</style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
    <div style="text-align:center;padding:14px 0;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:10px;">
        <?php if($adminPicUrl): ?>
            <img src="<?=htmlspecialchars($adminPicUrl)?>" style="width:65px;height:65px;border-radius:50%;object-fit:cover;border:3px solid #00E5FF;margin-bottom:7px;">
        <?php else: ?>
            <div style="width:65px;height:65px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1.7rem;color:white;margin:0 auto 7px;border:3px solid #00E5FF;">
                <?=strtoupper(substr($_SESSION['admin_nom'],0,1))?>
            </div>
        <?php endif; ?>
        <div style="color:#ccc;font-size:0.85rem;"><?=htmlspecialchars($_SESSION['admin_nom'])?></div>
    </div>
    <nav><ul>
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AIController.php?action=showAssistant" style="color:#a78bfa;"><i class="fas fa-robot"></i> AI Helper</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main">
    <?php if(!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($_SESSION['success'])?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h1><i class="fas fa-chart-line" style="color:#00E5FF;"></i> Tableau de bord</h1>
        <span class="admin-badge"><i class="fas fa-user-shield"></i> <?=htmlspecialchars($_SESSION['admin_nom'])?></span>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="fas fa-users"></i></div>
            <div><div class="kpi-val"><?=$total?></div><div class="kpi-lbl">Total Clients</div></div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-icon"><i class="fas fa-user-check"></i></div>
            <div><div class="kpi-val"><?=$actifs?></div><div class="kpi-lbl">Clients Actifs</div></div>
        </div>
        <div class="kpi-card kpi-red">
            <div class="kpi-icon"><i class="fas fa-user-lock"></i></div>
            <div><div class="kpi-val"><?=$bloques?></div><div class="kpi-lbl">Clients Bloqués</div></div>
        </div>
        <div class="kpi-card kpi-purple">
            <div class="kpi-icon" style="background:rgba(167,139,250,0.15);color:#a78bfa;"><i class="fas fa-user-plus"></i></div>
            <div><div class="kpi-val" style="color:#a78bfa;"><?=$newThisMonth?></div><div class="kpi-lbl">Nouveaux ce mois</div></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">

        <!-- Doughnut -->
        <div class="chart-box">
            <h3><i class="fas fa-chart-pie"></i> Répartition des clients</h3>
            <canvas id="doughnutChart" height="220"></canvas>
        </div>

        <!-- Progress bars -->
        <div class="chart-box">
            <h3><i class="fas fa-chart-bar"></i> Indicateurs clés</h3>
            <div class="progress-item">
                <div class="progress-label"><span>Taux d'activité</span><span style="color:#00e676;"><?=$tauxActivite?>%</span></div>
                <div class="progress-track"><div class="progress-fill" data-width="<?=$tauxActivite?>" style="background:linear-gradient(90deg,#00e676,#059669);"></div></div>
            </div>
            <div class="progress-item">
                <div class="progress-label"><span>Taux de blocage</span><span style="color:#ff5252;"><?=100-$tauxActivite?>%</span></div>
                <div class="progress-track"><div class="progress-fill" data-width="<?=100-$tauxActivite?>" style="background:linear-gradient(90deg,#ff5252,#dc2626);"></div></div>
            </div>
            <div class="progress-item">
                <div class="progress-label"><span>Objectif mensuel (20 clients)</span><span style="color:#a78bfa;"><?=min(100,round($newThisMonth/20*100))?>%</span></div>
                <div class="progress-track"><div class="progress-fill" data-width="<?=min(100,round($newThisMonth/20*100))?>" style="background:linear-gradient(90deg,#a78bfa,#7c3aed);"></div></div>
            </div>
            <div style="margin-top:24px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.06);">
                <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                    <span style="color:#888;font-size:0.78rem;">Actifs</span>
                    <span style="color:#00e676;font-weight:700;"><?=$actifs?> clients</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                    <span style="color:#888;font-size:0.78rem;">Bloqués</span>
                    <span style="color:#ff5252;font-weight:700;"><?=$bloques?> clients</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#888;font-size:0.78rem;">Nouveaux ce mois</span>
                    <span style="color:#a78bfa;font-weight:700;"><?=$newThisMonth?> clients</span>
                </div>
            </div>
        </div>

        <!-- Line chart: inscriptions par mois -->
        <div class="chart-box chart-full">
            <h3><i class="fas fa-chart-line"></i> Inscriptions — 6 derniers mois</h3>
            <canvas id="lineChart" height="100"></canvas>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="quick-row">
        <a href="/projet_final/controllers/AdminController.php?action=listUsers" class="qa-btn"><i class="fas fa-list" style="color:#00E5FF;"></i> Voir tous les clients</a>
        <a href="/projet_final/controllers/AdminController.php?action=showAddUser" class="qa-btn"><i class="fas fa-user-plus" style="color:#00e676;"></i> Ajouter un client</a>
        <a href="/projet_final/controllers/AIController.php?action=showAssistant" class="qa-btn"><i class="fas fa-robot" style="color:#a78bfa;"></i> Ouvrir AI Helper</a>
        <a href="/projet_final/controllers/AdminController.php?action=listUsers" class="qa-btn"><i class="fas fa-download" style="color:#ffc107;"></i> Exporter clients</a>
    </div>

</main>

<script>
Chart.defaults.color = '#888';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// ── Doughnut ──────────────────────────────────────────────────────────────
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Actifs', 'Bloqués'],
        datasets: [{
            data: [<?=$actifs?>, <?=$bloques?>],
            backgroundColor: ['rgba(0,230,118,0.85)', 'rgba(255,82,82,0.85)'],
            borderColor: ['#00e676', '#ff5252'],
            borderWidth: 2,
            hoverOffset: 8,
        }]
    },
    options: {
        cutout: '70%',
        plugins: {
            legend: { position:'bottom', labels:{ padding:20, font:{size:12} } },
            tooltip: { callbacks: { label: function(c) { return ' ' + c.label + ' : ' + c.parsed + ' clients'; } } }
        },
        animation: { animateRotate:true, duration:1200 }
    }
});

// ── Line chart ────────────────────────────────────────────────────────────
var months = <?=json_encode(array_column($byMonth,'mois'))?>;
var totals = <?=json_encode(array_map('intval', array_column($byMonth,'total')))?>;

// Fill empty months if needed
if (months.length === 0) { months = ['Ce mois']; totals = [<?=$newThisMonth?>]; }

new Chart(document.getElementById('lineChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Nouveaux clients',
            data: totals,
            backgroundColor: 'rgba(0,229,255,0.2)',
            borderColor: '#00E5FF',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }, {
            type: 'line',
            label: 'Tendance',
            data: totals,
            borderColor: '#a78bfa',
            borderWidth: 2,
            pointBackgroundColor: '#a78bfa',
            pointRadius: 5,
            tension: 0.4,
            fill: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position:'top' },
            tooltip: { mode:'index', intersect:false }
        },
        scales: {
            y: { beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'rgba(255,255,255,0.05)' } },
            x: { grid:{ color:'rgba(255,255,255,0.05)' } }
        },
        animation: { duration:1200 }
    }
});

// ── Animate progress bars ─────────────────────────────────────────────────
setTimeout(function() {
    document.querySelectorAll('.progress-fill').forEach(function(el) {
        el.style.width = el.getAttribute('data-width') + '%';
    });
}, 300);
</script>
<?php require_once __DIR__ . "/darkmode_back.php"; ?>
</body>
</html>
