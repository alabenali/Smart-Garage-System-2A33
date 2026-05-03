<?php
// ============================================
// Smart Garage – Page Admin : Génération manuelle du rapport
// ============================================

require_once __DIR__ . '/../cron/weekly_report.php';

$result = null;
$generating = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $generating = true;
    $result = generateWeeklyReport();
}

$pageTitle = 'Rapport Hebdomadaire';
$action = 'weeklyReport';

// Lire les derniers logs
$logFile = __DIR__ . '/../logs/weekly_report.log';
$lastLogs = [];
if (file_exists($logFile)) {
    $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lastLogs = array_slice($allLines, -10);
    $lastLogs = array_reverse($lastLogs);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> – Smart Garage Admin</title>
    <meta name="description" content="Smart Garage – Génération manuelle du rapport hebdomadaire.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../views/css/style.css">
    <style>
        .report-card {
            background: var(--bg-card, #1e2530);
            border: 1px solid var(--border, rgba(255,255,255,0.06));
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .report-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .report-header h1 {
            color: var(--text-primary, #fff);
            font-size: 1.8rem;
            font-weight: 700;
        }
        .report-header p {
            color: var(--text-secondary, #8899a6);
            font-size: 0.95rem;
        }
        .btn-generate {
            background: linear-gradient(135deg, #2980b9, #3498db);
            border: none;
            color: #fff;
            padding: 14px 36px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-generate:hover {
            background: linear-gradient(135deg, #2471a3, #2e86c1);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(41,128,185,0.35);
            color: #fff;
        }
        .btn-generate:disabled {
            opacity: 0.6;
            cursor: wait;
        }
        .result-box {
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1.5rem;
        }
        .result-success {
            background: rgba(39,174,96,0.12);
            border: 1px solid rgba(39,174,96,0.3);
        }
        .result-fail {
            background: rgba(231,76,60,0.12);
            border: 1px solid rgba(231,76,60,0.3);
        }
        .log-entry {
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            color: var(--text-secondary, #8899a6);
            padding: 6px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .log-entry:last-child { border-bottom: none; }
        .step-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            color: var(--text-secondary, #8899a6);
            font-size: 0.9rem;
        }
        .step-icon { font-size: 1.1rem; }
        .step-icon.ok { color: #27ae60; }
        .step-icon.fail { color: #e74c3c; }
        .spinner-border-sm { width: 1rem; height: 1rem; }
        .back-link {
            color: var(--text-secondary, #8899a6);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--accent, #3498db); }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <a href="../index.php?action=dashboard" class="brand-stack">
            <img src="../views/images/logo.png" alt="Smart Garage Logo">
            <span>
                <span class="brand-title">Smart Garage</span>
                <span class="brand-subtitle">Admin</span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a href="../index.php?action=dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="../index.php?action=manageVehicles"><i class="bi bi-car-front"></i> Véhicules</a>
            <a href="../index.php?action=backCalendar"><i class="bi bi-calendar-week"></i> Calendrier RDV</a>
            <a href="../index.php?action=backRdvList"><i class="bi bi-card-checklist"></i> Liste RDV</a>
            <a href="test_rapport.php" class="active"><i class="bi bi-file-earmark-bar-graph"></i> Rapport</a>
            <a href="../index.php?action=showVehicles"><i class="bi bi-box-arrow-up-right"></i> FrontOffice</a>
        </nav>
    </aside>

    <main class="app-main">
        <div class="page-wrapper">

            <a href="../index.php?action=dashboard" class="back-link">
                <i class="bi bi-arrow-left"></i> Retour au Dashboard
            </a>

            <div class="report-card">
                <div class="report-header">
                    <h1><i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Rapport Hebdomadaire</h1>
                    <p>Générez manuellement le rapport de la semaine précédente avec analyse IA, PDF et envoi email.</p>
                </div>

                <div style="text-align:center;">
                    <form method="POST" id="reportForm">
                        <input type="hidden" name="generate_report" value="1">
                        <button type="submit" class="btn-generate" id="btnGenerate">
                            <i class="bi bi-bar-chart-line-fill"></i>
                            📊 Générer le rapport hebdomadaire
                        </button>
                    </form>
                </div>

                <?php if ($result !== null): ?>
                    <div class="result-box <?php echo $result['success'] ? 'result-success' : 'result-fail'; ?>">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                            <?php if ($result['success']): ?>
                                <i class="bi bi-check-circle-fill" style="font-size:1.5rem;color:#27ae60;"></i>
                                <strong style="color:#27ae60;font-size:1.05rem;">Succès !</strong>
                            <?php else: ?>
                                <i class="bi bi-exclamation-triangle-fill" style="font-size:1.5rem;color:#e74c3c;"></i>
                                <strong style="color:#e74c3c;font-size:1.05rem;">Attention</strong>
                            <?php endif; ?>
                        </div>
                        <p style="margin:0 0 15px;color:var(--text-primary,#fff);"><?php echo htmlspecialchars($result['message']); ?></p>

                        <h4 style="color:var(--text-primary,#fff);font-size:0.95rem;margin-bottom:10px;">
                            <i class="bi bi-list-check me-1"></i> Étapes d'exécution
                        </h4>
                        <?php foreach ($result['logs'] as $log): ?>
                            <div class="step-item">
                                <?php
                                $isOk = strpos($log, 'FAIL') === false && strpos($log, 'FALLBACK') === false;
                                ?>
                                <span class="step-icon <?php echo $isOk ? 'ok' : 'fail'; ?>">
                                    <i class="bi <?php echo $isOk ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                                </span>
                                <?php echo htmlspecialchars($log); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($lastLogs)): ?>
            <div class="report-card">
                <h3 style="color:var(--text-primary,#fff);font-size:1.1rem;margin-bottom:1rem;">
                    <i class="bi bi-clock-history me-2"></i>Historique des exécutions
                </h3>
                <?php foreach ($lastLogs as $logEntry): ?>
                    <div class="log-entry"><?php echo htmlspecialchars($logEntry); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
document.getElementById('reportForm').addEventListener('submit', function() {
    var btn = document.getElementById('btnGenerate');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Génération en cours...';
});
</script>
</body>
</html>
