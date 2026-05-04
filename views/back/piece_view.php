<?php $pageTitle = 'Détails de la Pièce'; $action = 'managePieces'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="hero-panel">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Détails de la Pièce</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            Référence: <?php echo htmlspecialchars((string) $piece['reference']); ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=managePieces" class="btn-sg btn-sg-outline">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <a href="index.php?action=editPiece&id=<?php echo (int) $piece['id_piece']; ?>" class="btn-sg btn-sg-primary">
            <i class="bi bi-pencil-square"></i> Modifier
        </a>
    </div>
</div>

<div class="sg-form-wrap" style="max-width: 800px; margin: 2rem auto;">
    <div class="row">
        <div class="col-md-4 text-center mb-4">
            <?php if (!empty($piece['image'])): ?>
                <img src="<?php echo htmlspecialchars((string) $piece['image']); ?>" alt="<?php echo htmlspecialchars((string) $piece['nom']); ?>" style="max-width:100%; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            <?php else: ?>
                <div style="background:#1a202c; color:#a0aec0; height:200px; display:flex; align-items:center; justify-content:center; border-radius:8px;">
                    <i class="bi bi-image" style="font-size: 4rem;"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <h2 style="color:white; margin-bottom: 1rem;"><?php echo htmlspecialchars((string) $piece['nom']); ?></h2>
            <div class="table-responsive-wrap">
                <table class="sg-table">
                    <tbody>
                        <tr>
                            <td style="width: 40%; color: #a0aec0;"><strong>ID Pièce</strong></td>
                            <td>#<?php echo (int) $piece['id_piece']; ?></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Référence</strong></td>
                            <td><code class="code-chip"><?php echo htmlspecialchars((string) $piece['reference']); ?></code></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Catégorie</strong></td>
                            <td><span class="badge-category"><?php echo htmlspecialchars((string) $piece['categorie']); ?></span></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Marque</strong></td>
                            <td><?php echo htmlspecialchars((string) $piece['marque']); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Prix Unitaire</strong></td>
                            <td><strong style="color: white;"><?php echo number_format((float) $piece['prix_unitaire'], 2, ',', ' '); ?> DT</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Quantité en Stock</strong></td>
                            <td>
                                <?php if ((int) $piece['quantite_stock'] <= 0): ?>
                                    <span class="badge-stock out-of-stock"><?php echo (int) $piece['quantite_stock']; ?> - Rupture</span>
                                <?php elseif ((int) $piece['quantite_stock'] <= (int) $piece['seuil_alerte']): ?>
                                    <span class="badge-stock low-stock"><?php echo (int) $piece['quantite_stock']; ?> - Faible</span>
                                <?php else: ?>
                                    <span class="badge-stock in-stock"><?php echo (int) $piece['quantite_stock']; ?> - OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Seuil d'alerte</strong></td>
                            <td><?php echo (int) $piece['seuil_alerte']; ?></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Garantie (Mois)</strong></td>
                            <td><?php echo isset($piece['garantie_mois']) ? (int) $piece['garantie_mois'] : 12; ?> mois</td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0;"><strong>Date d'ajout</strong></td>
                            <td><?php echo !empty($piece['date_ajout']) ? date('d/m/Y H:i', strtotime((string) $piece['date_ajout'])) : '-'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <strong style="color: #a0aec0; display:block; margin-bottom: 0.5rem;">Description détaillée</strong>
                <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; color: #e2e8f0; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars((string) $piece['description'])); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
