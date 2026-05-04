<?php $pageTitle = 'Détails de la Commande'; $action = 'manageCommandes'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="hero-panel hero-panel-commands">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Détails de la Commande #<?php echo (int) $commande['id_commande']; ?></h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            Passée le <?php echo !empty($commande['date_commande']) ? date('d/m/Y à H:i', strtotime((string) $commande['date_commande'])) : '-'; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=manageCommandes" class="btn-sg btn-sg-outline">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
        <a href="index.php?action=exportCommande&id=<?php echo (int) $commande['id_commande']; ?>" class="btn-sg btn-sg-primary">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
    </div>
</div>

<div class="sg-form-wrap" style="max-width: 800px; margin: 2rem auto;">
    <div class="row">
        <!-- Informations Client & Commande -->
        <div class="col-md-6 mb-4">
            <h3 style="color:white; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">Informations Client</h3>
            <div class="table-responsive-wrap">
                <table class="sg-table" style="background: transparent;">
                    <tbody>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Nom Complet</strong></td>
                            <td style="border: none; padding: 0.5rem 0;"><strong><?php echo htmlspecialchars(trim((string) ($commande['prenom_client'] . ' ' . $commande['nom_client']))); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Téléphone</strong></td>
                            <td style="border: none; padding: 0.5rem 0;"><?php echo htmlspecialchars((string) $commande['telephone']); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Source</strong></td>
                            <td style="border: none; padding: 0.5rem 0;"><span class="badge-category"><?php echo htmlspecialchars((string) ($commande['source'] ?? 'Site Web')); ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Informations Paiement & Statut -->
        <div class="col-md-6 mb-4">
            <h3 style="color:white; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">Paiement & Statut</h3>
            <div class="table-responsive-wrap">
                <table class="sg-table" style="background: transparent;">
                    <tbody>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Statut</strong></td>
                            <td style="border: none; padding: 0.5rem 0;">
                                <?php
                                $statutClass = 'badge-stock in-stock';
                                if (($commande['statut'] ?? '') === 'En attente') $statutClass = 'badge-stock low-stock';
                                elseif (($commande['statut'] ?? '') === 'Annulee') $statutClass = 'badge-stock out-of-stock';
                                ?>
                                <span class="<?php echo $statutClass; ?>"><?php echo htmlspecialchars((string) $commande['statut']); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Méthode de Paiement</strong></td>
                            <td style="border: none; padding: 0.5rem 0;"><?php echo htmlspecialchars((string) ($commande['payment_method'] ?? 'Non défini')); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Statut du Paiement</strong></td>
                            <td style="border: none; padding: 0.5rem 0;">
                                <?php
                                $psClass = 'badge-stock out-of-stock';
                                if (($commande['payment_status'] ?? '') === 'Paye') $psClass = 'badge-stock in-stock';
                                ?>
                                <span class="<?php echo $psClass; ?>"><?php echo htmlspecialchars((string) ($commande['payment_status'] ?? 'Non paye')); ?></span>
                            </td>
                        </tr>
                        <?php if (!empty($commande['payment_gateway_reference'])): ?>
                        <tr>
                            <td style="color: #a0aec0; border: none; padding: 0.5rem 0;"><strong>Réf. Paiement</strong></td>
                            <td style="border: none; padding: 0.5rem 0;"><code class="code-chip"><?php echo htmlspecialchars((string) $commande['payment_gateway_reference']); ?></code></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Détails de la Pièce Commandée -->
    <h3 style="color:white; margin-top: 1rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">Détail de l'article</h3>
    <div class="table-responsive-wrap">
        <table class="sg-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Prix Unitaire</th>
                    <th>Quantité</th>
                    <th style="text-align: right;">Total TTC</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="table-piece-cell">
                            <div class="table-piece-thumb">
                                <?php if (!empty($commande['piece_image'])): ?>
                                    <img src="<?php echo htmlspecialchars((string) $commande['piece_image']); ?>" alt="<?php echo htmlspecialchars((string) $commande['piece_nom']); ?>">
                                <?php else: ?>
                                    <i class="bi bi-box-seam"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars((string) $commande['piece_nom']); ?></strong>
                                <div class="table-subtext">Réf: <?php echo htmlspecialchars((string) $commande['piece_reference']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo number_format((float) $commande['piece_prix_unitaire'], 2, ',', ' '); ?> DT</td>
                    <td><?php echo (int) $commande['quantite']; ?></td>
                    <td style="text-align: right;"><strong><?php echo number_format((float) $commande['montant_total'], 2, ',', ' '); ?> DT</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
