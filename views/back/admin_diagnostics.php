<?php
/**
 * Vue: Dashboard Admin - Gestion des Diagnostics
 * Affiche les diagnostics en attente avec options d'acceptation/refus
 * Variables: $diagnostics (list), $stats (statistiques)
 */
?>
<div class="container-fluid mt-4">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-light">
                <i class="fas fa-stethoscope me-2"></i>Gestion des Diagnostics
            </h1>
            <p class="text-muted">Gérez les demandes de diagnostic des clients</p>
        </div>
    </div>

    <!-- Statistiques -->
    <?php if (!empty($stats)): ?>
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-info h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-info mb-2">En attente</h6>
                        <h2 class="text-light"><?php echo $stats['waiting'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-warning h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-warning mb-2">Urgents</h6>
                        <h2 class="text-light"><?php echo $stats['urgent'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-success h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-success mb-2">Terminés</h6>
                        <h2 class="text-light"><?php echo $stats['completed'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-secondary mb-2">Total</h6>
                        <h2 class="text-light"><?php echo $stats['total'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Messages de succès -->
    <?php if (isset($_GET['accepted']) && $_GET['accepted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Diagnostic accepté!</strong> Une intervention peut maintenant être créée.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['refused']) && $_GET['refused'] == 1): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            <strong>Diagnostic refusé.</strong> Le client en a été notifié.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tableau des diagnostics -->
    <div class="card bg-dark border-light">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="mb-0 text-white">
                <i class="fas fa-list me-2"></i>Diagnostics en Attente
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($diagnostics)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-dark mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>#ID</th>
                                <th>Véhicule</th>
                                <th>Client / Problème</th>
                                <th>Gravité</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnostics as $diag): ?>
                                <tr class="align-middle">
                                    <td>
                                        <span class="badge bg-info">
                                            #<?php echo htmlspecialchars($diag['id_diagnostic']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-light">
                                            <?php echo htmlspecialchars($diag['immatriculation']); ?>
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($diag['vehicle_marque']); ?> 
                                            <?php echo htmlspecialchars($diag['vehicle_modele']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="text-light">
                                            <strong>Problème:</strong>
                                            <p class="mb-0 mt-1 text-muted" style="max-width: 250px; white-space: pre-wrap;">
                                                <?php echo htmlspecialchars(substr($diag['description_probleme'], 0, 100)); ?>
                                                <?php if (strlen($diag['description_probleme']) > 100): ?>...<?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $gravite = $diag['gravite'] ?? 'N/A';
                                            $badgeClass = 'bg-secondary';
                                            if ($gravite === 'Faible') $badgeClass = 'bg-success';
                                            elseif ($gravite === 'Moyen') $badgeClass = 'bg-warning';
                                            elseif ($gravite === 'Élevé') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($gravite); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php 
                                                $date = $diag['date_diagnostic'] ?? date('Y-m-d');
                                                echo date('d/m/Y', strtotime($date)); 
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <!-- Accepter -->
                                            <button type="button" class="btn btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#acceptModal"
                                                    onclick="setAcceptData(<?php echo $diag['id_diagnostic']; ?>, '<?php echo htmlspecialchars($diag['immatriculation']); ?>')">
                                                <i class="fas fa-check"></i> Accepter
                                            </button>
                                            
                                            <!-- Refuser -->
                                            <button type="button" class="btn btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#refuseModal"
                                                    onclick="setRefuseData(<?php echo $diag['id_diagnostic']; ?>, '<?php echo htmlspecialchars($diag['immatriculation']); ?>')">
                                                <i class="fas fa-times"></i> Refuser
                                            </button>
                                            
                                            <!-- Voir détails -->
                                            <a href="index.php?action=admin_diagnostic_detail&id=<?php echo $diag['id_diagnostic']; ?>" 
                                               class="btn btn-info">
                                                <i class="fas fa-eye"></i> Détails
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Aucun diagnostic en attente pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Accepter diagnostic -->
<div class="modal fade" id="acceptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-check-circle me-2"></i>Accepter le Diagnostic
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_diagnostics">
                <div class="modal-body text-light">
                    <input type="hidden" name="diagnostic_id" id="acceptDiagnosticId">
                    <input type="hidden" name="action_type" value="accept">

                    <div class="mb-3">
                        <p><strong>Véhicule:</strong> <span id="acceptVehicle" class="text-info"></span></p>
                    </div>

                    <div class="mb-3">
                        <label for="acceptResultat" class="form-label">Résultat du Diagnostic</label>
                        <textarea class="form-control bg-secondary text-light border-light" 
                                  id="acceptResultat" name="resultat" rows="4" required
                                  placeholder="Décrivez le diagnostic et la réparation proposée...">
                        </textarea>
                    </div>

                    <div class="mb-3">
                        <label for="acceptMontant" class="form-label">Montant Estimé (DT)</label>
                        <input type="number" class="form-control bg-secondary text-light border-light" 
                               id="acceptMontant" name="montant_estime" step="0.01" min="0" required
                               placeholder="Exemple: 150.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Accepter le Diagnostic
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Refuser diagnostic -->
<div class="modal fade" id="refuseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-times-circle me-2"></i>Refuser le Diagnostic
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_diagnostics">
                <div class="modal-body text-light">
                    <input type="hidden" name="diagnostic_id" id="refuseDiagnosticId">
                    <input type="hidden" name="action_type" value="refuse">

                    <div class="mb-3">
                        <p><strong>Véhicule:</strong> <span id="refuseVehicle" class="text-info"></span></p>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Le client sera notifié du refus avec la raison fournie.
                    </div>

                    <div class="mb-3">
                        <label for="refuseRaison" class="form-label">Raison du Refus</label>
                        <textarea class="form-control bg-secondary text-light border-light" 
                                  id="refuseRaison" name="raison_refus" rows="4" required
                                  placeholder="Expliquez pourquoi le diagnostic est refusé...">
                        </textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Refuser le Diagnostic
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setAcceptData(diagnosticId, vehicleImmat) {
    document.getElementById('acceptDiagnosticId').value = diagnosticId;
    document.getElementById('acceptVehicle').textContent = vehicleImmat;
}

function setRefuseData(diagnosticId, vehicleImmat) {
    document.getElementById('refuseDiagnosticId').value = diagnosticId;
    document.getElementById('refuseVehicle').textContent = vehicleImmat;
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.1) !important;
}

.btn-group-sm .btn {
    font-size: 0.75rem;
    padding: 0.35rem 0.6rem;
}

.badge {
    font-size: 0.85rem;
    padding: 0.4rem 0.6rem;
}

.form-control:focus, .form-select:focus {
    background-color: #3a3f5c !important;
    color: white !important;
    border-color: #667eea !important;
    box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
}
</style>
