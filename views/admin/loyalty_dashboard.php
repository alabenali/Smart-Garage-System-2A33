<?php
$pageTitle = 'Fidélité';
$action = 'adminLoyalty';
require __DIR__ . '/../back/layout_header.php';

$stats = $stats ?? [];
$membersTotal = max(0, (int) ($stats['membres_total'] ?? 0));
$maxTierCount = 1;
foreach (($stats['repartition'] ?? []) as $tier) {
    $maxTierCount = max($maxTierCount, (int) ($tier['count'] ?? 0));
}
?>

<style>
    .loyalty-admin-actions{display:flex;gap:.75rem;flex-wrap:wrap;margin:1rem 0 1.25rem}
    .loyalty-tier-row{display:grid;grid-template-columns:130px 1fr 90px;align-items:center;gap:.75rem;margin:.75rem 0}
    .loyalty-tier-bar{height:12px;background:#f1dfd2;border-radius:999px;overflow:hidden}
    .loyalty-tier-fill{height:100%;background:#E85D04;border-radius:999px}
    .loyalty-dashboard-section{margin-top:1.5rem}
    .loyalty-alert{padding:.8rem 1rem;border-radius:8px;margin:1rem 0}
    .loyalty-alert.success{background:#e9f7ef;color:#146c43}
    .loyalty-alert.error{background:#fdecec;color:#b02a37}
    .manual-modal{position:fixed;inset:0;background:rgba(33,37,41,.45);display:none;align-items:center;justify-content:center;z-index:1050;padding:1rem}
    .manual-modal.is-open{display:flex}
    .manual-modal-card{background:#fff;border-radius:8px;max-width:480px;width:100%;padding:1.25rem;box-shadow:0 20px 50px rgba(33,37,41,.22)}
    .manual-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}
    .manual-modal-close{border:0;background:#f1f3f5;border-radius:6px;width:34px;height:34px}
</style>

<h1 class="page-title">Programme Fidélité</h1>
<p class="page-subtitle">Vue d'ensemble des membres, paliers et récompenses utilisées.</p>

<?php if (!empty($message)): ?>
    <div class="loyalty-alert success"><?php echo htmlspecialchars((string) $message); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="loyalty-alert error"><?php echo htmlspecialchars((string) $error); ?></div>
<?php endif; ?>

<div class="loyalty-admin-actions">
    <a href="index.php?action=adminLoyalty&export=csv" class="btn-sg btn-sg-outline"><i class="bi bi-download me-1"></i> Exporter CSV</a>
    <button type="button" class="btn-sg btn-sg-primary" id="openLoyaltyManualModal"><i class="bi bi-plus-circle me-1"></i> Attribuer points manuellement</button>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-people-fill"></i></div>
        <div class="stat-value"><?php echo number_format($membersTotal, 0, ',', ' '); ?></div>
        <div class="stat-label">Membres total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-stars"></i></div>
        <div class="stat-value"><?php echo number_format((int) ($stats['points_distribues'] ?? 0), 0, ',', ' '); ?></div>
        <div class="stat-label">Points distribués</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon yellow"><i class="bi bi-award-fill"></i></div>
        <div class="stat-value"><?php echo htmlspecialchars((string) ($stats['palier_moyen'] ?? 'Bronze')); ?></div>
        <div class="stat-label">Palier moyen</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-gift-fill"></i></div>
        <div class="stat-value"><?php echo number_format((int) ($stats['recompenses_utilisees'] ?? 0), 0, ',', ' '); ?></div>
        <div class="stat-label">Récompenses utilisées</div>
    </div>
</div>

<div class="sg-table-wrap loyalty-dashboard-section">
    <div class="table-header">
        <h3><i class="bi bi-bar-chart-fill me-2"></i>Répartition par palier</h3>
    </div>
    <div style="padding:1rem;">
        <?php foreach (($stats['repartition'] ?? []) as $tier): ?>
            <?php
            $count = (int) ($tier['count'] ?? 0);
            $width = $maxTierCount > 0 ? (int) round(($count / $maxTierCount) * 100) : 0;
            ?>
            <div class="loyalty-tier-row">
                <strong><?php echo htmlspecialchars((string) ($tier['icone'] ?? '')); ?> <?php echo htmlspecialchars((string) ($tier['nom'] ?? '')); ?></strong>
                <div class="loyalty-tier-bar">
                    <div class="loyalty-tier-fill" style="width:<?php echo $width; ?>%;background:<?php echo htmlspecialchars((string) ($tier['couleur_hex'] ?? '#E85D04')); ?>"></div>
                </div>
                <span><?php echo $count; ?> membres</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="sg-table-wrap loyalty-dashboard-section">
    <div class="table-header">
        <h3><i class="bi bi-trophy-fill me-2"></i>Top 10 clients les plus fidèles</h3>
    </div>
    <table class="sg-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Points</th>
                <th>Palier</th>
                <th>Nb RDV</th>
                <th>Dernier RDV</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($stats['top_clients'])): ?>
                <?php foreach ($stats['top_clients'] as $client): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(trim((string) ($client['client_prenom'] ?? '') . ' ' . (string) ($client['client_nom'] ?? ''))); ?></td>
                        <td><?php echo number_format((int) ($client['points_restants'] ?? 0), 0, ',', ' '); ?></td>
                        <td><?php echo htmlspecialchars((string) ($client['palier_actuel'] ?? 'Bronze')); ?></td>
                        <td><?php echo (int) ($client['nb_rdv'] ?? 0); ?></td>
                        <td><?php echo !empty($client['dernier_rdv']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $client['dernier_rdv']))) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Aucun membre fidélité pour le moment.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="manual-modal" id="loyaltyManualModal" aria-hidden="true">
    <div class="manual-modal-card">
        <div class="manual-modal-head">
            <h3 style="margin:0;">Attribuer des points</h3>
            <button type="button" class="manual-modal-close" id="closeLoyaltyManualModal" aria-label="Fermer"><i class="bi bi-x-lg"></i></button>
        </div>
        <form method="post" action="index.php?action=adminLoyalty">
            <div class="mb-3">
                <label class="form-label" for="loyaltyEmail">Email client</label>
                <input type="email" class="form-control" id="loyaltyEmail" name="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="loyaltyPoints">Points</label>
                <input type="number" class="form-control" id="loyaltyPoints" name="points" min="1" step="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="loyaltyReason">Raison</label>
                <input type="text" class="form-control" id="loyaltyReason" name="raison" maxlength="255" required>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn-sg btn-sg-outline" id="cancelLoyaltyManualModal">Annuler</button>
                <button type="submit" class="btn-sg btn-sg-primary">Attribuer</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('loyaltyManualModal');
        var open = document.getElementById('openLoyaltyManualModal');
        var close = document.getElementById('closeLoyaltyManualModal');
        var cancel = document.getElementById('cancelLoyaltyManualModal');
        function setOpen(isOpen) {
            modal.classList.toggle('is-open', isOpen);
            modal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        }
        if (open && modal) open.addEventListener('click', function () { setOpen(true); });
        if (close && modal) close.addEventListener('click', function () { setOpen(false); });
        if (cancel && modal) cancel.addEventListener('click', function () { setOpen(false); });
        if (modal) modal.addEventListener('click', function (event) { if (event.target === modal) setOpen(false); });
    })();
</script>

<?php require __DIR__ . '/../back/layout_footer.php'; ?>
