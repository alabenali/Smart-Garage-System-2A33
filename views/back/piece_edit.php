<?php $pageTitle = 'Modifier la Pièce'; $action = 'editPiece'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom: 2rem;">
    <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title" style="margin-bottom:0.1rem;">Modifier la Pièce</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo htmlspecialchars($piece['nom']); ?> – <code style="color:var(--accent);"><?php echo htmlspecialchars($piece['reference']); ?></code>
        </p>
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
    <form method="POST" action="index.php?action=editPiece&id=<?php echo $piece['id_piece']; ?>" id="pieceForm" novalidate
          onsubmit="return validatePieceForm(this);">

        <div class="sg-form-grid">
            <!-- Référence -->
            <div class="sg-form-group">
                <label for="reference">Référence</label>
                <input type="text" name="reference" id="reference" placeholder="Ex: PLQ-BRK-001"
                       value="<?php echo htmlspecialchars($piece['reference']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Nom -->
            <div class="sg-form-group">
                <label for="nom">Nom de la Pièce</label>
                <input type="text" name="nom" id="nom" placeholder="Ex: Plaquette de frein avant"
                       value="<?php echo htmlspecialchars($piece['nom']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Catégorie -->
            <div class="sg-form-group">
                <label for="categorie">Catégorie</label>
                <select name="categorie" id="categorie">
                    <option value="">-- Sélectionner --</option>
                    <?php
                    $categories = ['Freinage', 'Filtration', 'Allumage', 'Suspension', 'Distribution', 'Électricité', 'Éclairage', 'Lubrification', 'Refroidissement', 'Transmission', 'Carrosserie', 'Autre'];
                    foreach ($categories as $cat):
                        $selected = ($piece['categorie'] === $cat) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $cat; ?>" <?php echo $selected; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <!-- Marque -->
            <div class="sg-form-group">
                <label for="marque">Marque</label>
                <input type="text" name="marque" id="marque" placeholder="Ex: Bosch"
                       value="<?php echo htmlspecialchars($piece['marque']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Prix Unitaire -->
            <div class="sg-form-group">
                <label for="prix_unitaire">Prix Unitaire (DT)</label>
                <input type="text" name="prix_unitaire" id="prix_unitaire" placeholder="Ex: 45.90"
                       value="<?php echo htmlspecialchars($piece['prix_unitaire']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Quantité Stock -->
            <div class="sg-form-group">
                <label for="quantite_stock">Quantité en Stock</label>
                <input type="text" name="quantite_stock" id="quantite_stock" placeholder="Ex: 25"
                       value="<?php echo htmlspecialchars($piece['quantite_stock']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Seuil d'Alerte -->
            <div class="sg-form-group">
                <label for="seuil_alerte">Seuil d'Alerte</label>
                <input type="text" name="seuil_alerte" id="seuil_alerte" placeholder="Ex: 5"
                       value="<?php echo htmlspecialchars($piece['seuil_alerte']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Description -->
            <div class="sg-form-group full-width">
                <label for="description">Description <span style="color:var(--text-muted);font-weight:400;">(optionnelle)</span></label>
                <textarea name="description" id="description" rows="3" placeholder="Description de la pièce..."><?php echo htmlspecialchars($piece['description'] ?? ''); ?></textarea>
                <div class="invalid-feedback"></div>
            </div>

            <!-- Submit -->
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-check-lg"></i> Sauvegarder les Modifications
                </button>
                <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline">Annuler</a>
                <a href="index.php?action=confirmDeletePiece&id=<?php echo $piece['id_piece']; ?>" class="btn-sg btn-sg-danger" style="margin-left:auto;">
                    <i class="bi bi-trash3"></i> Supprimer
                </a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
