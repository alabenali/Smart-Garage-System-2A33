<?php $pageTitle = 'Gestion des Pièces'; $action = 'managePieces'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Gestion des Pièces</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo count($pieces); ?> pièce<?php echo count($pieces) !== 1 ? 's' : ''; ?> dans le système
        </p>
    </div>
    <a href="index.php?action=addPiece" class="btn-sg btn-sg-primary">
        <i class="bi bi-plus-lg"></i> Ajouter une Pièce
    </a>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
<?php endif; ?>

<?php if (empty($pieces)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">📦</div>
        <h3>Aucune pièce trouvée</h3>
        <p>Aucune pièce n'est enregistrée dans le système.</p>
        <a href="index.php?action=addPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter une Pièce
        </a>
    </div>
<?php else: ?>
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-list-ul me-2"></i>Liste Complète</h3>
            <span style="color:var(--text-muted);font-size:0.85rem;"><?php echo count($pieces); ?> résultats</span>
        </div>
        <table class="sg-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Référence</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Marque</th>
                    <th>Prix (DT)</th>
                    <th>Stock</th>
                    <th>Date d'ajout</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pieces as $p): ?>
                    <tr>
                        <td style="color:var(--text-muted);">#<?php echo $p['id_piece']; ?></td>
                        <td><code style="color:var(--accent);background:var(--bg-secondary);padding:2px 8px;border-radius:4px;"><?php echo htmlspecialchars($p['reference']); ?></code></td>
                        <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                        <td><span class="badge-category"><?php echo htmlspecialchars($p['categorie']); ?></span></td>
                        <td><?php echo htmlspecialchars($p['marque']); ?></td>
                        <td class="badge-price"><?php echo number_format($p['prix_unitaire'], 2, ',', ' '); ?></td>
                        <td>
                            <?php
                            if ($p['quantite_stock'] <= 0) {
                                echo '<span class="badge-stock out-of-stock">' . $p['quantite_stock'] . ' – Rupture</span>';
                            } elseif ($p['quantite_stock'] <= $p['seuil_alerte']) {
                                echo '<span class="badge-stock low-stock">' . $p['quantite_stock'] . ' – Faible</span>';
                            } else {
                                echo '<span class="badge-stock in-stock">' . $p['quantite_stock'] . ' – OK</span>';
                            }
                            ?>
                        </td>
                        <td style="color:var(--text-secondary);font-size:0.82rem;"><?php echo date('d/m/Y', strtotime($p['date_ajout'])); ?></td>
                        <td>
                            <div class="btn-group-actions">
                                <a href="index.php?action=editPiece&id=<?php echo $p['id_piece']; ?>" class="btn-sg btn-sg-success btn-sg-sm" title="Modifier">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="index.php?action=confirmDeletePiece&id=<?php echo $p['id_piece']; ?>" class="btn-sg btn-sg-danger btn-sg-sm" title="Supprimer">
                                    <i class="bi bi-trash3"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
