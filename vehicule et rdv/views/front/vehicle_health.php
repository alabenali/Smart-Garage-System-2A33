<?php $pageTitle = 'Sante du vehicule'; $action = 'vehicleHealth'; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
    .health-page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
    .health-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:1.25rem;align-items:start}
    .health-main-card,.health-side-card{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    .health-main-inner{padding:1.4rem}
    .health-score-panel{display:grid;grid-template-columns:170px minmax(0,1fr);gap:1.25rem;align-items:center}
    .health-ring{width:160px;height:160px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(var(--health-color) calc(var(--score)*1%), #FDECEA 0);position:relative}
    .health-ring::after{content:'';position:absolute;inset:13px;border-radius:50%;background:#fff}
    .health-ring-content{position:relative;z-index:1;text-align:center}
    .health-ring-score{display:block;font-size:2rem;font-weight:900;color:var(--accent-secondary);line-height:1}
    .health-ring-label{font-size:.78rem;font-weight:800;color:var(--text-secondary)}
    .health-title-row{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;margin-bottom:.5rem}
    .health-title-row h2{margin:0;color:var(--accent-secondary)}
    .health-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.35rem .7rem;font-weight:800;font-size:.82rem}
    .health-good{--health-color:#2E7D32}.health-good .health-badge{background:#E8F5E9;color:#2E7D32}
    .health-ok{--health-color:#1A2E44}.health-ok .health-badge{background:#E7EEF7;color:#1A2E44}
    .health-watch{--health-color:#A66D03}.health-watch .health-badge{background:#FFF3CD;color:#A66D03}
    .health-risk{--health-color:#C62828}.health-risk .health-badge{background:#FDECEA;color:#C62828}
    .health-message{color:var(--text-secondary);font-size:1rem;line-height:1.55;margin:0}
    .health-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-top:1.25rem}
    .health-kpi{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:.85rem}
    .health-kpi span{display:block;font-size:.76rem;color:var(--text-secondary);font-weight:800;margin-bottom:.2rem}
    .health-kpi strong{font-size:1.05rem;color:var(--text-primary)}
    .health-section{border-top:1px solid var(--border-color);padding:1.15rem 1.4rem}
    .health-section h3{font-size:1rem;margin:0 0 .8rem;color:var(--accent-secondary)}
    .recommendation-list{display:grid;gap:.65rem;margin:0;padding:0;list-style:none}
    .recommendation-list li{display:flex;gap:.6rem;align-items:flex-start;color:var(--text-primary);background:var(--bg-secondary);border-radius:8px;padding:.75rem}
    .recommendation-list i{color:var(--accent);margin-top:.1rem}
    .vehicle-facts{padding:1rem;display:grid;gap:.7rem}
    .vehicle-fact{display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--border-color);padding-bottom:.55rem}
    .vehicle-fact:last-child{border-bottom:0;padding-bottom:0}
    .vehicle-fact span{color:var(--text-secondary);font-weight:700}
    .vehicle-fact strong{text-align:right;color:var(--text-primary)}
    .history-list{display:grid;gap:.75rem}
    .history-item{border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:.8rem;background:#fff}
    .history-item-head{display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start}
    .history-item h4{font-size:.95rem;margin:0;color:var(--accent-secondary)}
    .history-item p{margin:.45rem 0 0;color:var(--text-secondary);font-size:.86rem}
    @media(max-width:1100px){.health-layout,.health-score-panel{grid-template-columns:1fr}.health-ring{margin:auto}.health-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:560px){.health-kpis{grid-template-columns:1fr}.health-page-head .btn-group-actions,.health-page-head .btn-sg{width:100%;justify-content:center}.history-item-head{display:block}.history-item-head .status-badge{margin-top:.5rem}}
</style>

<?php
$healthClass = (string) ($health['class'] ?? 'health-watch');
$score = max(0, min(100, (int) ($health['score'] ?? 0)));
$vehicleLabel = trim((string) (($vehicle['marque'] ?? '') . ' ' . ($vehicle['modele'] ?? '')));
?>

<div class="health-page-head">
    <div>
        <h1 class="page-title">Sant&eacute; de la voiture</h1>
        <p class="page-subtitle"><?php echo htmlspecialchars($vehicleLabel); ?> - <?php echo formatPlate($vehicle['immatriculation'] ?? ''); ?></p>
    </div>
    <div class="btn-group-actions">
        <a href="index.php?action=showVehicles" class="btn-sg btn-sg-outline"><i class="bi bi-arrow-left"></i> Mes v&eacute;hicules</a>
        <a href="index.php?action=frontCalendar&id_vehicule=<?php echo (int) $vehicle['id']; ?>" class="btn-sg btn-sg-primary"><i class="bi bi-calendar-plus"></i> Prendre RDV</a>
    </div>
</div>

<div class="health-layout">
    <section class="health-main-card <?php echo htmlspecialchars($healthClass); ?>">
        <div class="health-main-inner">
            <div class="health-score-panel">
                <div class="health-ring" style="--score:<?php echo $score; ?>;">
                    <div class="health-ring-content">
                        <span class="health-ring-score"><?php echo $score; ?></span>
                        <span class="health-ring-label">sur 100</span>
                    </div>
                </div>
                <div>
                    <div class="health-title-row">
                        <h2>Etat estim&eacute; : <?php echo htmlspecialchars((string) ($health['label'] ?? 'Non calcule')); ?></h2>
                        <span class="health-badge"><i class="bi bi-heart-pulse"></i> <?php echo htmlspecialchars((string) ($health['label'] ?? 'Etat')); ?></span>
                    </div>
                    <p class="health-message"><?php echo htmlspecialchars((string) ($health['message'] ?? 'Analyse indisponible.')); ?></p>
                </div>
            </div>

            <div class="health-kpis">
                <div class="health-kpi"><span>Kilometrage</span><strong><?php echo number_format((int) ($vehicle['kilometrage'] ?? 0), 0, ',', ' '); ?> km</strong></div>
                <div class="health-kpi"><span>Age</span><strong><?php echo (int) ($health['age'] ?? 0); ?> ans</strong></div>
                <div class="health-kpi"><span>Interventions</span><strong><?php echo (int) ($health['total_rdv'] ?? 0); ?></strong></div>
                <div class="health-kpi"><span>Urgences</span><strong><?php echo (int) ($health['urgent_total'] ?? 0); ?></strong></div>
            </div>
        </div>

        <div class="health-section">
            <h3><i class="bi bi-tools me-1"></i> Conseils personnalises</h3>
            <ul class="recommendation-list">
                <?php foreach (($health['recommendations'] ?? []) as $recommendation): ?>
                    <li><i class="bi bi-check-circle-fill"></i><span><?php echo htmlspecialchars((string) $recommendation); ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="health-section">
            <h3><i class="bi bi-clock-history me-1"></i> Derniers rendez-vous de cette voiture</h3>
            <?php if (!empty($history)): ?>
                <div class="history-list">
                    <?php foreach (array_slice($history, 0, 5) as $row): ?>
                        <?php $date = !empty($row['date_heure']) ? strtotime((string) $row['date_heure']) : false; ?>
                        <article class="history-item">
                            <div class="history-item-head">
                                <div>
                                    <h4><?php echo htmlspecialchars((string) ($row['type_intervention'] ?? 'Intervention')); ?></h4>
                                    <p><i class="bi bi-calendar3"></i> <?php echo $date ? htmlspecialchars(date('d/m/Y H:i', $date)) : 'Date non renseignee'; ?></p>
                                </div>
                                <span class="status-badge <?php echo ((int) ($row['urgence_score'] ?? 0) >= 7) ? 'status-annule' : 'status-termine'; ?>">
                                    Urgence <?php echo (int) ($row['urgence_score'] ?? 0); ?>/10
                                </span>
                            </div>
                            <?php if (!empty($row['description_panne'])): ?>
                                <p><?php echo htmlspecialchars((string) $row['description_panne']); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:var(--text-secondary);margin:0;">Aucun historique atelier pour cette voiture.</p>
            <?php endif; ?>
        </div>
    </section>

    <aside class="health-side-card">
        <div class="health-section" style="border-top:0;">
            <h3><i class="bi bi-car-front me-1"></i> Fiche voiture</h3>
        </div>
        <div class="vehicle-facts">
            <div class="vehicle-fact"><span>Marque</span><strong><?php echo htmlspecialchars((string) $vehicle['marque']); ?></strong></div>
            <div class="vehicle-fact"><span>Modele</span><strong><?php echo htmlspecialchars((string) $vehicle['modele']); ?></strong></div>
            <div class="vehicle-fact"><span>Immatriculation</span><strong><?php echo formatPlate($vehicle['immatriculation'] ?? ''); ?></strong></div>
            <div class="vehicle-fact"><span>Annee</span><strong><?php echo (int) $vehicle['annee']; ?></strong></div>
            <div class="vehicle-fact"><span>Carburant</span><strong><?php echo htmlspecialchars((string) $vehicle['carburant']); ?></strong></div>
            <div class="vehicle-fact"><span>Couleur</span><strong><?php echo htmlspecialchars((string) $vehicle['couleur']); ?></strong></div>
            <div class="vehicle-fact"><span>Ajoutee le</span><strong><?php echo !empty($vehicle['date_ajout']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $vehicle['date_ajout']))) : '-'; ?></strong></div>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
