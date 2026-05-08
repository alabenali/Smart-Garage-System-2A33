<?php
$pageTitle = 'Historique des Commandes';
$action = 'orderHistory';
$orderCount = isset($pagination['total_items']) ? (int) $pagination['total_items'] : count($commandes);
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Historique des Commandes</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo $orderCount; ?> commande<?php echo $orderCount !== 1 ? 's' : ''; ?> retrouvee<?php echo $orderCount !== 1 ? 's' : ''; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=orderPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-cart-plus"></i> Nouvelle commande
        </a>
    </div>
</div>

<form method="GET" action="index.php" class="filter-panel history-filter-panel">
    <input type="hidden" name="action" value="orderHistory">
    <div class="history-filter-grid">
        <div class="search-wrap search-wrap-wide">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="q" placeholder="Rechercher par client, telephone, piece ou reference..." value="<?php echo htmlspecialchars((string) ($paginationQuery['q'] ?? '')); ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-search"></i> Rechercher</button>
            <a href="index.php?action=orderHistory" class="btn-sg btn-sg-outline">Reinitialiser</a>
        </div>
    </div>
</form>

<?php if (empty($commandes)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">Hist</div>
        <h3>Aucune commande trouvee</h3>
        <p>Essayez un autre nom, telephone ou reference de piece.</p>
    </div>
<?php else: ?>
    <div class="history-grid">
        <?php foreach ($commandes as $c): ?>
            <?php
            $statutClass = 'badge-stock in-stock';
            if (($c['statut'] ?? '') === 'En attente') {
                $statutClass = 'badge-stock low-stock';
            } elseif (($c['statut'] ?? '') === 'Annulee') {
                $statutClass = 'badge-stock out-of-stock';
            }
            $garantieCount = (int) ($c['garantie_count'] ?? 0);
            $garantieActiveCount = (int) ($c['garantie_active_count'] ?? 0);
            $garantieDays = isset($c['garantie_min_days']) ? (int) $c['garantie_min_days'] : null;
            $garantieText = 'Aucune garantie';
            $garantieClass = 'warranty-mini muted';
            if ($garantieCount > 0 && $garantieActiveCount > 0) {
                $garantieClass = 'warranty-mini active';
                $garantieText = $garantieActiveCount . ' garantie' . ($garantieActiveCount > 1 ? 's' : '') . ' active' . ($garantieActiveCount > 1 ? 's' : '');
                if (!empty($c['garantie_next_expiration'])) {
                    $garantieText .= ' jusqu au ' . date('d/m/Y', strtotime((string) $c['garantie_next_expiration']));
                }
            } elseif ($garantieCount > 0) {
                $garantieClass = 'warranty-mini expired';
                $garantieText = 'Garantie expiree';
            }
            ?>
            <a href="index.php?action=orderDetail&id=<?php echo (int) $c['id_commande']; ?>" class="history-card history-card-shell history-card-link" style="background:#ffffff;border:1px solid #d9dee5;border-radius:12px;padding:1.2rem;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
                <div class="history-card-head">
                    <div class="table-piece-cell">
                        <div class="table-piece-thumb">
                            <?php if (!empty($c['piece_image'])): ?>
                                <img src="<?php echo htmlspecialchars((string) $c['piece_image']); ?>" alt="<?php echo htmlspecialchars((string) $c['piece_nom']); ?>">
                            <?php else: ?>
                                <i class="bi bi-box-seam"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars((string) $c['piece_nom']); ?></strong>
                            <div class="table-subtext"><?php echo htmlspecialchars((string) $c['piece_reference']); ?></div>
                        </div>
                    </div>
                    <span class="<?php echo $statutClass; ?>"><?php echo htmlspecialchars((string) $c['statut']); ?></span>
                </div>

                <div class="history-card-body">
                    <div class="history-meta" style="padding-bottom:0.65rem;border-bottom:1px solid #edf1f5;">
                        <span class="history-label">Client</span>
                        <span class="history-value"><?php echo htmlspecialchars(trim((string) ($c['prenom_client'] . ' ' . $c['nom_client']))); ?></span>
                    </div>
                    <div class="history-meta" style="padding-bottom:0.65rem;border-bottom:1px solid #edf1f5;">
                        <span class="history-label">Telephone</span>
                        <span class="history-value"><?php echo htmlspecialchars((string) $c['telephone']); ?></span>
                    </div>
                    <div class="history-meta" style="padding-bottom:0.65rem;border-bottom:1px solid #edf1f5;">
                        <span class="history-label">Quantite</span>
                        <span class="history-value"><?php echo (int) $c['quantite']; ?></span>
                    </div>
                    <div class="history-meta">
                        <span class="history-label">Total</span>
                        <span class="history-price"><?php echo number_format((float) $c['montant_total'], 2, ',', ' '); ?> DT</span>
                    </div>
                </div>

                <div class="history-card-footer">
                    <span class="table-subtext">Commande #<?php echo (int) $c['id_commande']; ?></span>
                    <span class="table-subtext"><?php echo !empty($c['date_commande']) ? date('d/m/Y H:i', strtotime((string) $c['date_commande'])) : '-'; ?></span>
                </div>
                <div class="<?php echo $garantieClass; ?>">
                    <i class="bi bi-shield-check"></i>
                    <span><?php echo htmlspecialchars($garantieText); ?></span>
                    <?php if ($garantieDays !== null && $garantieDays >= 0 && $garantieActiveCount > 0): ?>
                        <small><?php echo $garantieDays; ?> j restants</small>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php
    $paginationAction = 'orderHistory';
    require __DIR__ . '/../shared/pagination.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
