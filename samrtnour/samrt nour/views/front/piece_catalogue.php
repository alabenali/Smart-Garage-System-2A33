<?php
$pageTitle = 'Catalogue des Pièces';
$action = 'showCatalogue';
$pieceCount = isset($pagination['total_items']) ? (int) $pagination['total_items'] : count($pieces);
$piecePlural = $pieceCount !== 1 ? 's' : '';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<!-- Styles spécifiques catalogue + panier -->
<style>
/* ── Barre de filtres améliorée ── */
.catalog-filter-panel { padding: 1.2rem 1.5rem; }
.catalog-filter-grid {
    display: flex; flex-wrap: wrap; gap: .75rem; align-items: center;
}
.catalog-filter-grid select,
.catalog-filter-grid .search-wrap input {
    min-width: 160px;
}

/* ── Grille responsive ── */
.piece-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-top: 1.5rem;
}
@media (max-width: 1024px) { .piece-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .piece-grid { grid-template-columns: 1fr; } }

/* ── Cartes pièces ── */
.piece-card-rich {
    border-radius: 18px;
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
    border: 1px solid var(--sg-border, #e2e8f0);
    background: var(--sg-card-bg, #fff);
}
.piece-card-rich:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(23,50,82,.12);
}

/* ── Badges catégorie colorés ── */
.badge-cat { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.badge-cat-freinage    { background: #fee2e2; color: #dc2626; }
.badge-cat-filtration  { background: #dbeafe; color: #2563eb; }
.badge-cat-suspension  { background: #d1fae5; color: #059669; }
.badge-cat-moteur      { background: #fef3c7; color: #d97706; }
.badge-cat-eclairage   { background: #ede9fe; color: #7c3aed; }
.badge-cat-electricite { background: #fce7f3; color: #db2777; }
.badge-cat-default     { background: #f1f5f9; color: #475569; }

/* ── Quantité sélecteur ── */
.qty-selector {
    display: inline-flex; align-items: center; gap: 0; border-radius: 10px;
    border: 1px solid var(--sg-border, #e2e8f0); overflow: hidden;
}
.qty-selector button {
    width: 32px; height: 32px; border: none; background: var(--sg-bg-subtle, #f8fafc);
    cursor: pointer; font-size: 1rem; font-weight: 700; color: var(--sg-text, #334155);
    transition: background .15s;
}
.qty-selector button:hover { background: var(--sg-primary, #173252); color: #fff; }
.qty-selector input {
    width: 40px; height: 32px; border: none; text-align: center; font-size: .85rem;
    font-weight: 600; background: transparent; color: var(--sg-text, #334155);
    -moz-appearance: textfield;
}
.qty-selector input::-webkit-outer-spin-button,
.qty-selector input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

/* ── Boutons d'action carte ── */
.pc-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; margin-top: .75rem; }
.btn-add-cart {
    display: inline-flex; align-items: center; gap: .35rem; padding: 8px 16px;
    background: linear-gradient(135deg, #059669, #10b981); color: #fff;
    border: none; border-radius: 10px; font-size: .82rem; font-weight: 600;
    cursor: pointer; transition: transform .15s, box-shadow .15s;
}
.btn-add-cart:hover { transform: scale(1.04); box-shadow: 0 4px 16px rgba(5,150,105,.3); }
.btn-direct-order {
    display: inline-flex; align-items: center; gap: .35rem; padding: 8px 14px;
    background: transparent; border: 1px solid var(--sg-border, #e2e8f0);
    border-radius: 10px; font-size: .8rem; font-weight: 500; color: var(--sg-text-muted, #64748b);
    cursor: pointer; transition: all .15s; text-decoration: none;
}
.btn-direct-order:hover { border-color: var(--sg-primary, #173252); color: var(--sg-primary, #173252); }

/* ── Compteur panier flottant ── */
.cart-float-btn {
    position: fixed; bottom: 28px; right: 28px; z-index: 999;
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, #173252, #c43d2f);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; cursor: pointer; border: none;
    box-shadow: 0 8px 28px rgba(23,50,82,.35);
    transition: transform .2s;
}
.cart-float-btn:hover { transform: scale(1.1); }
.cart-badge {
    position: absolute; top: -4px; right: -4px;
    min-width: 22px; height: 22px; padding: 0 6px;
    background: #ef4444; color: #fff; border-radius: 50%;
    font-size: .72rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    transition: transform .3s;
}
.cart-badge.bounce { animation: badgeBounce .4s ease; }
.cart-badge.hidden { display: none; }
@keyframes badgeBounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.4); }
}

/* ── Overlay ── */
.cart-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.45);
    z-index: 1000; opacity: 0; visibility: hidden;
    transition: opacity .3s, visibility .3s;
}
.cart-overlay.open { opacity: 1; visibility: visible; }

/* ── Sidebar panier ── */
.cart-sidebar {
    position: fixed; top: 0; right: -420px; width: 400px; max-width: 90vw;
    height: 100vh; background: var(--sg-card-bg, #fff);
    box-shadow: -8px 0 32px rgba(0,0,0,.15); z-index: 1001;
    display: flex; flex-direction: column; transition: right .3s ease;
}
.cart-sidebar.open { right: 0; }
.cart-sidebar-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--sg-border, #e2e8f0);
}
.cart-sidebar-header h3 { margin: 0; font-size: 1.1rem; }
.cart-sidebar-close {
    width: 36px; height: 36px; border-radius: 10px;
    border: 1px solid var(--sg-border, #e2e8f0); background: transparent;
    font-size: 1.2rem; cursor: pointer; display: flex; align-items: center;
    justify-content: center; transition: background .15s;
}
.cart-sidebar-close:hover { background: #fee2e2; color: #dc2626; }

/* ── Contenu items sidebar ── */
.cart-items-wrap { flex: 1; overflow-y: auto; padding: 1rem 1.5rem; }
.cart-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 0; border-bottom: 1px solid var(--sg-border-light, #f1f5f9);
    transition: opacity .3s, max-height .3s;
}
.cart-item-img {
    width: 50px; height: 50px; border-radius: 10px; object-fit: cover;
    background: var(--sg-bg-subtle, #f8fafc);
}
.cart-item-img-fallback {
    width: 50px; height: 50px; border-radius: 10px;
    background: var(--sg-bg-subtle, #f8fafc);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: var(--sg-text-muted, #94a3b8);
}
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-name { font-weight: 600; font-size: .85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cart-item-brand { font-size: .75rem; color: var(--sg-text-muted, #94a3b8); }
.cart-item-price { font-weight: 700; font-size: .85rem; color: var(--sg-primary, #173252); white-space: nowrap; }
.cart-item-remove {
    width: 28px; height: 28px; border-radius: 8px;
    border: none; background: #fee2e2; color: #dc2626;
    cursor: pointer; font-size: .85rem; display: flex;
    align-items: center; justify-content: center; transition: background .15s;
}
.cart-item-remove:hover { background: #fecaca; }
.cart-item-qty { display: flex; align-items: center; gap: 0; margin-top: 4px; }
.cart-item-qty button {
    width: 24px; height: 24px; border: 1px solid var(--sg-border, #e2e8f0);
    border-radius: 6px; background: var(--sg-bg-subtle, #f8fafc);
    cursor: pointer; font-size: .8rem; font-weight: 700; color: var(--sg-text, #334155);
}
.cart-item-qty button:hover { background: var(--sg-primary, #173252); color: #fff; }
.cart-item-qty span { min-width: 28px; text-align: center; font-size: .82rem; font-weight: 600; }
.cart-price-changed { color: #d97706; font-size: .7rem; font-style: italic; }
.cart-empty-msg { text-align: center; color: var(--sg-text-muted, #94a3b8); padding: 3rem 1rem; font-size: .9rem; }

/* ── Footer sidebar ── */
.cart-sidebar-footer {
    border-top: 1px solid var(--sg-border, #e2e8f0);
    padding: 1.25rem 1.5rem;
}
.cart-totals { margin-bottom: 1rem; }
.cart-total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: .85rem; }
.cart-total-row.total-final { font-weight: 700; font-size: 1rem; border-top: 1px solid var(--sg-border, #e2e8f0); padding-top: .5rem; margin-top: .5rem; }
.cart-sidebar-footer .btn-checkout {
    display: block; width: 100%; padding: 12px; border: none; border-radius: 12px;
    background: linear-gradient(135deg, #173252, #c43d2f); color: #fff;
    font-size: .95rem; font-weight: 700; cursor: pointer;
    text-align: center; text-decoration: none; transition: opacity .2s;
}
.cart-sidebar-footer .btn-checkout:hover { opacity: .9; }
.btn-clear-cart {
    display: block; width: 100%; padding: 8px; margin-top: .5rem;
    border: 1px solid var(--sg-border, #e2e8f0); border-radius: 10px;
    background: transparent; color: var(--sg-text-muted, #64748b);
    font-size: .82rem; cursor: pointer; transition: all .15s; text-align: center;
}
.btn-clear-cart:hover { border-color: #dc2626; color: #dc2626; }

/* ── Warning bandeau ── */
.cart-warning {
    background: #fef3c7; border: 1px solid #fbbf24; border-radius: 10px;
    padding: 8px 12px; margin-bottom: .75rem; font-size: .8rem; color: #92400e;
}

/* ── Toast notifications ── */
.toast-container {
    position: fixed; bottom: 100px; right: 28px; z-index: 9999;
    display: flex; flex-direction: column-reverse; gap: .5rem;
    pointer-events: none; max-width: 340px;
}
.toast {
    padding: 12px 18px; border-radius: 12px; color: #fff;
    font-size: .85rem; font-weight: 500; pointer-events: auto;
    box-shadow: 0 6px 20px rgba(0,0,0,.15);
    animation: toastIn .3s ease forwards;
    display: flex; align-items: center; gap: .5rem;
}
.toast.fade-out { animation: toastOut .3s ease forwards; }
.toast-success { background: #059669; }
.toast-error   { background: #dc2626; }
.toast-warning { background: #d97706; }
@keyframes toastIn  { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes toastOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(20px); } }

/* ── Livraison gratuite bar ── */
.free-shipping-bar {
    font-size: .75rem; color: #059669; margin-bottom: .5rem; text-align: center;
}
</style>

<!-- Bouton panier flottant -->
<button class="cart-float-btn" onclick="Cart.toggle()" id="cart-float-btn" title="Mon panier">
    <i class="bi bi-cart3"></i>
    <span class="cart-badge hidden" id="cart-badge">0</span>
</button>

<!-- Overlay -->
<div class="cart-overlay" id="cart-overlay" onclick="Cart.toggle()"></div>

<!-- Sidebar panier -->
<div class="cart-sidebar" id="cart-sidebar">
    <div class="cart-sidebar-header">
        <h3><i class="bi bi-cart3"></i> Mon panier (<span id="cart-header-count">0</span> articles)</h3>
        <button class="cart-sidebar-close" onclick="Cart.toggle()" title="Fermer">✕</button>
    </div>

    <div class="cart-items-wrap" id="cart-items">
        <div class="cart-empty-msg">Votre panier est vide.</div>
    </div>

    <div class="cart-sidebar-footer">
        <div id="cart-price-warning"></div>
        <div class="cart-totals">
            <div class="cart-total-row"><span>Sous-total HT</span><span id="cart-ht">0.00 DT</span></div>
            <div class="cart-total-row"><span>TVA (19%)</span><span id="cart-tva">0.00 DT</span></div>
            <div class="cart-total-row"><span>Livraison</span><span id="cart-livraison">15.00 DT</span></div>
            <div class="cart-total-row total-final"><span>Total TTC</span><span id="cart-ttc">0.00 DT</span></div>
        </div>
        <a href="index.php?action=checkout" class="btn-checkout" id="btn-checkout">
            <i class="bi bi-bag-check"></i> Commander le panier
        </a>
        <button class="btn-clear-cart" onclick="Cart.clear()">
            <i class="bi bi-trash3"></i> Vider le panier
        </button>
    </div>
</div>

<!-- Toasts container -->
<div class="toast-container" id="toast-container"></div>

<!-- Contenu catalogue -->
<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Catalogue des Pièces</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo $pieceCount; ?> pièce<?php echo $piecePlural; ?> disponible<?php echo $piecePlural; ?>
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

<!-- Filtres -->
<form method="GET" action="index.php" class="filter-panel catalog-filter-panel">
    <input type="hidden" name="action" value="showCatalogue">
    <div class="catalog-filter-grid">
        <div class="search-wrap search-wrap-wide">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="q" placeholder="Rechercher une pièce, marque ou référence..." value="<?php echo htmlspecialchars((string) ($paginationQuery['q'] ?? '')); ?>">
        </div>
        <select name="categorie">
            <option value="">Toutes les catégories</option>
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
            <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Réinitialiser</a>
        </div>
    </div>
</form>

<?php if (empty($pieces)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">Pièces</div>
        <h3>Aucune pièce trouvée</h3>
        <p>Aucune pièce ne correspond à vos critères.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Voir tout le catalogue</a>
            <a href="index.php?action=requestPiece&q=<?php echo urlencode((string) ($paginationQuery['q'] ?? '')); ?>" class="btn-sg btn-sg-primary">
                <i class="bi bi-send"></i> Demander cette pièce
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="piece-grid">
        <?php foreach ($pieces as $p):
            if ((int)$p['quantite_stock'] <= 0) { $stockClass = 'out-of-stock'; $stockLabel = 'Rupture'; }
            elseif ((int)$p['quantite_stock'] <= (int)$p['seuil_alerte']) { $stockClass = 'low-stock'; $stockLabel = 'Stock faible'; }
            else { $stockClass = 'in-stock'; $stockLabel = 'En stock'; }

            $catLower = strtolower(trim($p['categorie']));
            $badgeClass = 'badge-cat-default';
            if (strpos($catLower, 'frein') !== false) $badgeClass = 'badge-cat-freinage';
            elseif (strpos($catLower, 'filtr') !== false) $badgeClass = 'badge-cat-filtration';
            elseif (strpos($catLower, 'suspen') !== false) $badgeClass = 'badge-cat-suspension';
            elseif (strpos($catLower, 'moteur') !== false) $badgeClass = 'badge-cat-moteur';
            elseif (strpos($catLower, 'eclair') !== false || strpos($catLower, 'éclair') !== false) $badgeClass = 'badge-cat-eclairage';
            elseif (strpos($catLower, 'electr') !== false || strpos($catLower, 'électr') !== false) $badgeClass = 'badge-cat-electricite';
        ?>
            <div class="piece-card piece-card-rich" id="piece-card-<?php echo (int)$p['id_piece']; ?>" data-categorie="<?php echo htmlspecialchars($p['categorie']); ?>">
                <div class="piece-media">
                    <?php if (!empty($p['image'])): ?>
                        <img src="<?php echo htmlspecialchars((string) $p['image']); ?>" alt="<?php echo htmlspecialchars((string) $p['nom']); ?>">
                    <?php else: ?>
                        <div class="piece-media-fallback"><i class="bi bi-box-seam"></i></div>
                    <?php endif; ?>
                </div>

                <div class="pc-header">
                    <div>
                        <span class="badge-cat <?php echo $badgeClass; ?>"><?php echo htmlspecialchars((string) $p['categorie']); ?></span>
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
                        <span class="pc-detail-label">Stock</span>
                        <span class="pc-detail-value"><span class="badge-stock <?php echo $stockClass; ?>"><?php echo (int)$p['quantite_stock']; ?> - <?php echo $stockLabel; ?></span></span>
                    </div>
                </div>

                <div class="pc-footer">
                    <span class="pc-price"><?php echo number_format((float) $p['prix_unitaire'], 2, ',', ' '); ?> <small>DT</small></span>

                    <?php if ((int)$p['quantite_stock'] > 0): ?>
                        <div class="pc-actions">
                            <div class="qty-selector">
                                <button type="button" onclick="this.nextElementSibling.stepDown(); this.nextElementSibling.dispatchEvent(new Event('change'))">−</button>
                                <input type="number" id="qty-<?php echo (int)$p['id_piece']; ?>" value="1" min="1" max="<?php echo (int)$p['quantite_stock']; ?>">
                                <button type="button" onclick="this.previousElementSibling.stepUp(); this.previousElementSibling.dispatchEvent(new Event('change'))">+</button>
                            </div>
                            <button class="btn-add-cart" onclick="Cart.add(<?php echo (int)$p['id_piece']; ?>, document.getElementById('qty-<?php echo (int)$p['id_piece']; ?>').value, '<?php echo addslashes(htmlspecialchars($p['nom'])); ?>')">
                                <i class="bi bi-cart-plus"></i> Panier
                            </button>
                            <a href="index.php?action=orderPiece&id_piece=<?php echo (int)$p['id_piece']; ?>" class="btn-direct-order" title="Commander directement">
                                <i class="bi bi-lightning"></i> Direct
                            </a>
                        </div>
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

<script src="views/assets/js/cart.js"></script>

<?php
// Ouvrir automatiquement le panier si paramètre dans l'URL
if (isset($_GET['open_cart']) && $_GET['open_cart'] === '1'):
?>
<script>document.addEventListener('DOMContentLoaded', function(){ setTimeout(function(){ Cart.toggle(); }, 300); });</script>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
