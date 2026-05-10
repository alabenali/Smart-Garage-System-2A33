<?php $pageTitle = 'Confirmer la Suppression'; $action = 'confirmDeletePiece'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom: 2rem;">
    <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title" style="margin-bottom:0;">Confirmer la Suppression</h1>
    </div>
</div>

<div class="delete-confirm-card">
    <div class="delete-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <h2>Supprimer cette pièce ?</h2>
    <p class="delete-detail"><strong><?php echo htmlspecialchars($piece['nom']); ?></strong> – <?php echo htmlspecialchars($piece['marque']); ?></p>
    <div class="delete-ref"><?php echo htmlspecialchars($piece['reference']); ?></div>
    <p class="delete-detail">Catégorie : <?php echo htmlspecialchars($piece['categorie']); ?> | Stock : <?php echo $piece['quantite_stock']; ?> | Prix : <?php echo number_format($piece['prix_unitaire'], 2, ',', ' '); ?> DT</p>
    <p style="color:var(--danger);font-size:0.85rem;margin-bottom:1.5rem;">
        <i class="bi bi-info-circle me-1"></i> Cette action est irréversible. Toutes les données de cette pièce seront définitivement supprimées.
    </p>
    <div class="delete-actions">
        <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline">
            <i class="bi bi-x-lg"></i> Annuler
        </a>
        <a href="index.php?action=deletePiece&id=<?php echo $piece['id_piece']; ?>" class="btn-sg btn-sg-danger">
            <i class="bi bi-trash3"></i> Oui, Supprimer
        </a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
