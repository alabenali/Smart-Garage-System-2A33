<?php $pageTitle = 'Gestion des Pieces'; $action = 'managePieces'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
@keyframes pulse-danger {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}
@keyframes pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.6); }
    70% { box-shadow: 0 0 0 10px rgba(245, 158, 11, 0); }
    100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}
.row-danger {
    background-color: rgba(239, 68, 68, 0.05) !important;
    border-left: 3px solid #ef4444 !important;
}
.row-warning {
    background-color: rgba(245, 158, 11, 0.05) !important;
    border-left: 3px solid #f59e0b !important;
}
.badge-animate-danger {
    animation: pulse-danger 2s infinite;
}
.badge-animate-warning {
    animation: pulse-warning 2s infinite;
}
</style>

<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Gestion des Pieces</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo (int) $pagination['total_items']; ?> piece<?php echo ((int) $pagination['total_items'] !== 1) ? 's' : ''; ?> dans le systeme
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=addPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter une Piece
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
<?php endif; ?>

<form method="GET" action="index.php" class="filter-panel filter-panel-tight">
    <input type="hidden" name="action" value="managePieces">
    <div class="history-filter-grid">
        <div class="search-wrap search-wrap-wide">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="q" placeholder="Rechercher une piece, une marque ou une reference..." value="<?php echo htmlspecialchars((string) ($paginationQuery['q'] ?? '')); ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-search"></i> Rechercher</button>
            <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline">Reinitialiser</a>
        </div>
    </div>
</form>

<?php if (empty($pieces)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">Pieces</div>
        <h3>Aucune piece trouvee</h3>
        <p>Aucune piece n'est enregistree pour ces criteres.</p>
        <a href="index.php?action=addPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-plus-lg"></i> Ajouter une Piece
        </a>
    </div>
<?php else: ?>
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-box-seam me-2"></i>Catalogue admin</h3>
            <span class="table-meta">Affichage <?php echo (int) $pagination['from']; ?> - <?php echo (int) $pagination['to']; ?> sur <?php echo (int) $pagination['total_items']; ?></span>
        </div>
        <div class="table-responsive-wrap">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>Piece</th>
                        <th>Reference</th>
                        <th>Categorie</th>
                        <th>Marque</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pieces as $p): ?>
                        <?php 
                        $rowClass = '';
                        $badgeAnimateClass = '';
                        if ((int) $p['quantite_stock'] <= 0) {
                            $rowClass = 'row-danger';
                            $badgeAnimateClass = 'badge-animate-danger';
                        } elseif ((int) $p['quantite_stock'] <= (int) $p['seuil_alerte']) {
                            $rowClass = 'row-warning';
                            $badgeAnimateClass = 'badge-animate-warning';
                        }
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td>
                                <div class="table-piece-cell">
                                    <div class="table-piece-thumb table-piece-thumb-sm">
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="<?php echo htmlspecialchars((string) $p['image']); ?>" alt="<?php echo htmlspecialchars((string) $p['nom']); ?>">
                                        <?php else: ?>
                                            <i class="bi bi-image"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars((string) $p['nom']); ?></strong>
                                        <div class="table-subtext">#<?php echo (int) $p['id_piece']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code class="code-chip"><?php echo htmlspecialchars((string) $p['reference']); ?></code></td>
                            <td><span class="badge-category"><?php echo htmlspecialchars((string) $p['categorie']); ?></span></td>
                            <td><?php echo htmlspecialchars((string) $p['marque']); ?></td>
                            <td class="badge-price"><?php echo number_format((float) $p['prix_unitaire'], 2, ',', ' '); ?> DT</td>
                            <td>
                                <?php if ((int) $p['quantite_stock'] <= 0): ?>
                                    <span class="badge-stock out-of-stock <?php echo $badgeAnimateClass; ?>"><?php echo (int) $p['quantite_stock']; ?> - Rupture</span>
                                <?php elseif ((int) $p['quantite_stock'] <= (int) $p['seuil_alerte']): ?>
                                    <span class="badge-stock low-stock <?php echo $badgeAnimateClass; ?>"><?php echo (int) $p['quantite_stock']; ?> - Faible</span>
                                <?php else: ?>
                                    <span class="badge-stock in-stock"><?php echo (int) $p['quantite_stock']; ?> - OK</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($p['date_ajout']) ? date('d/m/Y', strtotime((string) $p['date_ajout'])) : '-'; ?></td>
                            <td>
                                <div class="btn-group-actions btn-group-actions-compact">
                                    <a href="index.php?action=viewPiece&id=<?php echo (int) $p['id_piece']; ?>" class="btn-sg btn-sg-outline btn-sg-sm" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="index.php?action=editPiece&id=<?php echo (int) $p['id_piece']; ?>" class="btn-sg btn-sg-success btn-sg-sm" title="Modifier">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="index.php?action=confirmDeletePiece&id=<?php echo (int) $p['id_piece']; ?>" class="btn-sg btn-sg-danger btn-sg-sm" title="Supprimer">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $paginationAction = 'managePieces';
    require __DIR__ . '/../shared/pagination.php';
    ?>

    <div class="sg-table-wrap" style="margin-top:1.5rem;">
        <div class="table-header">
            <h3><i class="bi bi-send me-2"></i>Demandes de pieces introuvables</h3>
            <div class="hero-actions">
                <span class="table-meta"><?php echo isset($demandesPiece) ? count($demandesPiece) : 0; ?> demande<?php echo (isset($demandesPiece) && count($demandesPiece) !== 1) ? 's' : ''; ?></span>
                <a href="index.php?action=exportDemandes" class="btn-sg btn-sg-outline btn-sg-sm">
                    <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
                </a>
            </div>
        </div>

        <?php if (empty($demandesPiece)): ?>
            <div class="empty-inline">Aucune demande de piece introuvable pour le moment.</div>
        <?php else: ?>
            <div class="table-responsive-wrap">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Telephone</th>
                            <th>Piece demandee</th>
                            <th>Marque / Modele</th>
                            <th>Quantite</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandesPiece as $d): ?>
                            <tr>
                                <td><?php echo !empty($d['date_demande']) ? date('d/m/Y H:i', strtotime((string) $d['date_demande'])) : '-'; ?></td>
                                <td><strong><?php echo htmlspecialchars(trim((string) (($d['prenom_client'] ?? '') . ' ' . ($d['nom_client'] ?? '')))); ?></strong></td>
                                <td><?php echo htmlspecialchars((string) ($d['telephone'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($d['nom_piece'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars((string) ($d['marque'] ?? '-')); ?></td>
                                <td><?php echo isset($d['quantite']) ? (int) $d['quantite'] : 1; ?></td>
                                <td><?php echo htmlspecialchars((string) ($d['description'] ?? '-')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
