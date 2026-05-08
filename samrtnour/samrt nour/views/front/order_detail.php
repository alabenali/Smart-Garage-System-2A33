<?php
$pageTitle = 'Detail de la commande';
$action = 'orderHistory';
$totalGaranties = count($garanties ?? []);
$activeGaranties = 0;
foreach (($garanties ?? []) as $g) {
    if (($g['statut'] ?? '') === 'active' && (int) ($g['jours_restants'] ?? -1) >= 0) {
        $activeGaranties++;
    }
}
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
.order-detail-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr); gap:1.25rem; margin-top:1.5rem; }
.order-detail-panel { background:#fff; border:1px solid var(--border-color); border-radius:14px; padding:1.2rem; box-shadow:var(--shadow); }
.order-detail-panel h2 { font-size:1.05rem; margin:0 0 1rem; color:var(--text-primary); }
.detail-row { display:flex; justify-content:space-between; gap:1rem; padding:.7rem 0; border-bottom:1px solid #edf1f5; }
.detail-row:last-child { border-bottom:0; }
.detail-label { color:var(--text-muted); font-weight:800; text-transform:uppercase; font-size:.78rem; }
.detail-value { color:var(--text-primary); font-weight:800; text-align:right; }
.warranty-list { display:grid; gap:.8rem; }
.warranty-card { border:1px solid #dbe4ef; border-radius:12px; padding:.9rem; background:#f8fafc; }
.warranty-card.active { border-color:#bbf7d0; background:#f0fdf4; }
.warranty-card.expired { border-color:#fecaca; background:#fff7f7; }
.warranty-card-head { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:.65rem; }
.warranty-title { font-weight:900; color:var(--text-primary); }
.warranty-meta { display:grid; gap:.35rem; color:var(--text-secondary); font-size:.88rem; }
.order-items { display:grid; gap:.75rem; }
.order-item-line { display:flex; align-items:center; gap:.9rem; padding:.75rem; border:1px solid #edf1f5; border-radius:12px; }
.order-item-line img, .order-item-fallback { width:54px; height:54px; border-radius:12px; object-fit:cover; background:#f8e9e8; display:flex; align-items:center; justify-content:center; color:var(--accent); }
@media (max-width:900px){ .order-detail-grid{grid-template-columns:1fr;} }
</style>

<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Commande #<?php echo (int) $commande['id_commande']; ?></h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            Detail de l'achat et garantie associee
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=orderHistory" class="btn-sg btn-sg-outline"><i class="bi bi-arrow-left"></i> Retour</a>
    </div>
</div>

<div class="order-detail-grid">
    <section class="order-detail-panel">
        <h2><i class="bi bi-bag-check me-1"></i> Achat</h2>
        <div class="order-items">
            <?php foreach (($items ?? []) as $item): ?>
                <div class="order-item-line">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars((string) $item['image']); ?>" alt="<?php echo htmlspecialchars((string) $item['nom']); ?>">
                    <?php else: ?>
                        <div class="order-item-fallback"><i class="bi bi-box-seam"></i></div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0;">
                        <strong><?php echo htmlspecialchars((string) $item['nom']); ?></strong>
                        <div class="table-subtext"><?php echo htmlspecialchars((string) ($item['reference'] ?? '')); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <strong><?php echo (int) ($item['quantite'] ?? 0); ?> x <?php echo number_format((float) ($item['prix_unitaire'] ?? 0), 2, ',', ' '); ?> DT</strong>
                        <div class="table-subtext"><?php echo number_format((float) ($item['sous_total'] ?? 0), 2, ',', ' '); ?> DT</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:1rem;">
            <div class="detail-row"><span class="detail-label">Statut</span><span class="detail-value"><?php echo htmlspecialchars((string) $commande['statut']); ?></span></div>
            <div class="detail-row"><span class="detail-label">Paiement</span><span class="detail-value"><?php echo htmlspecialchars((string) ($commande['payment_method'] ?? '-')); ?></span></div>
            <div class="detail-row"><span class="detail-label">Total</span><span class="detail-value"><?php echo number_format((float) $commande['montant_total'], 2, ',', ' '); ?> DT</span></div>
            <div class="detail-row"><span class="detail-label">Date</span><span class="detail-value"><?php echo !empty($commande['date_commande']) ? date('d/m/Y H:i', strtotime((string) $commande['date_commande'])) : '-'; ?></span></div>
        </div>
    </section>

    <aside class="order-detail-panel">
        <h2><i class="bi bi-shield-check me-1"></i> Garantie</h2>
        <?php if (empty($garanties)): ?>
            <div class="warranty-card">
                <div class="warranty-title">Aucune garantie associee</div>
                <div class="warranty-meta">Cette commande ne contient pas de garantie active.</div>
            </div>
        <?php else: ?>
            <div class="warranty-list">
                <?php foreach ($garanties as $g): ?>
                    <?php
                    $days = (int) ($g['jours_restants'] ?? 0);
                    $isActive = ($g['statut'] ?? '') === 'active' && $days >= 0;
                    $cardClass = $isActive ? 'active' : 'expired';
                    ?>
                    <div class="warranty-card <?php echo $cardClass; ?>">
                        <div class="warranty-card-head">
                            <div>
                                <div class="warranty-title"><?php echo htmlspecialchars((string) ($g['nom_piece'] ?? $commande['piece_nom'])); ?></div>
                                <div class="table-subtext">Garantie #<?php echo (int) $g['id_garantie']; ?></div>
                            </div>
                            <span class="<?php echo $isActive ? 'badge-stock in-stock' : 'badge-stock out-of-stock'; ?>">
                                <?php echo $isActive ? 'Active' : htmlspecialchars((string) ($g['statut'] ?? 'Expiree')); ?>
                            </span>
                        </div>
                        <div class="warranty-meta">
                            <div>Debut : <?php echo !empty($g['date_pose']) ? date('d/m/Y', strtotime((string) $g['date_pose'])) : '-'; ?></div>
                            <div>Expiration : <?php echo !empty($g['date_expiration']) ? date('d/m/Y', strtotime((string) $g['date_expiration'])) : '-'; ?></div>
                            <div>Duree : <?php echo (int) ($g['duree_mois'] ?? 0); ?> mois</div>
                            <div><?php echo $isActive ? $days . ' jours restants' : 'Garantie non active'; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h2 style="margin-top:1.25rem;"><i class="bi bi-car-front me-1"></i> Liaison garage</h2>
        <div class="detail-row"><span class="detail-label">Vehicule</span><span class="detail-value"><?php echo htmlspecialchars(trim((string) (($commande['vehicule_marque'] ?? '') . ' ' . ($commande['vehicule_modele'] ?? ''))) ?: '-'); ?></span></div>
        <div class="detail-row"><span class="detail-label">Immat.</span><span class="detail-value"><?php echo htmlspecialchars((string) ($commande['vehicule_immatriculation'] ?? '-')); ?></span></div>
        <div class="detail-row"><span class="detail-label">RDV</span><span class="detail-value"><?php echo !empty($commande['id_rdv']) ? '#' . (int) $commande['id_rdv'] : '-'; ?></span></div>
    </aside>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
