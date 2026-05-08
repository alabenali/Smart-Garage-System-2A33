<?php $pageTitle = 'Mes vehicules'; $action = 'showVehicles'; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
    .vehicles-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
    .vehicles-head-actions{display:flex;gap:.65rem;flex-wrap:wrap}
    .vehicle-card.sg-client-vehicle{display:flex;flex-direction:column;gap:1rem}
    .health-summary{border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:.8rem;background:var(--bg-secondary)}
    .health-top{display:flex;justify-content:space-between;align-items:center;gap:.8rem;margin-bottom:.55rem}
    .health-label{display:inline-flex;align-items:center;gap:.35rem;font-weight:800;color:var(--accent-secondary)}
    .health-score{font-weight:900;font-size:1.1rem}
    .health-bar{height:9px;background:#FDECEA;border-radius:999px;overflow:hidden}
    .health-bar span{display:block;height:100%;border-radius:999px}
    .health-good .health-score,.health-good .health-label{color:#2E7D32}
    .health-ok .health-score,.health-ok .health-label{color:#1A2E44}
    .health-watch .health-score,.health-watch .health-label{color:#A66D03}
    .health-risk .health-score,.health-risk .health-label{color:#C62828}
    .health-good .health-bar span{background:#2E7D32}
    .health-ok .health-bar span{background:#1A2E44}
    .health-watch .health-bar span{background:#A66D03}
    .health-risk .health-bar span{background:#C62828}
    .vehicle-card-actions{display:flex;gap:.55rem;flex-wrap:wrap;margin-top:auto}
    .vehicle-card-actions .btn-sg{justify-content:center}
    .vehicle-kpi-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem}
    .vehicle-kpi{background:#fff;border:1px solid var(--border-color);border-radius:8px;padding:.65rem}
    .vehicle-kpi span{display:block;font-size:.72rem;color:var(--text-secondary);font-weight:700;margin-bottom:.15rem}
    .vehicle-kpi strong{color:var(--text-primary);font-size:.9rem}
    @media(max-width:640px){.vehicles-head-actions,.vehicle-card-actions{width:100%}.vehicles-head-actions .btn-sg,.vehicle-card-actions .btn-sg{width:100%}.vehicle-kpi-row{grid-template-columns:1fr}}
</style>

<div class="vehicles-head">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Mes v&eacute;hicules</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo count($vehicles); ?> v&eacute;hicule<?php echo count($vehicles) !== 1 ? 's' : ''; ?> rattach&eacute;<?php echo count($vehicles) !== 1 ? 's' : ''; ?> &agrave; votre compte
        </p>
    </div>
    <div class="vehicles-head-actions">
        <a href="index.php?action=frontCalendar" class="btn-sg btn-sg-outline">
            <i class="bi bi-calendar-plus"></i> Prendre un RDV
        </a>
        <a href="index.php?action=addVehicle" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter
        </a>
    </div>
</div>

<?php if (!empty($_GET['error'])): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars((string) $_GET['error']); ?></div>
<?php endif; ?>

<?php if (empty($vehicles)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon"><i class="bi bi-car-front"></i></div>
        <h3>Aucun v&eacute;hicule trouv&eacute;</h3>
        <p>Ajoutez votre premier v&eacute;hicule pour suivre son historique, ses rendez-vous et son &eacute;tat de sant&eacute;.</p>
        <a href="index.php?action=addVehicle" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter un v&eacute;hicule
        </a>
    </div>
<?php else: ?>
    <div class="vehicle-grid">
        <?php foreach ($vehicles as $v): ?>
            <?php
                $colorMap = [
                    'blanc' => '#f5f5f5', 'noir' => '#1a1a1a', 'gris' => '#9ca3af',
                    'rouge' => '#ef4444', 'bleu' => '#3b82f6', 'vert' => '#22c55e',
                    'jaune' => '#f59e0b', 'orange' => '#f97316', 'marron' => '#92400e',
                    'beige' => '#d4a574', 'violet' => '#8b5cf6', 'rose' => '#ec4899',
                ];
                $dotColor = $colorMap[strtolower((string) $v['couleur'])] ?? '#6b7280';
                $fuelClass = strtolower((string) $v['carburant']);
                $health = $vehicleHealthById[(int) $v['id']] ?? [
                    'score' => 0,
                    'label' => 'Non calcule',
                    'class' => 'health-watch',
                    'total_rdv' => 0,
                    'urgent_total' => 0,
                    'message' => 'Historique insuffisant.',
                ];
            ?>
            <div class="vehicle-card sg-client-vehicle">
                <div class="vc-header">
                    <div>
                        <div class="vc-brand"><?php echo htmlspecialchars((string) $v['marque']); ?></div>
                        <div class="vc-model"><?php echo htmlspecialchars((string) $v['modele']); ?></div>
                    </div>
                    <span class="vc-plate"><?php echo formatPlate($v['immatriculation'] ?? ''); ?></span>
                </div>

                <div class="health-summary <?php echo htmlspecialchars((string) $health['class']); ?>">
                    <div class="health-top">
                        <span class="health-label"><i class="bi bi-heart-pulse"></i> Sant&eacute; : <?php echo htmlspecialchars((string) $health['label']); ?></span>
                        <span class="health-score"><?php echo (int) $health['score']; ?>/100</span>
                    </div>
                    <div class="health-bar"><span style="width:<?php echo (int) $health['score']; ?>%;"></span></div>
                </div>

                <div class="vehicle-kpi-row">
                    <div class="vehicle-kpi">
                        <span>Ann&eacute;e</span>
                        <strong><?php echo (int) $v['annee']; ?></strong>
                    </div>
                    <div class="vehicle-kpi">
                        <span>Kilom&eacute;trage</span>
                        <strong><?php echo number_format((int) $v['kilometrage'], 0, ',', ' '); ?> km</strong>
                    </div>
                    <div class="vehicle-kpi">
                        <span>RDV</span>
                        <strong><?php echo (int) $health['total_rdv']; ?></strong>
                    </div>
                </div>

                <div class="vc-details">
                    <div class="vc-detail">
                        <span class="vc-detail-label">Couleur</span>
                        <span class="vc-detail-value badge-color">
                            <span class="color-dot" style="background:<?php echo $dotColor; ?>;"></span>
                            <?php echo htmlspecialchars((string) $v['couleur']); ?>
                        </span>
                    </div>
                    <div class="vc-detail">
                        <span class="vc-detail-label">Carburant</span>
                        <span class="vc-detail-value">
                            <span class="badge-fuel <?php echo htmlspecialchars($fuelClass); ?>"><?php echo htmlspecialchars((string) $v['carburant']); ?></span>
                        </span>
                    </div>
                </div>

                <div class="vehicle-card-actions">
                    <a href="index.php?action=vehicleHealth&id=<?php echo (int) $v['id']; ?>" class="btn-sg btn-sg-primary btn-sg-sm">
                        <i class="bi bi-heart-pulse"></i> Voir la sant&eacute;
                    </a>
                    <a href="index.php?action=frontCalendar&id_vehicule=<?php echo (int) $v['id']; ?>" class="btn-sg btn-sg-outline btn-sg-sm">
                        <i class="bi bi-calendar-plus"></i> RDV
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
