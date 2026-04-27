<?php
/**
 * Vue: Suivi des Interventions - Client
 * Affiche les interventions liées aux diagnostics du client
 * Variables: $interventions (liste des interventions)
 */
?>
<div class="container-fluid mt-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-light">
                <i class="fas fa-wrench me-2"></i>Suivi de mes Interventions
            </h1>
            <p class="text-muted">Consultez le détail et l'historique de vos interventions</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if (isset($_GET['intervention_created']) && $_GET['intervention_created'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Succès!</strong> Intervention créée avec succès. Le client en a été notifié.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == 1): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Mise à jour!</strong> Le statut a été mis à jour.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Erreur!</strong> Une erreur s'est produite.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Interventions -->
    <?php if (!empty($interventions)): ?>
        <div class="row">
            <?php foreach ($interventions as $inter): ?>
                <div class="col-lg-6 col-xxl-4 mb-4">
                    <div class="card bg-dark border-light h-100" style="box-shadow: 0 5px 20px rgba(0,0,0,0.3);">
                        <!-- En-tête de la carte -->
                        <div class="card-header" style="background: linear-gradient(135deg, #23555f 0%, #08bce8 100%);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="text-white mb-1">
                                        <i class="fas fa-wrench me-2"></i>#<?php echo $inter['id_intervention']; ?>
                                    </h5>
                                    <small class="inter-type-pill">
                                        <?php echo htmlspecialchars($inter['type_nom']); ?>
                                    </small>
                                </div>
                                <?php 
                                    $statut = $inter['statut'];
                                    $badgeClass = 'bg-secondary';
                                    $icon = 'fa-clock';
                                    
                                    if ($statut === 'planifiée') {
                                        $badgeClass = 'bg-info';
                                        $icon = 'fa-calendar';
                                    } elseif ($statut === 'en_cours') {
                                        $badgeClass = 'bg-warning';
                                        $icon = 'fa-spinner';
                                    } elseif ($statut === 'terminée') {
                                        $badgeClass = 'bg-success';
                                        $icon = 'fa-check-circle';
                                    }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <i class="fas <?php echo $icon; ?> me-1"></i>
                                    <?php echo ucfirst($statut); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Corps de la carte -->
                        <div class="card-body text-light">
                            <!-- Diagnostic lié -->
                            <div class="mb-3 pb-3 border-bottom border-secondary">
                                <small class="text-muted">
                                    <i class="fas fa-stethoscope me-2"></i>Diagnostic
                                </small>
                                <p class="mb-0 mt-1">
                                    <span class="badge bg-info">
                                        #<?php echo $inter['id_diagnostic']; ?>
                                    </span>
                                    - <?php echo htmlspecialchars($inter['immatriculation']); ?>
                                </p>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($inter['vehicle_marque']); ?> 
                                    <?php echo htmlspecialchars($inter['vehicle_modele']); ?>
                                </small>
                            </div>

                            <!-- Description du travail -->
                            <div class="mb-3 pb-3 border-bottom border-secondary">
                                <small class="text-muted">
                                    <i class="fas fa-tasks me-2"></i>Travaux à effectuer
                                </small>
                                <p class="mb-0 mt-1 text-light" style="font-size: 0.95rem; line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars(substr($inter['description_travail'], 0, 200))); ?>
                                    <?php if (strlen($inter['description_travail']) > 200): ?>...<?php endif; ?>
                                </p>
                            </div>

                            <!-- Coûts -->
                            <div class="row mb-3 pb-3 border-bottom border-secondary">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-2"></i>Coût Initial
                                    </small>
                                    <p class="mb-0 mt-1 h6 text-info">
                                        <?php echo number_format($inter['cout_initial'], 2, ',', ' '); ?> DT
                                    </p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-money-bill me-2"></i>Coût Final
                                    </small>
                                    <p class="mb-0 mt-1 h6">
                                        <?php if ($inter['cout_final'] !== null): ?>
                                            <span class="text-success">
                                                <?php echo number_format($inter['cout_final'], 2, ',', ' '); ?> DT
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">En attente</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-2"></i>Début
                                    </small>
                                    <p class="mb-0 mt-1 small inter-date-value inter-date-start">
                                        <?php 
                                            if ($inter['date_debut']) {
                                                echo date('d/m/Y', strtotime($inter['date_debut']));
                                            } else {
                                                echo '<span class="inter-date-muted">Non démarrée</span>';
                                            }
                                        ?>
                                    </p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-check me-2"></i>Fin
                                    </small>
                                    <p class="mb-0 mt-1 small inter-date-value inter-date-end">
                                        <?php 
                                            if ($inter['date_fin']) {
                                                echo date('d/m/Y', strtotime($inter['date_fin']));
                                            } else {
                                                echo '<span class="inter-date-muted">En cours</span>';
                                            }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-footer bg-dark border-secondary">
                            <div class="btn-group w-100" role="group">
                                <a href="index.php?action=intervention_detail&id=<?php echo $inter['id_intervention']; ?>" 
                                   class="btn btn-sm btn-outline-light" style="flex: 1;">
                                    <i class="fas fa-eye me-1"></i>Détails
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Aucune intervention -->
        <div class="card bg-dark border-light">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
                <h5 class="text-light mt-3 mb-2">Aucune intervention actuellement</h5>
                <p class="text-muted mb-3">
                    Vos interventions apparaîtront ici une fois approuvées par nos techniciens.
                </p>
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.card-header {
    border-radius: 10px 10px 0 0;
    padding: 1.25rem;
}

.card-footer {
    border-radius: 0 0 10px 10px;
    padding: 0.75rem;
}

.btn-group-sm .btn {
    padding: 0.35rem 0.6rem;
    font-size: 0.75rem;
}

.badge {
    font-size: 0.85rem;
    font-weight: 600;
    padding: 0.4rem 0.7rem;
}

.inter-type-pill {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: rgba(25, 211, 255, 0.2);
    border: 1px solid rgba(25, 211, 255, 0.45);
    color: #dff7ff;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.inter-date-value {
    font-weight: 600;
}

.inter-date-start {
    color: #9fd4ff;
}

.inter-date-end {
    color: #8ff0b2;
}

.inter-date-muted {
    color: #9cb4d6;
    font-weight: 500;
}

.btn-outline-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(255, 255, 255, 0.2);
}

.btn-outline-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(23, 162, 184, 0.3);
}
</style>
