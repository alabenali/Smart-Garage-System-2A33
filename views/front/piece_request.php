<?php
$pageTitle = 'Demander une pièce';
$action = 'requestPiece';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom: 1.5rem;">
    <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour au catalogue</a>
</div>

<h1 class="page-title">Demande de pièce introuvable</h1>
<p class="page-subtitle">Vous ne trouvez pas votre pièce dans le catalogue ? Remplissez ce formulaire, nous reviendrons vers vous.</p>

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
    <form method="POST" action="index.php?action=requestPiece" id="missingPieceForm" novalidate>
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="nom_client">Nom</label>
                <input type="text" name="nom_client" id="nom_client" placeholder="Ex: Ben Ahmed" value="<?php echo htmlspecialchars($old['nom_client'] ?? ''); ?>">
            </div>

            <div class="sg-form-group">
                <label for="prenom_client">Prénom</label>
                <input type="text" name="prenom_client" id="prenom_client" placeholder="Ex: Karim" value="<?php echo htmlspecialchars($old['prenom_client'] ?? ''); ?>">
            </div>

            <div class="sg-form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" name="telephone" id="telephone" placeholder="Ex: 98 765 432" value="<?php echo htmlspecialchars($old['telephone'] ?? ''); ?>">
            </div>

            <div class="sg-form-group">
                <label for="quantite">Quantité souhaitée</label>
                <input type="text" name="quantite" id="quantite" placeholder="Ex: 2" value="<?php echo htmlspecialchars((string)($old['quantite'] ?? '1')); ?>">
            </div>

            <div class="sg-form-group full-width">
                <label for="nom_piece">Nom de la pièce recherchée</label>
                <input type="text" name="nom_piece" id="nom_piece" placeholder="Ex: Alternateur Peugeot 208" value="<?php echo htmlspecialchars($old['nom_piece'] ?? ''); ?>">
            </div>

            <div class="sg-form-group full-width">
                <label for="marque">Marque / modèle (optionnel)</label>
                <input type="text" name="marque" id="marque" placeholder="Ex: Bosch / Peugeot 208" value="<?php echo htmlspecialchars($old['marque'] ?? ''); ?>">
            </div>

            <div class="sg-form-group full-width">
                <label for="description">Précisions supplémentaires (optionnel)</label>
                <textarea name="description" id="description" rows="4" placeholder="Référence constructeur, année, motorisation, etc."><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
            </div>

            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-send-check"></i> Envoyer la demande
                </button>
                <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
