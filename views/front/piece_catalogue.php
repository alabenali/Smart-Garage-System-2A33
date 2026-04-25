<?php
$pageTitle = 'Catalogue des Pieces';
$action = 'showCatalogue';
$pieceCount = isset($pagination['total_items']) ? (int) $pagination['total_items'] : count($pieces);
$piecePlural = $pieceCount !== 1 ? 's' : '';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Catalogue des Pieces</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo $pieceCount; ?> piece<?php echo $piecePlural; ?> disponible<?php echo $piecePlural; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=orderHistory" class="btn-sg btn-sg-outline">
            <i class="bi bi-clock-history"></i> Historique
        </a>
        <a href="index.php?action=requestPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-send"></i> Demander
        </a>
    </div>
</div>

<form method="GET" action="index.php" class="filter-panel catalog-filter-panel">
    <input type="hidden" name="action" value="showCatalogue">
    <div class="catalog-filter-grid">
        <div class="search-wrap search-wrap-wide">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="q" placeholder="Rechercher une piece, une marque ou une reference..." value="<?php echo htmlspecialchars((string) ($paginationQuery['q'] ?? '')); ?>">
        </div>
        <select name="categorie">
            <option value="">Toutes les categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars((string) $cat); ?>" <?php echo (($paginationQuery['categorie'] ?? '') === $cat) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $cat); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="stock">
            <option value="">Tous les stocks</option>
            <option value="in-stock" <?php echo (($paginationQuery['stock'] ?? '') === 'in-stock') ? 'selected' : ''; ?>>En stock</option>
            <option value="low-stock" <?php echo (($paginationQuery['stock'] ?? '') === 'low-stock') ? 'selected' : ''; ?>>Stock faible</option>
            <option value="out-of-stock" <?php echo (($paginationQuery['stock'] ?? '') === 'out-of-stock') ? 'selected' : ''; ?>>Rupture</option>
        </select>
        <div class="filter-actions">
            <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-funnel"></i> Filtrer</button>
            <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Reinitialiser</a>
        </div>
    </div>
</form>

<?php if (empty($pieces)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">Pieces</div>
        <h3>Aucune piece trouvee</h3>
        <p>Aucune piece ne correspond a vos criteres pour le moment.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Voir tout le catalogue</a>
            <a href="index.php?action=requestPiece&q=<?php echo urlencode((string) ($paginationQuery['q'] ?? '')); ?>" class="btn-sg btn-sg-primary">
                <i class="bi bi-send"></i> Demander cette piece
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="piece-grid">
        <?php foreach ($pieces as $p): ?>
            <?php
            if ((int) $p['quantite_stock'] <= 0) {
                $stockClass = 'out-of-stock';
                $stockLabel = 'Rupture';
            } elseif ((int) $p['quantite_stock'] <= (int) $p['seuil_alerte']) {
                $stockClass = 'low-stock';
                $stockLabel = 'Stock faible';
            } else {
                $stockClass = 'in-stock';
                $stockLabel = 'En stock';
            }
            ?>
            <div class="piece-card piece-card-rich">
                <div class="piece-media">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?php echo htmlspecialchars((string) $p['image']); ?>" alt="<?php echo htmlspecialchars((string) $p['nom']); ?>">
                    <?php else: ?>
                        <div class="piece-media-fallback">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pc-header">
                    <div>
                        <div class="pc-name"><?php echo htmlspecialchars((string) $p['nom']); ?></div>
                        <div class="pc-brand"><?php echo htmlspecialchars((string) $p['marque']); ?></div>
                    </div>
                    <span class="pc-ref"><?php echo htmlspecialchars((string) $p['reference']); ?></span>
                </div>

                <?php if (!empty($p['description'])): ?>
                    <div class="pc-description"><?php echo htmlspecialchars((string) $p['description']); ?></div>
                <?php endif; ?>

                <div class="pc-details">
                    <div class="pc-detail">
                        <span class="pc-detail-label">Categorie</span>
                        <span class="pc-detail-value"><span class="badge-category"><?php echo htmlspecialchars((string) $p['categorie']); ?></span></span>
                    </div>
                    <div class="pc-detail">
                        <span class="pc-detail-label">Stock</span>
                        <span class="pc-detail-value"><span class="badge-stock <?php echo $stockClass; ?>"><?php echo (int) $p['quantite_stock']; ?> - <?php echo $stockLabel; ?></span></span>
                    </div>
                </div>

                <div class="pc-footer">
                    <span class="pc-price"><?php echo number_format((float) $p['prix_unitaire'], 2, ',', ' '); ?> <small>DT</small></span>
                    <?php if ((int) $p['quantite_stock'] > 0): ?>
                        <a href="index.php?action=orderPiece&id_piece=<?php echo (int) $p['id_piece']; ?>" class="btn-sg btn-sg-primary">
                            <i class="bi bi-cart-check"></i> Commander
                        </a>
                    <?php else: ?>
                        <span class="stock-caption stock-caption-danger">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    $paginationAction = 'showCatalogue';
    require __DIR__ . '/../shared/pagination.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
