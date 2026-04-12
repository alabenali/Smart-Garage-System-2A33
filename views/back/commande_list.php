<?php $pageTitle = 'Gestion des Commandes'; $action = 'manageCommandes'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Gestion des Commandes</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo count($commandes); ?> commande<?php echo count($commandes) !== 1 ? 's' : ''; ?> enregistrée<?php echo count($commandes) !== 1 ? 's' : ''; ?>
        </p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
<?php endif; ?>

<?php if (empty($commandes)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">📦</div>
        <h3>Aucune commande</h3>
        <p>Aucune commande n'a été passée pour le moment.</p>
    </div>
<?php else: ?>
    <div class="table-responsive-wrap">
        <table class="sg-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Pièce</th>
                    <th>Réf.</th>
                    <th>Qté</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $c): ?>
                    <?php
                        $statutClass = 'badge-stock in-stock';
                        if ($c['statut'] === 'En attente') $statutClass = 'badge-stock low-stock';
                        elseif ($c['statut'] === 'Annulée') $statutClass = 'badge-stock out-of-stock';
                    ?>
                    <tr>
                        <td><?php echo $c['id_commande']; ?></td>
                        <td><strong><?php echo htmlspecialchars($c['prenom_client'] . ' ' . $c['nom_client']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['telephone']); ?></td>
                        <td><?php echo htmlspecialchars($c['piece_nom']); ?></td>
                        <td><span class="badge-category"><?php echo htmlspecialchars($c['piece_reference']); ?></span></td>
                        <td><?php echo $c['quantite']; ?></td>
                        <td><strong><?php echo number_format($c['montant_total'], 2, ',', ' '); ?> DT</strong></td>
                        <td><span class="<?php echo $statutClass; ?>"><?php echo htmlspecialchars($c['statut']); ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($c['date_commande'])); ?></td>
                        <td>
                            <a href="index.php?action=deleteCommande&id=<?php echo $c['id_commande']; ?>"
                               class="btn-sg btn-sg-danger btn-sm"
                               onclick="return confirm('Supprimer cette commande ?');"
                               title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
