<?php
$pageTitle = 'Detail Intervention';
$action = 'client_interventions';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : (int)($intervention['id_vehicule'] ?? 0);
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title text-white mb-1">Intervention #<?php echo (int)$intervention['id_intervention']; ?></h1>
            <p class="text-muted mb-0">Consultez le devis et repondez depuis votre espace client.</p>
        </div>
        <a href="index.php?action=client_interventions&vehicle_id=<?php echo (int)$vehicleId; ?>" class="btn btn-outline-light">
            Retour a la liste
        </a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Votre reponse devis a ete enregistree.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">Action impossible. Verifiez les donnees.</div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card bg-dark border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">Informations de l'intervention</div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="small text-muted">Vehicule</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($intervention['immatriculation'] ?? '-')); ?></div>
                            <div class="text-muted small">
                                <?php echo htmlspecialchars(trim((string)($intervention['vehicle_marque'] ?? '') . ' ' . (string)($intervention['vehicle_modele'] ?? ''))); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Type intervention</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($intervention['type_nom'] ?? '-')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Statut intervention</div>
                            <div><span class="badge bg-info"><?php echo htmlspecialchars((string)($intervention['statut'] ?? 'planifiee')); ?></span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Statut devis</div>
                            <div><span class="badge bg-warning text-dark"><?php echo htmlspecialchars((string)($intervention['statut_devis'] ?? 'en_attente')); ?></span></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="small text-muted">Couleur</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($intervention['vehicle_couleur'] ?? '-')); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Annee</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($intervention['vehicle_annee'] ?? '-')); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Kilometrage</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($intervention['vehicle_km'] ?? '-')); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Carburant</div>
                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($intervention['vehicle_carburant'] ?? '-')); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted mb-1">Description des travaux</div>
                        <div class="p-3 rounded-3 bg-secondary bg-opacity-10 border border-secondary-subtle">
                            <?php echo nl2br(htmlspecialchars((string)($intervention['description_travail'] ?? '-'))); ?>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="small text-muted">Cout initial</div>
                            <div class="h5 mb-0"><?php echo number_format((float)($intervention['cout_initial'] ?? 0), 2, ',', ' '); ?> DT</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Cout final</div>
                            <div class="h5 mb-0">
                                <?php if (isset($intervention['cout_final']) && $intervention['cout_final'] !== null): ?>
                                    <?php echo number_format((float)$intervention['cout_final'], 2, ',', ' '); ?> DT
                                <?php else: ?>
                                    <span class="text-muted">En attente</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Date fin</div>
                            <div class="h6 mb-0"><?php echo !empty($intervention['date_fin']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$intervention['date_fin']))) : 'En cours'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-dark border-0 shadow-sm rounded-4 mb-3">
                <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">Actions devis</div>
                <div class="card-body p-3 d-grid gap-2">
                    <a href="index.php?action=export_quote_pdf&id=<?php echo (int)$intervention['id_intervention']; ?>" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Telecharger le devis PDF
                    </a>

                    <form method="POST" action="index.php?action=intervention_detail" class="d-grid">
                        <input type="hidden" name="action" value="accept_quote">
                        <input type="hidden" name="id_intervention" value="<?php echo (int)$intervention['id_intervention']; ?>">
                        <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicleId; ?>">
                        <button type="submit" class="btn btn-success" <?php echo (($intervention['statut_devis'] ?? '') === 'accepte') ? 'disabled' : ''; ?>>
                            Accepter le devis
                        </button>
                    </form>

                    <form method="POST" action="index.php?action=intervention_detail" class="d-grid">
                        <input type="hidden" name="action" value="refuse_quote">
                        <input type="hidden" name="id_intervention" value="<?php echo (int)$intervention['id_intervention']; ?>">
                        <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicleId; ?>">
                        <button type="submit" class="btn btn-outline-danger" <?php echo (($intervention['statut_devis'] ?? '') === 'refuse') ? 'disabled' : ''; ?>>
                            Refuser le devis
                        </button>
                    </form>

                    <a href="index.php?action=client_messages&vehicle_id=<?php echo (int)$vehicleId; ?>" class="btn btn-outline-light">
                        Negocier via messagerie
                    </a>
                </div>
            </div>

            <div class="card bg-dark border-0 shadow-sm rounded-4">
                <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">Derniers messages</div>
                <div class="card-body p-3" id="recentMessagesContainer" style="max-height: 260px; overflow:auto;">
                    <?php if (empty($recentMessages)): ?>
                        <div class="text-muted small">Aucun message pour le moment.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($recentMessages, -4) as $msg): ?>
                            <div class="mb-2 p-2 rounded-3 <?php echo ($msg['expediteur'] === 'client') ? 'bg-primary bg-opacity-25' : 'bg-light bg-opacity-10'; ?>">
                                <div class="small text-muted mb-1"><?php echo strtoupper(htmlspecialchars((string)$msg['expediteur'])); ?> - <?php echo htmlspecialchars(date('d/m H:i', strtotime((string)$msg['date_envoi']))); ?></div>
                                <div class="small"><?php echo nl2br(htmlspecialchars((string)$msg['contenu'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('recentMessagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
