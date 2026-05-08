<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /integration/client/controllers/UserController.php?action=showLogin');
    exit;
}

$pageTitle = 'Mes rendez-vous';
$action = 'clientRendezvous';
$rdvPageData = $rdvPageData ?? [];
$upcoming = $rdvPageData['upcoming'] ?? [];
$past = $rdvPageData['past'] ?? [];
$stats = $rdvPageData['stats'] ?? ['total' => 0, 'upcoming' => 0, 'past' => 0];
$loyalty = $rdvPageData['loyalty'] ?? [
    'points' => 0,
    'progress_pct' => 0,
    'missing' => 200,
    'goal' => 200,
    'unlocked' => false,
    'tier' => 'Bronze',
    'history' => [],
];

function sg_status_class(string $status): string {
    $normalized = strtolower(trim($status));
    $normalized = str_replace(['é', 'è', 'ê', 'ë', 'à', 'â', 'ù', 'û', 'ç'], ['e', 'e', 'e', 'e', 'a', 'a', 'u', 'u', 'c'], $normalized);
    if (str_contains($normalized, 'confirme')) return 'status-confirme';
    if (str_contains($normalized, 'cours')) return 'status-en-cours';
    if (str_contains($normalized, 'termine')) return 'status-termine';
    if (str_contains($normalized, 'annule')) return 'status-annule';
    return 'status-en-attente';
}

function sg_rdv_card(array $rdv): void {
    $date = !empty($rdv['date_heure']) ? strtotime((string) $rdv['date_heure']) : false;
    $vehicle = trim((string) ($rdv['vehicle_label'] ?? ''));
    if ($vehicle === '') {
        $vehicle = !empty($rdv['id_vehicle']) ? 'Vehicule #' . (int) $rdv['id_vehicle'] : 'Vehicule non renseigne';
    }
    $status = (string) ($rdv['statut'] ?? 'En attente');
    ?>
    <article class="rdv-timeline-card">
        <div class="rdv-date-box">
            <span><?php echo $date ? htmlspecialchars(date('d', $date)) : '--'; ?></span>
            <small><?php echo $date ? htmlspecialchars(date('M Y', $date)) : '-'; ?></small>
        </div>
        <div class="rdv-card-body">
            <div class="rdv-card-head">
                <div>
                    <h3><?php echo htmlspecialchars((string) ($rdv['type_intervention'] ?? 'Intervention')); ?></h3>
                    <p><i class="bi bi-clock"></i> <?php echo $date ? htmlspecialchars(date('d/m/Y H:i', $date)) : 'Date non renseignee'; ?></p>
                </div>
                <span class="status-badge <?php echo sg_status_class($status); ?>"><?php echo htmlspecialchars($status); ?></span>
            </div>
            <div class="rdv-meta-grid">
                <span><i class="bi bi-car-front"></i> <?php echo htmlspecialchars($vehicle); ?></span>
                <span><i class="bi bi-speedometer2"></i> Urgence <?php echo (int) ($rdv['urgence_score'] ?? 0); ?>/10</span>
                <span><i class="bi bi-tag"></i> Remise eco <?php echo number_format((float) ($rdv['remise_eco_appliquee'] ?? 0), 0); ?>%</span>
            </div>
            <?php if (!empty($rdv['description_panne'])): ?>
                <p class="rdv-description"><?php echo htmlspecialchars((string) $rdv['description_panne']); ?></p>
            <?php endif; ?>
        </div>
    </article>
    <?php
}
?>

<?php require __DIR__ . '/layout_header.php'; ?>

<style>
    .client-rdv-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
    .client-rdv-actions{display:flex;gap:.65rem;flex-wrap:wrap}
    .loyalty-hero{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius);padding:1.25rem;margin-bottom:1.5rem;box-shadow:var(--shadow);display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:1.25rem;align-items:center}
    .loyalty-title{display:flex;align-items:center;gap:.65rem;margin-bottom:.5rem}
    .loyalty-title h2{font-size:1.15rem;margin:0;color:var(--accent-secondary)}
    .loyalty-points{font-size:2rem;font-weight:800;color:var(--accent);line-height:1}
    .loyalty-note{margin:.7rem 0 0;color:var(--text-secondary)}
    .loyalty-note strong{color:var(--accent-secondary)}
    .loyalty-progress-wrap{margin-top:1rem}
    .loyalty-progress-top{display:flex;justify-content:space-between;gap:1rem;font-size:.86rem;font-weight:700;color:var(--text-secondary);margin-bottom:.45rem}
    .loyalty-progress{height:12px;border-radius:999px;background:#FDECEA;overflow:hidden}
    .loyalty-progress-bar{height:100%;width:0;background:var(--accent);border-radius:999px;transition:width .5s ease}
    .loyalty-tier-card{background:var(--info-bg);border-radius:var(--radius-sm);padding:1rem;color:var(--accent-secondary)}
    .loyalty-tier-card span{display:block;font-size:.8rem;font-weight:700;color:var(--text-secondary);margin-bottom:.25rem}
    .loyalty-tier-card strong{font-size:1.35rem}
    .rdv-tabs-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:1.25rem}
    .rdv-section{background:#fff;border:1px solid var(--border-color);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    .rdv-section-header{padding:1rem 1.25rem;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:space-between;gap:1rem}
    .rdv-section-header h2{font-size:1rem;margin:0;color:var(--accent-secondary)}
    .rdv-count{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:28px;border-radius:999px;background:var(--info-bg);color:var(--accent-secondary);font-weight:800;font-size:.82rem}
    .rdv-list{padding:1rem;display:flex;flex-direction:column;gap:.85rem}
    .rdv-timeline-card{display:flex;gap:.85rem;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:.85rem;background:var(--bg-secondary);transition:var(--transition)}
    .rdv-timeline-card:hover{border-color:var(--accent);transform:translateY(-1px);box-shadow:var(--shadow)}
    .rdv-date-box{width:72px;min-width:72px;border-radius:10px;background:#fff;border:1px solid var(--border-color);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:.55rem}
    .rdv-date-box span{font-size:1.45rem;font-weight:800;color:var(--accent)}
    .rdv-date-box small{font-size:.72rem;color:var(--text-secondary);text-transform:uppercase}
    .rdv-card-body{min-width:0;flex:1}
    .rdv-card-head{display:flex;justify-content:space-between;gap:.8rem;align-items:flex-start}
    .rdv-card-head h3{font-size:.98rem;margin:0 0 .25rem;color:var(--accent-secondary)}
    .rdv-card-head p{margin:0;color:var(--text-secondary);font-size:.84rem}
    .rdv-meta-grid{display:flex;flex-wrap:wrap;gap:.45rem .8rem;margin-top:.7rem;color:var(--text-secondary);font-size:.82rem}
    .rdv-description{margin:.65rem 0 0;color:var(--text-primary);font-size:.88rem;line-height:1.5}
    .rdv-empty{padding:2rem 1rem;text-align:center;color:var(--text-secondary)}
    .loyalty-history-mini{margin-top:1rem;border-top:1px solid var(--border-color);padding-top:.85rem}
    .loyalty-history-mini h3{font-size:.95rem;margin:0 0 .65rem;color:var(--accent-secondary)}
    .loyalty-history-row{display:flex;justify-content:space-between;gap:.75rem;padding:.45rem 0;border-bottom:1px dashed var(--border-color);font-size:.84rem;color:var(--text-secondary)}
    .loyalty-history-row strong{color:var(--success);white-space:nowrap}
    @media (max-width:1100px){.loyalty-hero,.rdv-tabs-layout{grid-template-columns:1fr}}
    @media (max-width:640px){.rdv-timeline-card,.rdv-card-head{display:block}.rdv-date-box{width:100%;margin-bottom:.75rem}.rdv-card-head .status-badge{margin-top:.6rem}.client-rdv-actions .btn-sg{width:100%;justify-content:center}}
</style>

<div class="client-rdv-head">
    <div>
        <h1 class="page-title">Mes rendez-vous</h1>
        <p class="page-subtitle">Suivez vos prochains passages au garage, votre historique et vos points fidelite.</p>
    </div>
    <div class="client-rdv-actions">
        <a class="btn-sg btn-sg-primary" href="/integration/vehicule%20et%20rdv/index.php?action=frontCalendar"><i class="bi bi-calendar-plus"></i> Prendre un RDV</a>
        <a class="btn-sg btn-sg-outline" href="/integration/vehicule%20et%20rdv/index.php?action=showVehicles"><i class="bi bi-car-front"></i> Mes vehicules</a>
    </div>
</div>

<?php if (!empty($rdvPageData['load_error'])): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars((string) $rdvPageData['load_error']); ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-calendar2-week"></i></div><div class="stat-value"><?php echo (int) $stats['total']; ?></div><div class="stat-label">RDV total</div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-calendar-check"></i></div><div class="stat-value"><?php echo (int) $stats['upcoming']; ?></div><div class="stat-label">RDV futurs</div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="bi bi-clock-history"></i></div><div class="stat-value"><?php echo (int) $stats['past']; ?></div><div class="stat-label">RDV passes</div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-stars"></i></div><div class="stat-value"><?php echo (int) $loyalty['points']; ?></div><div class="stat-label">Points fidelite</div></div>
</div>

<section class="loyalty-hero">
    <div>
        <div class="loyalty-title">
            <i class="bi bi-stars" style="color:var(--accent);font-size:1.35rem;"></i>
            <h2>Votre progression fidelite</h2>
        </div>
        <div class="loyalty-points"><?php echo number_format((int) $loyalty['points'], 0, ',', ' '); ?> pts</div>
        <p class="loyalty-note">
            <?php if (!empty($loyalty['unlocked'])): ?>
                <strong>Vidange gratuite debloquee !</strong> Vous avez atteint les 200 points.
            <?php else: ?>
                Petite note : lorsque vous atteignez <strong>200 points</strong>, vous gagnez une <strong>vidange gratuite</strong>.
                Encore <?php echo (int) $loyalty['missing']; ?> points.
            <?php endif; ?>
        </p>
        <div class="loyalty-progress-wrap">
            <div class="loyalty-progress-top">
                <span><?php echo (int) $loyalty['points']; ?>/<?php echo (int) $loyalty['goal']; ?> points</span>
                <span><?php echo (int) $loyalty['progress_pct']; ?>%</span>
            </div>
            <div class="loyalty-progress"><div class="loyalty-progress-bar" style="width:<?php echo (int) $loyalty['progress_pct']; ?>%;"></div></div>
        </div>
        <?php if (!empty($loyalty['history'])): ?>
            <div class="loyalty-history-mini">
                <h3>Derniers mouvements</h3>
                <?php foreach ($loyalty['history'] as $item): ?>
                    <?php $isGain = in_array((string) ($item['type'] ?? ''), ['gain', 'bonus'], true); ?>
                    <div class="loyalty-history-row">
                        <span><?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $item['date_transaction'])) . ' - ' . (string) ($item['description'] ?? '-')); ?></span>
                        <strong style="color:<?php echo $isGain ? 'var(--success)' : 'var(--danger)'; ?>"><?php echo ($isGain ? '+' : '-') . (int) ($item['points'] ?? 0); ?> pts</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="loyalty-tier-card">
        <span>Palier actuel</span>
        <strong><?php echo htmlspecialchars((string) $loyalty['tier']); ?></strong>
        <p style="margin:.65rem 0 0;color:var(--text-secondary);">Les points sont ajoutes apres les interventions terminees.</p>
    </div>
</section>

<div class="rdv-tabs-layout">
    <section class="rdv-section">
        <div class="rdv-section-header">
            <h2><i class="bi bi-calendar-check me-1"></i> Rendez-vous futurs</h2>
            <span class="rdv-count"><?php echo count($upcoming); ?></span>
        </div>
        <div class="rdv-list">
            <?php if (!empty($upcoming)): ?>
                <?php foreach ($upcoming as $rdv): ?>
                    <?php sg_rdv_card($rdv); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rdv-empty">Aucun rendez-vous futur. Vous pouvez prendre un nouveau RDV a tout moment.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="rdv-section">
        <div class="rdv-section-header">
            <h2><i class="bi bi-clock-history me-1"></i> Historique des RDV</h2>
            <span class="rdv-count"><?php echo count($past); ?></span>
        </div>
        <div class="rdv-list">
            <?php if (!empty($past)): ?>
                <?php foreach ($past as $rdv): ?>
                    <?php sg_rdv_card($rdv); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rdv-empty">Aucun rendez-vous passe pour le moment.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
