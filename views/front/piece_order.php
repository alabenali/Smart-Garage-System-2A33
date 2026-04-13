<?php
// Variables used by shared layout and active navbar item.
$pageTitle = 'Commander une Pièce';
$action = 'orderPiece';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<h1 class="page-title">Commander une Pièce</h1>
<p class="page-subtitle">Remplissez le formulaire ci-dessous pour passer votre commande.</p>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <!-- Server-side validation errors are shown here -->
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
    <form method="POST" action="index.php?action=orderPiece" id="orderForm" novalidate
          onsubmit="return validateOrderForm(this);">

        <div class="sg-form-grid">
            <!-- Nom du client -->
            <div class="sg-form-group">
                <label for="nom_client">Nom</label>
                <!-- Keep previously entered value after a failed submit -->
                <?php $oldNom = isset($old['nom_client']) ? $old['nom_client'] : ''; ?>
                <input type="text" name="nom_client" id="nom_client" placeholder="Ex: Ben Ahmed"
                       value="<?php echo htmlspecialchars($oldNom); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Prénom du client -->
            <div class="sg-form-group">
                <label for="prenom_client">Prénom</label>
                <!-- Keep previously entered value after a failed submit -->
                <?php $oldPrenom = isset($old['prenom_client']) ? $old['prenom_client'] : ''; ?>
                <input type="text" name="prenom_client" id="prenom_client" placeholder="Ex: Karim"
                       value="<?php echo htmlspecialchars($oldPrenom); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Téléphone -->
            <div class="sg-form-group">
                <label for="telephone">Téléphone</label>
                <!-- Keep previously entered value after a failed submit -->
                <?php $oldTelephone = isset($old['telephone']) ? $old['telephone'] : ''; ?>
                <input type="text" name="telephone" id="telephone" placeholder="Ex: 98 765 432"
                       value="<?php echo htmlspecialchars($oldTelephone); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Pièce à commander -->
            <div class="sg-form-group">
                <label for="id_piece">Pièce à commander</label>
                <select name="id_piece" id="id_piece">
                    <option value="">-- Sélectionner une pièce --</option>
                    <?php foreach ($pieces as $p):
                        // Prepare option state and stock label.
                        $selected = (isset($old['id_piece']) && (int)$old['id_piece'] === (int)$p['id_piece']) ? 'selected' : '';
                        $stockInfo = $p['quantite_stock'] > 0 ? '(Stock: ' . $p['quantite_stock'] . ')' : '(Rupture)';
                        $disabled = $p['quantite_stock'] <= 0 ? 'disabled' : '';
                    ?>
                        <option value="<?php echo $p['id_piece']; ?>"
                                data-prix="<?php echo $p['prix_unitaire']; ?>"
                                data-stock="<?php echo $p['quantite_stock']; ?>"
                                <?php echo $selected; ?> <?php echo $disabled; ?>>
                            <?php echo htmlspecialchars($p['nom'] . ' – ' . $p['marque'] . ' – ' . number_format($p['prix_unitaire'], 2, ',', ' ') . ' DT ' . $stockInfo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <!-- Quantité -->
            <div class="sg-form-group">
                <label for="quantite">Quantité</label>
                <!-- Default quantity = 1 for easier first order -->
                <?php $oldQuantite = isset($old['quantite']) ? $old['quantite'] : '1'; ?>
                <input type="text" name="quantite" id="quantite" placeholder="Ex: 2"
                       value="<?php echo htmlspecialchars($oldQuantite); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Montant estimé (read-only, dynamic) -->
            <div class="sg-form-group">
                <label for="montant_estime">Montant Estimé</label>
                <input type="text" id="montant_estime" readonly
                       style="background: rgba(212,175,55,0.08); color: var(--gold); font-weight: 600; cursor: default;"
                       value="0,00 DT">
            </div>

            <!-- Submit -->
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-cart-check"></i> Passer la Commande
                </button>
                <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<!-- Dynamic price calculation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pieceSelect = document.getElementById('id_piece');
    const quantiteInput = document.getElementById('quantite');
    const montantEstime = document.getElementById('montant_estime');

    function updateMontant() {
        // Read selected piece price and multiply by typed quantity.
        const selectedOption = pieceSelect.options[pieceSelect.selectedIndex];
        const prix = selectedOption ? parseFloat(selectedOption.getAttribute('data-prix')) || 0 : 0;
        const qte = parseInt(quantiteInput.value) || 0;
        const total = prix * qte;
        // Format like: 1 234,56 DT
        montantEstime.value = total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' DT';
    }

    pieceSelect.addEventListener('change', updateMontant);
    quantiteInput.addEventListener('input', updateMontant);

    // Initial calculation
    updateMontant();
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
