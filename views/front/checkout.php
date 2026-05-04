<?php
$pageTitle = $pageTitle ?? 'Finaliser la commande';
$action = $action ?? 'checkout';
$errors = $errors ?? [];
$old = $old ?? [];
$cartSummary = $cartSummary ?? ['items' => [], 'nb_articles' => 0, 'sous_total_ht' => 0, 'tva' => 0, 'frais_livraison' => 0, 'total_ttc' => 0, 'has_price_changes' => false];
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
/* ── Layout checkout 2 colonnes ── */
.checkout-layout {
    display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; margin-top: 1.5rem;
}
@media (max-width: 900px) { .checkout-layout { grid-template-columns: 1fr; } }

.checkout-panel {
    background: var(--sg-card-bg, #fff);
    border: 1px solid var(--sg-border, #e2e8f0);
    border-radius: 18px; padding: 1.5rem;
}
.checkout-panel h2 { margin: 0 0 1.25rem; font-size: 1.15rem; }

/* ── Items recap ── */
.checkout-item {
    display: flex; align-items: center; gap: 1rem;
    padding: .75rem 0; border-bottom: 1px solid var(--sg-border-light, #f1f5f9);
}
.checkout-item:last-child { border-bottom: none; }
.checkout-item-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; background: var(--sg-bg-subtle, #f8fafc); }
.checkout-item-img-fallback {
    width: 50px; height: 50px; border-radius: 10px; background: var(--sg-bg-subtle, #f8fafc);
    display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--sg-text-muted, #94a3b8);
}
.checkout-item-info { flex: 1; min-width: 0; }
.checkout-item-name { font-weight: 600; font-size: .9rem; }
.checkout-item-meta { font-size: .78rem; color: var(--sg-text-muted, #94a3b8); }
.checkout-item-right { text-align: right; }
.checkout-item-price { font-weight: 700; font-size: .9rem; color: var(--sg-primary, #173252); }
.checkout-item-qty { font-size: .78rem; color: var(--sg-text-muted, #94a3b8); }

/* ── Totaux ── */
.checkout-totals {
    border-top: 2px solid var(--sg-border, #e2e8f0);
    margin-top: 1rem; padding-top: 1rem;
}
.checkout-total-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: .9rem; }
.checkout-total-row.final { font-weight: 700; font-size: 1.15rem; border-top: 2px solid var(--sg-primary, #173252); padding-top: .75rem; margin-top: .5rem; color: var(--sg-primary, #173252); }

/* ── Warning prix ── */
.checkout-price-warning {
    background: #fef3c7; border: 1px solid #fbbf24; border-radius: 10px;
    padding: 10px 14px; margin-bottom: 1rem; font-size: .82rem; color: #92400e;
    display: flex; align-items: center; gap: .5rem;
}

/* ── Formulaire client ── */
.checkout-form .form-group { margin-bottom: 1rem; }
.checkout-form label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: 4px; color: var(--sg-text, #334155); }
.checkout-form input[type="text"],
.checkout-form input[type="email"],
.checkout-form input[type="tel"],
.checkout-form textarea {
    width: 100%; padding: 10px 14px; border: 1px solid var(--sg-border, #e2e8f0);
    border-radius: 10px; font-size: .9rem; background: var(--sg-bg-subtle, #f8fafc);
    transition: border-color .15s;
}
.checkout-form input:focus, .checkout-form textarea:focus {
    border-color: var(--sg-primary, #173252); outline: none;
    box-shadow: 0 0 0 3px rgba(23,50,82,.08);
}
.checkout-form textarea { resize: vertical; min-height: 60px; }

/* ── Radio paiement ── */
.payment-options { display: flex; flex-direction: column; gap: .5rem; }
.payment-option {
    display: flex; align-items: center; gap: .75rem;
    padding: 12px 14px; border: 1px solid var(--sg-border, #e2e8f0);
    border-radius: 12px; cursor: pointer; transition: border-color .15s, background .15s;
}
.payment-option:hover { border-color: var(--sg-primary, #173252); }
.payment-option input[type="radio"] { accent-color: var(--sg-primary, #173252); }
.payment-option.selected { border-color: var(--sg-primary, #173252); background: rgba(23,50,82,.04); }
.payment-option-label { font-weight: 600; font-size: .9rem; }
.payment-option-desc { font-size: .78rem; color: var(--sg-text-muted, #94a3b8); }

/* ── Bouton confirmer ── */
.btn-confirm-order {
    display: block; width: 100%; padding: 14px; border: none; border-radius: 14px;
    background: linear-gradient(135deg, #173252, #c43d2f); color: #fff;
    font-size: 1.05rem; font-weight: 700; cursor: pointer; margin-top: 1.5rem;
    transition: opacity .2s, transform .15s;
}
.btn-confirm-order:hover { opacity: .92; transform: translateY(-1px); }
.btn-confirm-order:disabled { opacity: .5; cursor: not-allowed; }
.confirm-sub-text { text-align: center; font-size: .78rem; color: var(--sg-text-muted, #94a3b8); margin-top: .5rem; }

/* ── Erreurs ── */
.checkout-errors {
    background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px;
    padding: 12px 16px; margin-bottom: 1rem;
}
.checkout-errors li { font-size: .85rem; color: #dc2626; margin-bottom: 4px; }
</style>

<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Finaliser la commande</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo (int)$cartSummary['nb_articles']; ?> article<?php echo (int)$cartSummary['nb_articles'] !== 1 ? 's' : ''; ?> dans votre panier
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=showCatalogue&open_cart=1" class="btn-sg btn-sg-outline">
            <i class="bi bi-pencil"></i> Modifier le panier
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <ul class="checkout-errors">
        <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div class="checkout-layout">
    <!-- Colonne gauche : Récapitulatif -->
    <div class="checkout-panel">
        <h2><i class="bi bi-receipt"></i> Récapitulatif de commande</h2>

        <?php if ($cartSummary['has_price_changes']): ?>
            <div class="checkout-price-warning">
                <i class="bi bi-exclamation-triangle"></i>
                Certains prix ont été mis à jour depuis l'ajout au panier.
            </div>
        <?php endif; ?>

        <?php foreach ($cartSummary['items'] as $item): ?>
            <div class="checkout-item">
                <?php if (!empty($item['image'])): ?>
                    <img class="checkout-item-img" src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>">
                <?php else: ?>
                    <div class="checkout-item-img-fallback"><i class="bi bi-box-seam"></i></div>
                <?php endif; ?>
                <div class="checkout-item-info">
                    <div class="checkout-item-name"><?php echo htmlspecialchars($item['nom']); ?></div>
                    <div class="checkout-item-meta"><?php echo htmlspecialchars($item['marque']); ?> · <?php echo htmlspecialchars($item['reference']); ?></div>
                    <?php if ($item['prix_a_change']): ?>
                        <div style="color:#d97706; font-size:.75rem;">⚠ Prix mis à jour : <?php echo number_format((float)$item['prix_actuel'], 2, ',', ' '); ?> DT (ancien : <?php echo number_format((float)$item['prix_snapshot'], 2, ',', ' '); ?> DT)</div>
                    <?php endif; ?>
                </div>
                <div class="checkout-item-right">
                    <div class="checkout-item-price"><?php echo number_format((float)$item['sous_total'], 2, ',', ' '); ?> DT</div>
                    <div class="checkout-item-qty"><?php echo (int)$item['quantite']; ?> × <?php echo number_format((float)$item['prix_snapshot'], 2, ',', ' '); ?> DT</div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="checkout-totals">
            <div class="checkout-total-row"><span>Sous-total HT</span><span><?php echo number_format($cartSummary['sous_total_ht'], 2, ',', ' '); ?> DT</span></div>
            <div class="checkout-total-row"><span>TVA (19%)</span><span><?php echo number_format($cartSummary['tva'], 2, ',', ' '); ?> DT</span></div>
            <div class="checkout-total-row"><span>Frais de livraison</span><span><?php echo $cartSummary['frais_livraison'] > 0 ? number_format($cartSummary['frais_livraison'], 2, ',', ' ') . ' DT' : '<span style="color:#059669;font-weight:600">Gratuit</span>'; ?></span></div>
            <div class="checkout-total-row final"><span>Total TTC</span><span><?php echo number_format($cartSummary['total_ttc'], 2, ',', ' '); ?> DT</span></div>
        </div>
    </div>

    <!-- Colonne droite : Formulaire -->
    <div class="checkout-panel">
        <h2><i class="bi bi-person"></i> Vos informations</h2>

        <form method="POST" action="index.php?action=confirmOrder" class="checkout-form" id="checkout-form">
            <div class="form-group">
                <label for="nom_client">Nom complet *</label>
                <input type="text" id="nom_client" name="nom_client" required placeholder="Votre nom complet" value="<?php echo htmlspecialchars($old['nom_client'] ?? ''); ?>">
            </div>

            <div style="display:none;">
                <input type="text" name="prenom_client" value="<?php echo htmlspecialchars($old['prenom_client'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone *</label>
                <input type="tel" id="telephone" name="telephone" required placeholder="ex: 98765432" value="<?php echo htmlspecialchars($old['telephone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email (optionnel)</label>
                <input type="email" id="email" name="email" placeholder="votre@email.com" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="adresse">Adresse de livraison (optionnel)</label>
                <input type="text" id="adresse" name="adresse" placeholder="Votre adresse pour la livraison" value="<?php echo htmlspecialchars($old['adresse'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Mode de paiement</label>
                <div class="payment-options">
                    <label class="payment-option <?php echo ($old['payment_method'] ?? 'cash') !== 'konnect' ? 'selected' : ''; ?>">
                        <input type="radio" name="payment_method" value="cash" <?php echo ($old['payment_method'] ?? 'cash') !== 'konnect' ? 'checked' : ''; ?>>
                        <div>
                            <div class="payment-option-label"><i class="bi bi-cash-coin"></i> Paiement à la remise</div>
                            <div class="payment-option-desc">Payez en espèces à la réception</div>
                        </div>
                    </label>
                    <label class="payment-option <?php echo ($old['payment_method'] ?? '') === 'konnect' ? 'selected' : ''; ?>">
                        <input type="radio" name="payment_method" value="konnect" <?php echo ($old['payment_method'] ?? '') === 'konnect' ? 'checked' : ''; ?>>
                        <div>
                            <div class="payment-option-label"><i class="bi bi-credit-card"></i> Paiement en ligne (Konnect)</div>
                            <div class="payment-option-desc">Carte bancaire, e-DINAR, wallet</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="note">Note (optionnel)</label>
                <textarea id="note" name="note" placeholder="Instructions spéciales, commentaires..."><?php echo htmlspecialchars($old['note'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn-confirm-order" id="btn-confirm">
                <i class="bi bi-check-circle"></i> Confirmer ma commande (<?php echo number_format($cartSummary['total_ttc'], 2, ',', ' '); ?> DT)
            </button>
            <p class="confirm-sub-text"><i class="bi bi-chat-dots"></i> En confirmant, vous recevrez un SMS de confirmation</p>
        </form>
    </div>
</div>

<script>
// Gestion visuelle des radios paiement
document.querySelectorAll('.payment-option input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-option').forEach(function(opt) { opt.classList.remove('selected'); });
        this.closest('.payment-option').classList.add('selected');
    });
});

// Anti double-clic
document.getElementById('checkout-form').addEventListener('submit', function() {
    var btn = document.getElementById('btn-confirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Traitement en cours...';
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
