// ============================================
// Cart.js – Panier multi-achats (Vanilla JS)
// ============================================

const Cart = {

    _debounceTimers: {},

    // ── Ajouter au panier ──
    add(id_piece, quantite, nom_piece) {
        const formData = new FormData();
        formData.append('id_piece', id_piece);
        formData.append('quantite', quantite);

        fetch('index.php?action=addToCart', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Cart.updateBadge(data.cart_count);
                Cart.showToast(nom_piece + ' ajouté au panier', 'success');
                Cart._flyToCart(id_piece);
                // Refresh sidebar si ouverte
                if (document.getElementById('cart-sidebar').classList.contains('open')) {
                    Cart.refresh();
                }
            } else {
                Cart.showToast(data.message, 'error');
            }
        })
        .catch(err => {
            Cart.showToast('Erreur de connexion', 'error');
            console.error('Cart.add error:', err);
        });
    },

    // ── Retirer du panier ──
    remove(id_piece) {
        const formData = new FormData();
        formData.append('id_piece', id_piece);

        // Animation de suppression
        const itemEl = document.querySelector('[data-cart-item="' + id_piece + '"]');
        if (itemEl) {
            itemEl.style.opacity = '0';
            itemEl.style.maxHeight = '0';
            itemEl.style.overflow = 'hidden';
            itemEl.style.transition = 'opacity .3s, max-height .3s, padding .3s';
            itemEl.style.padding = '0';
        }

        fetch('index.php?action=removeFromCart', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Cart.updateBadge(data.cart_count);
                Cart.refresh();
            } else {
                Cart.showToast(data.message, 'error');
            }
        })
        .catch(err => {
            Cart.showToast('Erreur de connexion', 'error');
            console.error('Cart.remove error:', err);
        });
    },

    // ── Modifier la quantité (debounce 300ms) ──
    updateQty(id_piece, nouvelle_quantite) {
        clearTimeout(Cart._debounceTimers[id_piece]);
        Cart._debounceTimers[id_piece] = setTimeout(function() {
            const formData = new FormData();
            formData.append('id_piece', id_piece);
            formData.append('quantite', nouvelle_quantite);

            fetch('index.php?action=updateQty', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Cart.updateBadge(data.cart_count);
                    Cart.refresh();
                } else {
                    Cart.showToast(data.message, 'error');
                    Cart.refresh();
                }
            })
            .catch(err => {
                Cart.showToast('Erreur de connexion', 'error');
                console.error('Cart.updateQty error:', err);
            });
        }, 300);
    },

    // ── Vider le panier ──
    clear() {
        if (!confirm('Vider le panier ? Cette action est irréversible.')) return;

        fetch('index.php?action=clearCart', {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Cart.updateBadge(0);
                Cart.refresh();
                Cart.showToast('Panier vidé', 'success');
            }
        })
        .catch(err => {
            Cart.showToast('Erreur de connexion', 'error');
        });
    },

    // ── Ouvrir/fermer la sidebar ──
    toggle() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');

        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        } else {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
            Cart.refresh();
        }
    },

    // ── Rafraîchir le contenu du panier ──
    refresh() {
        fetch('index.php?action=getCart')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            const d = data.data;
            const itemsWrap = document.getElementById('cart-items');
            const headerCount = document.getElementById('cart-header-count');

            // Mettre à jour le badge et le compteur header
            Cart.updateBadge(data.cart_count);
            if (headerCount) headerCount.textContent = d.nb_articles;

            // Rendu des items
            if (!d.items || d.items.length === 0) {
                itemsWrap.innerHTML = '<div class="cart-empty-msg"><i class="bi bi-cart-x" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>Votre panier est vide.</div>';
            } else {
                itemsWrap.innerHTML = Cart.renderItems(d.items);
            }

            // Mise à jour des totaux
            document.getElementById('cart-ht').textContent = Cart._formatPrice(d.sous_total_ht);
            document.getElementById('cart-tva').textContent = Cart._formatPrice(d.tva);
            document.getElementById('cart-livraison').textContent = d.frais_livraison > 0 ? Cart._formatPrice(d.frais_livraison) : 'Gratuit';
            document.getElementById('cart-ttc').textContent = Cart._formatPrice(d.total_ttc);

            // Warning prix
            const warningEl = document.getElementById('cart-price-warning');
            if (warningEl) {
                warningEl.innerHTML = d.has_price_changes
                    ? '<div class="cart-warning"><i class="bi bi-exclamation-triangle"></i> Certains prix ont été mis à jour.</div>'
                    : '';
            }

            // Livraison gratuite info
            if (d.sous_total_ht > 0 && d.frais_livraison === 0) {
                document.getElementById('cart-livraison').innerHTML = '<span style="color:#059669;font-weight:600;">Gratuit ✓</span>';
            }
        })
        .catch(err => {
            console.error('Cart.refresh error:', err);
        });
    },

    // ── Rendu HTML des items sidebar ──
    renderItems(items) {
        let html = '';
        items.forEach(function(item) {
            const imgHtml = item.image
                ? '<img class="cart-item-img" src="' + Cart._esc(item.image) + '" alt="' + Cart._esc(item.nom) + '">'
                : '<div class="cart-item-img-fallback"><i class="bi bi-box-seam"></i></div>';

            const priceWarning = item.prix_a_change
                ? '<div class="cart-price-changed">⚠ Prix mis à jour</div>'
                : '';

            html += '<div class="cart-item" data-cart-item="' + item.id_piece + '">'
                + imgHtml
                + '<div class="cart-item-info">'
                + '<div class="cart-item-name">' + Cart._esc(item.nom) + '</div>'
                + '<div class="cart-item-brand">' + Cart._esc(item.marque) + '</div>'
                + priceWarning
                + '<div class="cart-item-qty">'
                + '<button onclick="Cart.updateQty(' + item.id_piece + ',' + (parseInt(item.quantite) - 1) + ')">−</button>'
                + '<span>' + item.quantite + '</span>'
                + '<button onclick="Cart.updateQty(' + item.id_piece + ',' + (parseInt(item.quantite) + 1) + ')">+</button>'
                + '</div>'
                + '</div>'
                + '<div style="text-align:right;">'
                + '<div class="cart-item-price">' + Cart._formatPrice(item.sous_total) + '</div>'
                + '<button class="cart-item-remove" onclick="Cart.remove(' + item.id_piece + ')" title="Retirer">'
                + '<i class="bi bi-trash3"></i>'
                + '</button>'
                + '</div>'
                + '</div>';
        });
        return html;
    },

    // ── Mise à jour du badge ──
    updateBadge(count) {
        const badge = document.getElementById('cart-badge');
        if (!badge) return;

        count = parseInt(count) || 0;
        badge.textContent = count;

        if (count > 0) {
            badge.classList.remove('hidden');
            badge.classList.remove('bounce');
            // Force reflow pour relancer l'animation
            void badge.offsetWidth;
            badge.classList.add('bounce');
        } else {
            badge.classList.add('hidden');
        }
    },

    // ── Toast notifications ──
    showToast(message, type) {
        type = type || 'success';
        const container = document.getElementById('toast-container');
        if (!container) return;

        // Max 3 toasts (FIFO)
        while (container.children.length >= 3) {
            container.removeChild(container.firstChild);
        }

        const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill' };
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = '<i class="bi ' + (icons[type] || icons.success) + '"></i> ' + Cart._esc(message);
        container.appendChild(toast);

        // Auto-remove après 3s
        setTimeout(function() {
            toast.classList.add('fade-out');
            setTimeout(function() {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 3000);
    },

    // ── Initialisation ──
    init() {
        // Charger le compteur du panier
        fetch('index.php?action=getCart')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Cart.updateBadge(data.cart_count);
            }
        })
        .catch(function() {});
    },

    // ── Animation "vol" vers le panier ──
    _flyToCart(id_piece) {
        const card = document.getElementById('piece-card-' + id_piece);
        const cartBtn = document.getElementById('cart-float-btn');
        if (!card || !cartBtn) return;

        const cardRect = card.getBoundingClientRect();
        const cartRect = cartBtn.getBoundingClientRect();

        const flyEl = document.createElement('div');
        flyEl.style.cssText = 'position:fixed;z-index:9999;width:50px;height:50px;border-radius:50%;'
            + 'background:linear-gradient(135deg,#173252,#c43d2f);opacity:.8;pointer-events:none;'
            + 'left:' + (cardRect.left + cardRect.width / 2 - 25) + 'px;'
            + 'top:' + (cardRect.top + cardRect.height / 2 - 25) + 'px;'
            + 'transition:all .6s cubic-bezier(.25,.46,.45,.94);';
        document.body.appendChild(flyEl);

        requestAnimationFrame(function() {
            flyEl.style.left = (cartRect.left + cartRect.width / 2 - 15) + 'px';
            flyEl.style.top = (cartRect.top + cartRect.height / 2 - 15) + 'px';
            flyEl.style.width = '30px';
            flyEl.style.height = '30px';
            flyEl.style.opacity = '0';
        });

        setTimeout(function() {
            if (flyEl.parentNode) flyEl.parentNode.removeChild(flyEl);
        }, 700);
    },

    // ── Helpers ──
    _formatPrice(val) {
        return parseFloat(val || 0).toFixed(2).replace('.', ',') + ' DT';
    },

    _esc(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

document.addEventListener('DOMContentLoaded', function() { Cart.init(); });
