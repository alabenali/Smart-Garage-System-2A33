<?php $pageTitle = 'Catalogue des Pièces'; $action = 'showCatalogue'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Catalogue des Pièces</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo count($pieces); ?> pièce<?php echo count($pieces) !== 1 ? 's' : ''; ?> disponible<?php echo count($pieces) !== 1 ? 's' : ''; ?>
        </p>
    </div>
    <a href="index.php?action=orderPiece" class="btn-sg btn-sg-primary">
        <i class="bi bi-cart-plus"></i> Commander
    </a>
</div>

<!-- Search & Filter Bar -->
<div class="filter-bar" id="filterBar">
    <div class="search-wrap">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Rechercher une pièce..." autocomplete="off">
    </div>
    <select id="filterCategory">
        <option value="">Toutes les catégories</option>
        <?php
        $categories = array_unique(array_column($pieces, 'categorie'));
        sort($categories);
        foreach ($categories as $cat):
        ?>
            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filterStock">
        <option value="">Tous les stocks</option>
        <option value="in-stock">En stock</option>
        <option value="low-stock">Stock faible</option>
        <option value="out-of-stock">Rupture</option>
    </select>
</div>

<?php if (empty($pieces)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">🔩</div>
        <h3>Aucune pièce trouvée</h3>
        <p>Aucune pièce n'est disponible pour le moment.</p>
        <a href="index.php?action=orderPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-cart-plus"></i> Commander une Pièce
        </a>
    </div>
<?php else: ?>
    <div class="piece-grid" id="pieceGrid">
        <?php foreach ($pieces as $p): ?>
            <?php
                // Determine stock status
                if ($p['quantite_stock'] <= 0) {
                    $stockClass = 'out-of-stock';
                    $stockLabel = 'Rupture';
                } elseif ($p['quantite_stock'] <= $p['seuil_alerte']) {
                    $stockClass = 'low-stock';
                    $stockLabel = 'Stock faible';
                } else {
                    $stockClass = 'in-stock';
                    $stockLabel = 'En stock';
                }
            ?>
            <div class="piece-card"
                 data-name="<?php echo strtolower(htmlspecialchars($p['nom'] . ' ' . $p['marque'] . ' ' . $p['reference'])); ?>"
                 data-category="<?php echo htmlspecialchars($p['categorie']); ?>"
                 data-stock="<?php echo $stockClass; ?>">

                <div class="pc-header">
                    <div>
                        <div class="pc-name"><?php echo htmlspecialchars($p['nom']); ?></div>
                        <div class="pc-brand"><?php echo htmlspecialchars($p['marque']); ?></div>
                    </div>
                    <span class="pc-ref"><?php echo htmlspecialchars($p['reference']); ?></span>
                </div>

                <?php if (!empty($p['description'])): ?>
                    <div class="pc-description"><?php echo htmlspecialchars($p['description']); ?></div>
                <?php endif; ?>

                <div class="pc-details">
                    <div class="pc-detail">
                        <span class="pc-detail-label">Catégorie</span>
                        <span class="pc-detail-value">
                            <span class="badge-category"><?php echo htmlspecialchars($p['categorie']); ?></span>
                        </span>
                    </div>
                    <div class="pc-detail">
                        <span class="pc-detail-label">Stock</span>
                        <span class="pc-detail-value">
                            <span class="badge-stock <?php echo $stockClass; ?>"><?php echo $p['quantite_stock']; ?> – <?php echo $stockLabel; ?></span>
                        </span>
                    </div>
                </div>

                <div class="pc-footer">
                    <span class="pc-price"><?php echo number_format($p['prix_unitaire'], 2, ',', ' '); ?> <small>DT</small></span>
                    <?php if ($p['quantite_stock'] > 0): ?>
                        <a href="index.php?action=orderPiece&id_piece=<?php echo $p['id_piece']; ?>" class="btn-sg btn-sg-primary" style="padding:0.4rem 1rem; font-size:0.85rem;">
                            <i class="bi bi-cart-plus"></i> Commander
                        </a>
                    <?php else: ?>
                        <span style="color:var(--danger); font-size:0.85rem; font-weight:500;">Rupture de stock</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- No results message (hidden by default) -->
    <div class="sg-form-wrap empty-state" id="noResults" style="display:none;">
        <div class="empty-icon">🔍</div>
        <h3>Aucun résultat</h3>
        <p>Aucune pièce ne correspond à vos critères de recherche.</p>
    </div>
<?php endif; ?>

<!-- Client-side search & filter -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterCategory = document.getElementById('filterCategory');
    const filterStock = document.getElementById('filterStock');
    const pieceGrid = document.getElementById('pieceGrid');
    const noResults = document.getElementById('noResults');

    if (!pieceGrid) return;

    function filterPieces() {
        const search = searchInput.value.toLowerCase().trim();
        const cat = filterCategory.value;
        const stock = filterStock.value;
        const cards = pieceGrid.querySelectorAll('.piece-card');
        let visibleCount = 0;

        cards.forEach(function(card) {
            const name = card.getAttribute('data-name');
            const cardCat = card.getAttribute('data-category');
            const cardStock = card.getAttribute('data-stock');

            let show = true;
            if (search && name.indexOf(search) === -1) show = false;
            if (cat && cardCat !== cat) show = false;
            if (stock && cardStock !== stock) show = false;

            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', filterPieces);
    filterCategory.addEventListener('change', filterPieces);
    filterStock.addEventListener('change', filterPieces);
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
