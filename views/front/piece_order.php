<?php
$pageTitle = 'Commander une Piece';
$action = 'orderPiece';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="hero-panel">
    <div>
        <h1 class="page-title">Commander une Piece</h1>
        <p class="page-subtitle">Choisissez votre piece, la quantite et votre mode de paiement.</p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="sg-alert sg-alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?php echo $err; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="sg-form-wrap">
    <form method="POST" action="index.php?action=orderPiece" id="orderForm" novalidate onsubmit="return validateOrderForm(this);">
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="nom_client">Nom</label>
                <input type="text" name="nom_client" id="nom_client" placeholder="Ex: Ben Ahmed" value="<?php echo htmlspecialchars((string) ($old['nom_client'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="prenom_client">Prenom</label>
                <input type="text" name="prenom_client" id="prenom_client" placeholder="Ex: Karim" value="<?php echo htmlspecialchars((string) ($old['prenom_client'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="telephone">Telephone</label>
                <input type="text" name="telephone" id="telephone" placeholder="Ex: 98 765 432" value="<?php echo htmlspecialchars((string) ($old['telephone'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="id_piece">Piece a commander</label>
                <select name="id_piece" id="id_piece">
                    <option value="">-- Selectionner une piece --</option>
                    <?php foreach ($pieces as $p): ?>
                        <?php
                        $selected = (isset($old['id_piece']) && (int) $old['id_piece'] === (int) $p['id_piece']) ? 'selected' : '';
                        $stockInfo = (int) $p['quantite_stock'] > 0 ? '(Stock: ' . (int) $p['quantite_stock'] . ')' : '(Rupture)';
                        $disabled = (int) $p['quantite_stock'] <= 0 ? 'disabled' : '';
                        ?>
                        <option value="<?php echo (int) $p['id_piece']; ?>" data-prix="<?php echo htmlspecialchars((string) $p['prix_unitaire']); ?>" data-stock="<?php echo (int) $p['quantite_stock']; ?>" <?php echo $selected; ?> <?php echo $disabled; ?>>
                            <?php echo htmlspecialchars($p['nom'] . ' - ' . $p['marque'] . ' - ' . number_format((float) $p['prix_unitaire'], 2, ',', ' ') . ' DT ' . $stockInfo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="quantite">Quantite</label>
                <input type="text" name="quantite" id="quantite" placeholder="Ex: 2" value="<?php echo htmlspecialchars((string) ($old['quantite'] ?? '1')); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="montant_estime">Montant estime</label>
                <input type="text" id="montant_estime" readonly value="0,00 DT">
            </div>

            <div class="sg-form-group full-width">
                <label>Mode de paiement</label>
                <?php $selectedPayment = isset($old['payment_method']) ? (string) $old['payment_method'] : 'cash'; ?>
                <div class="payment-choice-grid">
                    <label class="payment-choice-card <?php echo $selectedPayment === 'cash' ? 'active' : ''; ?>">
                        <input type="radio" name="payment_method" value="cash" <?php echo $selectedPayment === 'cash' ? 'checked' : ''; ?>>
                        <span class="payment-choice-icon"><i class="bi bi-cash-coin"></i></span>
                        <span class="payment-choice-title">Paiement a la livraison</span>
                        <span class="payment-choice-text">La commande est enregistree tout de suite, puis vous reglez plus tard.</span>
                    </label>
                    <label class="payment-choice-card <?php echo $selectedPayment === 'konnect' ? 'active' : ''; ?>">
                        <input type="radio" name="payment_method" value="konnect" <?php echo $selectedPayment === 'konnect' ? 'checked' : ''; ?>>
                        <span class="payment-choice-icon"><i class="bi bi-credit-card"></i></span>
                        <span class="payment-choice-title">Konnect</span>
                        <span class="payment-choice-text">Paiement en ligne via Konnect. Vous serez redirige vers une page de paiement securisee.</span>
                    </label>
                </div>
                <div class="field-help">Le paiement Konnect utilise la devise configuree dans vos variables d'environnement.</div>
            </div>

            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-cart-check"></i> Continuer
                </button>
                <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pieceSelect = document.getElementById('id_piece');
    const quantiteInput = document.getElementById('quantite');
    const montantEstime = document.getElementById('montant_estime');
    const paymentCards = document.querySelectorAll('.payment-choice-card');

    function updateMontant() {
        const selectedOption = pieceSelect.options[pieceSelect.selectedIndex];
        const prix = selectedOption ? parseFloat(selectedOption.getAttribute('data-prix')) || 0 : 0;
        const qte = parseInt(quantiteInput.value, 10) || 0;
        const total = prix * qte;
        montantEstime.value = total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' DT';
    }

    function updatePaymentCards() {
        paymentCards.forEach(function(card) {
            const input = card.querySelector('input[type="radio"]');
            card.classList.toggle('active', !!input && input.checked);
        });
    }

    pieceSelect.addEventListener('change', updateMontant);
    quantiteInput.addEventListener('input', updateMontant);
    document.querySelectorAll('input[name="payment_method"]').forEach(function(input) {
        input.addEventListener('change', updatePaymentCards);
    });

    updateMontant();
    updatePaymentCards();
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
