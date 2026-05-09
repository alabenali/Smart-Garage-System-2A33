<?php
$pageTitle = 'Gestion des Commandes';
$action = 'manageCommandes';
$statusOptions = isset($statusOptions) && is_array($statusOptions)
    ? $statusOptions
    : ['En attente', 'Paiement initie', 'Paiement', 'Confirmee', 'Payee', 'Livree', 'Annulee'];
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="hero-panel hero-panel-commands">
    <div>
        <h1 class="page-title" style="margin-bottom:0.2rem;">Gestion des Commandes</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo (int) $pagination['total_items']; ?> commande<?php echo ((int) $pagination['total_items'] !== 1) ? 's' : ''; ?> enregistree<?php echo ((int) $pagination['total_items'] !== 1) ? 's' : ''; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a href="index.php?action=orderHistory" class="btn-sg btn-sg-outline">
            <i class="bi bi-clock-history"></i> Historique client
        </a>
        <a href="index.php?action=exportCommandes" class="btn-sg btn-sg-outline">
            <i class="bi bi-file-earmark-pdf"></i> Exporter PDF
        </a>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
<?php endif; ?>

<form method="GET" action="index.php" class="filter-panel command-filter-panel">
    <input type="hidden" name="action" value="manageCommandes">
    <div class="command-filter-grid">
        <div class="search-wrap search-wrap-wide">
            <i class="bi bi-search search-icon"></i>
            <input type="text" name="q" placeholder="Rechercher un client, un telephone ou une piece..." value="<?php echo htmlspecialchars((string) ($paginationQuery['q'] ?? '')); ?>">
        </div>
        <select name="statut">
            <option value="">Tous les statuts</option>
            <?php foreach ($statusOptions as $option): ?>
                <option value="<?php echo htmlspecialchars((string) $option); ?>" <?php echo (($paginationQuery['statut'] ?? '') === $option) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $option); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="filter-actions">
            <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-funnel"></i> Filtrer</button>
            <a href="index.php?action=manageCommandes" class="btn-sg btn-sg-outline">Reinitialiser</a>
        </div>
    </div>
</form>

<?php if (empty($commandes)): ?>
    <div class="sg-form-wrap empty-state">
        <div class="empty-icon">Cmd</div>
        <h3>Aucune commande</h3>
        <p>Aucune commande ne correspond a vos criteres.</p>
    </div>
<?php else: ?>
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-receipt me-2"></i>Historique des commandes</h3>
            <span class="table-meta">Affichage <?php echo (int) $pagination['from']; ?> - <?php echo (int) $pagination['to']; ?> sur <?php echo (int) $pagination['total_items']; ?></span>
        </div>
        <div class="table-responsive-wrap">
            <table class="sg-table">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Piece</th>
                        <th>Prix unitaire</th>
                        <th>Qte</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $c): ?>
                        <?php
                        $statusSelectClass = 'status-success';
                        if (in_array(($c['statut'] ?? ''), ['En attente', 'Paiement initie'], true)) {
                            $statusSelectClass = 'status-warning';
                        } elseif (($c['statut'] ?? '') === 'Annulee') {
                            $statusSelectClass = 'status-danger';
                        }
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong>#<?php echo (int) $c['id_commande']; ?></strong>
                                    <div class="table-subtext"><?php echo htmlspecialchars((string) $c['piece_reference']); ?></div>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars(trim((string) ($c['prenom_client'] . ' ' . $c['nom_client']))); ?></strong>
                                <div class="table-subtext"><?php echo htmlspecialchars((string) $c['telephone']); ?></div>
                            </td>
                            <td>
                                <div class="table-piece-cell">
                                    <div class="table-piece-thumb">
                                        <?php if (!empty($c['piece_image'])): ?>
                                            <img src="<?php echo htmlspecialchars((string) $c['piece_image']); ?>" alt="<?php echo htmlspecialchars((string) $c['piece_nom']); ?>">
                                        <?php else: ?>
                                            <i class="bi bi-box-seam"></i>
                                        <?php endif; ?>
                                    </div>
                                    <strong><?php echo htmlspecialchars((string) $c['piece_nom']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo number_format((float) $c['piece_prix_unitaire'], 2, ',', ' '); ?> DT</td>
                            <td><?php echo (int) $c['quantite']; ?></td>
                            <td><strong><?php echo number_format((float) $c['montant_total'], 2, ',', ' '); ?> DT</strong></td>
                            <td>
                                <form method="POST" action="index.php?action=updateCommandeStatus" class="command-status-form">
                                    <input type="hidden" name="id_commande" value="<?php echo (int) $c['id_commande']; ?>">
                                    <input type="hidden" name="return_q" value="<?php echo htmlspecialchars((string) ($paginationQuery['q'] ?? '')); ?>">
                                    <input type="hidden" name="return_statut" value="<?php echo htmlspecialchars((string) ($paginationQuery['statut'] ?? '')); ?>">
                                    <input type="hidden" name="return_page" value="<?php echo (int) ($pagination['current_page'] ?? 1); ?>">
                                    <select name="statut" class="command-status-select <?php echo $statusSelectClass; ?>" onchange="this.form.submit()" title="Changer le statut">
                                        <?php foreach ($statusOptions as $option): ?>
                                            <option value="<?php echo htmlspecialchars((string) $option); ?>" <?php echo (($c['statut'] ?? '') === $option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars((string) $option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (!in_array(($c['statut'] ?? ''), $statusOptions, true) && ($c['statut'] ?? '') !== ''): ?>
                                            <option value="<?php echo htmlspecialchars((string) $c['statut']); ?>" selected>
                                                <?php echo htmlspecialchars((string) $c['statut']); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                    <button type="submit" class="btn-sg btn-sg-outline btn-sg-sm command-status-save" title="Enregistrer">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                            </td>
                            <td><?php echo !empty($c['date_commande']) ? date('d/m/Y H:i', strtotime((string) $c['date_commande'])) : '-'; ?></td>
                            <td>
                                <div class="btn-group-actions btn-group-actions-compact">
                                    <a href="index.php?action=viewCommande&id=<?php echo (int) $c['id_commande']; ?>" class="btn-sg btn-sg-outline btn-sg-sm" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="index.php?action=exportCommande&id=<?php echo (int) $c['id_commande']; ?>" class="btn-sg btn-sg-outline btn-sg-sm" title="Exporter PDF">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                    <a href="index.php?action=deleteCommande&id=<?php echo (int) $c['id_commande']; ?>" class="btn-sg btn-sg-danger btn-sg-sm" onclick="return confirm('Supprimer cette commande ?');" title="Supprimer">
                                        <i class="bi bi-trash"></i>
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
    $paginationAction = 'manageCommandes';
    require __DIR__ . '/../shared/pagination.php';
    ?>
<?php endif; ?>

<?php require __DIR__ . '/layout_footer.php'; ?>
