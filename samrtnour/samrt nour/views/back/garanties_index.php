<?php
$pageTitle = 'Garanties';
$action    = $action ?? 'manageGaranties';
$filtre    = $filtre ?? 'toutes';
$stats     = $stats ?? ['total' => 0, 'actives' => 0, 'expirees' => 0, 'remplacees' => 0, 'expirent_bientot' => 0];
$garanties = $garanties ?? [];
$success   = $success ?? '';
$error     = $error ?? '';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
/* ── Garanties dashboard ── */
.gar-stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.75rem; }
@media (max-width: 900px) { .gar-stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px) { .gar-stats-grid { grid-template-columns: repeat(2, 1fr); } }
.gar-stat { background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 16px; padding: 1.25rem; text-align: center; transition: transform .2s, box-shadow .2s; }
.gar-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.gar-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: .5rem; }
.gar-stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.gar-stat-icon.green  { background: #d1fae5; color: #059669; }
.gar-stat-icon.orange { background: #fef3c7; color: #d97706; }
.gar-stat-icon.red    { background: #fee2e2; color: #dc2626; }
.gar-stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.gar-stat-value { font-size: 1.8rem; font-weight: 800; color: var(--text-primary, #1e293b); }
.gar-stat-label { font-size: .78rem; color: var(--text-muted, #94a3b8); margin-top: 2px; }

/* ── Filtres ── */
.gar-filters { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.gar-filter-btn {
    padding: 8px 18px; border-radius: 10px; border: 1px solid var(--border-color, #e2e8f0);
    background: transparent; font-size: .85rem; font-weight: 600; cursor: pointer;
    text-decoration: none; color: var(--text-secondary, #64748b); transition: all .15s;
}
.gar-filter-btn:hover { border-color: #173252; color: #173252; }
.gar-filter-btn.active { background: linear-gradient(135deg, #173252, #c43d2f); color: #fff; border-color: transparent; }

/* ── Badges état garantie ── */
.badge-gar { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.badge-gar-active  { background: #d1fae5; color: #059669; }
.badge-gar-warning { background: #fef3c7; color: #d97706; }
.badge-gar-expired { background: #fee2e2; color: #dc2626; }
.badge-gar-replaced{ background: #e0e7ff; color: #4338ca; }

/* ── Jours restants ── */
.gar-days { font-weight: 700; font-size: .88rem; }
.gar-days.safe    { color: #059669; }
.gar-days.warning { color: #d97706; }
.gar-days.danger  { color: #dc2626; }

/* ── Barre progression garantie ── */
.gar-progress { width: 100%; height: 6px; background: var(--bg-secondary, #f1f5f9); border-radius: 3px; overflow: hidden; margin-top: 4px; }
.gar-progress-bar { height: 100%; border-radius: 3px; transition: width .3s; }
.gar-progress-bar.green  { background: #059669; }
.gar-progress-bar.orange { background: #d97706; }
.gar-progress-bar.red    { background: #dc2626; }

/* ── Modal remplacement ── */
.gar-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1000; display: none; align-items: center; justify-content: center; }
.gar-modal-overlay.show { display: flex; }
.gar-modal { background: var(--card-bg, #fff); border-radius: 18px; padding: 2rem; max-width: 450px; width: 90%; box-shadow: 0 16px 48px rgba(0,0,0,.15); }
.gar-modal h3 { margin: 0 0 1rem; font-size: 1.1rem; }
.gar-modal textarea { width: 100%; min-height: 80px; padding: 10px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; font-size: .9rem; resize: vertical; }
.gar-modal-actions { display: flex; gap: .5rem; margin-top: 1rem; justify-content: flex-end; }

/* ── Toast succès/erreur ── */
.gar-alert { padding: 12px 18px; border-radius: 12px; margin-bottom: 1rem; font-size: .88rem; font-weight: 500; }
.gar-alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
.gar-alert-error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
</style>

<h1 class="page-title"><i class="bi bi-shield-check me-2"></i>Garanties Pièces</h1>
<p class="page-subtitle">Suivi des garanties actives, alertes d'expiration et historique.</p>

<?php if ($success !== ''): ?>
    <div class="gar-alert gar-alert-success"><i class="bi bi-check-circle me-1"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="gar-alert gar-alert-error"><i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?></div>
<?php endif; ?>

<!-- Statistiques -->
<div class="gar-stats-grid">
    <div class="gar-stat">
        <div class="gar-stat-icon blue"><i class="bi bi-shield-fill"></i></div>
        <div class="gar-stat-value"><?php echo $stats['total']; ?></div>
        <div class="gar-stat-label">Total garanties</div>
    </div>
    <div class="gar-stat">
        <div class="gar-stat-icon green"><i class="bi bi-shield-fill-check"></i></div>
        <div class="gar-stat-value"><?php echo $stats['actives']; ?></div>
        <div class="gar-stat-label">Actives</div>
    </div>
    <div class="gar-stat">
        <div class="gar-stat-icon orange"><i class="bi bi-clock-history"></i></div>
        <div class="gar-stat-value"><?php echo $stats['expirent_bientot']; ?></div>
        <div class="gar-stat-label">Expirent sous 30j</div>
    </div>
    <div class="gar-stat">
        <div class="gar-stat-icon red"><i class="bi bi-shield-fill-x"></i></div>
        <div class="gar-stat-value"><?php echo $stats['expirees']; ?></div>
        <div class="gar-stat-label">Expirées</div>
    </div>
    <div class="gar-stat">
        <div class="gar-stat-icon purple"><i class="bi bi-arrow-repeat"></i></div>
        <div class="gar-stat-value"><?php echo $stats['remplacees']; ?></div>
        <div class="gar-stat-label">Remplacées</div>
    </div>
</div>

<!-- Filtres -->
<div class="gar-filters">
    <a href="index.php?action=manageGaranties&filtre=toutes" class="gar-filter-btn <?php echo $filtre === 'toutes' ? 'active' : ''; ?>">
        <i class="bi bi-list-ul me-1"></i> Toutes
    </a>
    <a href="index.php?action=manageGaranties&filtre=actives" class="gar-filter-btn <?php echo $filtre === 'actives' ? 'active' : ''; ?>">
        <i class="bi bi-shield-check me-1"></i> Actives
    </a>
    <a href="index.php?action=manageGaranties&filtre=bientot" class="gar-filter-btn <?php echo $filtre === 'bientot' ? 'active' : ''; ?>">
        <i class="bi bi-exclamation-triangle me-1"></i> Expirent bientôt
    </a>
    <a href="index.php?action=manageGaranties&filtre=expirees" class="gar-filter-btn <?php echo $filtre === 'expirees' ? 'active' : ''; ?>">
        <i class="bi bi-x-circle me-1"></i> Expirées
    </a>
    <a href="index.php?action=manageGaranties&filtre=remplacees" class="gar-filter-btn <?php echo $filtre === 'remplacees' ? 'active' : ''; ?>">
        <i class="bi bi-arrow-repeat me-1"></i> Remplacées
    </a>
    <a href="index.php?action=testAlertes" target="_blank" class="gar-filter-btn" style="margin-left:auto;">
        <i class="bi bi-bell me-1"></i> Tester alertes
    </a>
</div>

<!-- Tableau des garanties -->
<div class="sg-table-wrap">
    <div class="table-header">
        <h3><i class="bi bi-table me-2"></i>Liste des garanties (<?php echo count($garanties); ?>)</h3>
    </div>
    <table class="sg-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Pièce</th>
                <th>Client</th>
                <th>Date pose</th>
                <th>Expiration</th>
                <th>Progression</th>
                <th>Statut</th>
                <th>Alertes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($garanties)): ?>
                <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:2.5rem;">
                    <i class="bi bi-shield" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                    Aucune garantie trouvée pour ce filtre.
                </td></tr>
            <?php else: ?>
                <?php foreach ($garanties as $g):
                    $jours = (int)($g['jours_restants'] ?? 0);
                    $duree = (int)($g['duree_mois'] ?? 12);
                    $totalDays = $duree * 30;
                    $elapsed = $totalDays - $jours;
                    $pct = $totalDays > 0 ? min(100, max(0, round(($elapsed / $totalDays) * 100))) : 100;

                    if ($g['statut'] === 'remplacee') { $badgeClass = 'badge-gar-replaced'; $badgeText = 'Remplacée'; }
                    elseif ($g['statut'] === 'expiree' || $jours < 0) { $badgeClass = 'badge-gar-expired'; $badgeText = 'Expirée'; }
                    elseif ($jours <= 30) { $badgeClass = 'badge-gar-warning'; $badgeText = 'Bientôt'; }
                    else { $badgeClass = 'badge-gar-active'; $badgeText = 'Active'; }

                    $daysClass = $jours > 30 ? 'safe' : ($jours > 7 ? 'warning' : 'danger');
                    $barClass  = $pct < 70 ? 'green' : ($pct < 90 ? 'orange' : 'red');
                ?>
                <tr>
                    <td style="color:var(--text-muted);">#<?php echo (int)$g['id_garantie']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($g['nom_piece'] ?? ''); ?></strong><br>
                        <small style="color:var(--text-muted);"><?php echo htmlspecialchars($g['marque_piece'] ?? ''); ?> · <?php echo htmlspecialchars($g['ref_piece'] ?? ''); ?></small>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($g['nom_complet'] ?? ''); ?></strong><br>
                        <small style="color:var(--text-muted);"><?php echo htmlspecialchars($g['telephone'] ?? ''); ?></small>
                    </td>
                    <td><?php echo !empty($g['date_pose']) ? date('d/m/Y', strtotime($g['date_pose'])) : '-'; ?></td>
                    <td>
                        <?php echo !empty($g['date_expiration']) ? date('d/m/Y', strtotime($g['date_expiration'])) : '-'; ?><br>
                        <span class="gar-days <?php echo $daysClass; ?>">
                            <?php if ($jours > 0): ?>
                                <?php echo $jours; ?>j restants
                            <?php elseif ($jours === 0): ?>
                                Aujourd'hui
                            <?php else: ?>
                                Expirée (<?php echo abs($jours); ?>j)
                            <?php endif; ?>
                        </span>
                    </td>
                    <td style="min-width:100px;">
                        <div class="gar-progress"><div class="gar-progress-bar <?php echo $barClass; ?>" style="width:<?php echo $pct; ?>%"></div></div>
                        <small style="color:var(--text-muted);font-size:.72rem;"><?php echo $pct; ?>% écoulé</small>
                    </td>
                    <td><span class="badge-gar <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span></td>
                    <td>
                        <?php if ((int)$g['alerte_30j_envoyee']): ?><span title="Alerte 30j envoyée" style="color:#d97706;">📨</span><?php endif; ?>
                        <?php if ((int)$g['alerte_7j_envoyee']): ?><span title="Alerte 7j envoyée" style="color:#dc2626;">📨</span><?php endif; ?>
                        <?php if ((int)$g['alerte_expir_envoyee']): ?><span title="Alerte expiration envoyée" style="color:#991b1b;">📨</span><?php endif; ?>
                        <?php if (!(int)$g['alerte_30j_envoyee'] && !(int)$g['alerte_7j_envoyee'] && !(int)$g['alerte_expir_envoyee']): ?>
                            <span style="color:var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($g['statut'] === 'active'): ?>
                            <button class="btn-sg btn-sg-outline btn-sg-sm" onclick="openReplaceModal(<?php echo (int)$g['id_garantie']; ?>, '<?php echo addslashes(htmlspecialchars($g['nom_piece'] ?? '')); ?>')" title="Marquer comme remplacée">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                        <?php else: ?>
                            <span style="color:var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Remplacement -->
<div class="gar-modal-overlay" id="replaceModal">
    <div class="gar-modal">
        <h3><i class="bi bi-arrow-repeat me-2"></i>Marquer comme remplacée</h3>
        <p id="replaceModalPiece" style="font-size:.88rem;color:var(--text-secondary);margin-bottom:1rem;"></p>
        <form method="POST" action="index.php?action=marquerRemplacee">
            <input type="hidden" name="id_garantie" id="replaceModalId">
            <label style="font-size:.82rem;font-weight:600;display:block;margin-bottom:4px;">Note (optionnel)</label>
            <textarea name="notes" placeholder="Raison du remplacement, nouvelle pièce installée..."></textarea>
            <div class="gar-modal-actions">
                <button type="button" class="btn-sg btn-sg-outline btn-sg-sm" onclick="closeReplaceModal()">Annuler</button>
                <button type="submit" class="btn-sg btn-sg-primary btn-sg-sm"><i class="bi bi-check-lg me-1"></i>Confirmer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplaceModal(id, nomPiece) {
    document.getElementById('replaceModalId').value = id;
    document.getElementById('replaceModalPiece').textContent = 'Pièce : ' + nomPiece + ' (Garantie #' + id + ')';
    document.getElementById('replaceModal').classList.add('show');
}
function closeReplaceModal() {
    document.getElementById('replaceModal').classList.remove('show');
}
document.getElementById('replaceModal').addEventListener('click', function(e) {
    if (e.target === this) closeReplaceModal();
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
