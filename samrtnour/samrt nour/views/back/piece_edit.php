<?php $pageTitle = 'Modifier la Piece'; $action = 'editPiece'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom: 2rem;">
    <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title" style="margin-bottom:0.1rem;">Modifier la Piece</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo htmlspecialchars((string) $piece['nom']); ?> - <code class="code-chip"><?php echo htmlspecialchars((string) $piece['reference']); ?></code>
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
    <form method="POST" action="index.php?action=editPiece&id=<?php echo (int) $piece['id_piece']; ?>" id="pieceForm" enctype="multipart/form-data" novalidate onsubmit="return validatePieceForm(this);">
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="reference">Reference</label>
                <input type="text" name="reference" id="reference" placeholder="Ex: PLQ-BRK-001" value="<?php echo htmlspecialchars((string) $piece['reference']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="nom">Nom de la piece</label>
                <input type="text" name="nom" id="nom" placeholder="Ex: Plaquette de frein avant" value="<?php echo htmlspecialchars((string) $piece['nom']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="categorie">Categorie</label>
                <select name="categorie" id="categorie">
                    <option value="">-- Selectionner --</option>
                    <?php
                    $categories = ['Freinage', 'Filtration', 'Allumage', 'Suspension', 'Distribution', 'Electricite', 'Eclairage', 'Lubrification', 'Refroidissement', 'Transmission', 'Carrosserie', 'Autre'];
                    foreach ($categories as $cat):
                        $selected = ($piece['categorie'] === $cat) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="marque">Marque</label>
                <input type="text" name="marque" id="marque" placeholder="Ex: Bosch" value="<?php echo htmlspecialchars((string) $piece['marque']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="prix_unitaire">Prix unitaire (DT)</label>
                <input type="text" name="prix_unitaire" id="prix_unitaire" placeholder="Ex: 45.90" value="<?php echo htmlspecialchars((string) $piece['prix_unitaire']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="quantite_stock">Quantite en stock</label>
                <input type="text" name="quantite_stock" id="quantite_stock" placeholder="Ex: 25" value="<?php echo htmlspecialchars((string) $piece['quantite_stock']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="seuil_alerte">Seuil d'alerte</label>
                <input type="text" name="seuil_alerte" id="seuil_alerte" placeholder="Ex: 5" value="<?php echo htmlspecialchars((string) $piece['seuil_alerte']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="image_file">Remplacer l'image</label>
                <input type="file" name="image_file" id="image_file" accept="image/png,image/jpeg,image/gif,image/webp">
                <div class="field-help">Formats acceptes: JPG, PNG, GIF, WEBP - 4 Mo max.</div>
            </div>

            <div class="sg-form-group full-width">
                <label>Image actuelle</label>
                <div class="image-editor-panel">
                    <div class="image-editor-preview">
                        <?php if (!empty($piece['image'])): ?>
                            <img src="<?php echo htmlspecialchars((string) $piece['image']); ?>" alt="<?php echo htmlspecialchars((string) $piece['nom']); ?>">
                        <?php else: ?>
                            <div class="piece-media-fallback"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="remove_image" value="1">
                        Supprimer l'image actuelle
                    </label>
                </div>
            </div>

            <div class="sg-form-group full-width">
                <label for="description">Description <span style="color:var(--text-muted);font-weight:400;">(optionnelle)</span></label>
                <textarea name="description" id="description" rows="3" placeholder="Description de la piece..."><?php echo htmlspecialchars((string) ($piece['description'] ?? '')); ?></textarea>
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-check-lg"></i> Sauvegarder les modifications
                </button>
                <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline">Annuler</a>
                <a href="index.php?action=confirmDeletePiece&id=<?php echo (int) $piece['id_piece']; ?>" class="btn-sg btn-sg-danger" style="margin-left:auto;">
                    <i class="bi bi-trash3"></i> Supprimer
                </a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
