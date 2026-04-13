<?php 
$pageTitle = 'Mes Diagnostics';
$action = 'mes_diagnostics';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container py-5">
    <div class="mb-5 text-center">
        <h1 class="display-5 text-white fw-bold">Mes Diagnostics</h1>
        <p class="text-muted lead">Consultez l'état de santé de vos véhicules et validez les devis.</p>
    </div>

    <!-- Stats summary (Front) -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card bg-dark text-white border-0 shadow-sm p-4 text-center rounded-4">
                <div class="text-primary mb-2"><i class="bi bi-clipboard-pulse fs-2"></i></div>
                <h3 class="fw-bold mb-0"><?php echo count($diagnostics); ?></h3>
                <span class="text-muted small">Total Diagnostics</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white border-0 shadow-sm p-4 text-center rounded-4">
                <div class="text-warning mb-2"><i class="bi bi-hourglass-split fs-2"></i></div>
                <h3 class="fw-bold mb-0">
                    <?php echo count(array_filter($diagnostics, fn($d) => ($d['status'] ?? '') === 'en attente')); ?>
                </h3>
                <span class="text-muted small">En attente</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white border-0 shadow-sm p-4 text-center rounded-4">
                <div class="text-success mb-2"><i class="bi bi-check2-circle fs-2"></i></div>
                <h3 class="fw-bold mb-0">
                    <?php echo count(array_filter($diagnostics, fn($d) => ($d['status'] ?? '') === 'terminé')); ?>
                </h3>
                <span class="text-muted small">Acceptés / Terminé</span>
            </div>
        </div>
    </div>

    <!-- Diagnostic Cards List -->
    <div class="row g-4 justify-content-center">
        <?php if (empty($diagnostics)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3"><i class="bi bi-emoji-smile-upside-down fs-1"></i></div>
                <h4 class="text-muted">Aucun diagnostic n'a encore été réalisé.</h4>
                <a href="vehicle_add.php" class="btn btn-primary rounded-pill px-4 mt-3">Ajouter mon premier véhicule</a>
            </div>
        <?php else: ?>
            <?php foreach ($diagnostics as $diag): ?>
                <div class="col-lg-6">
                    <div class="card bg-dark text-white border-0 shadow rounded-4 overflow-hidden h-100">
                        <div class="card-header bg-secondary bg-opacity-10 border-0 p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($diag['marque'] . ' ' . $diag['modele']); ?></h5>
                                <div class="badge bg-light text-dark small rounded-pill"><?php echo htmlspecialchars($diag['immatriculation']); ?></div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block mb-1">Date Diagnostic</small>
                                <span class="fw-bold fs-6 text-primary"><?php echo date('d M Y', strtotime($diag['date_diagnostic'])); ?></span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="text-muted small d-block mb-2 text-uppercase fw-bold letter-spacing-1">Symptômes identifiés</label>
                                <p class="card-text fs-5"><?php echo nl2br(htmlspecialchars($diag['description_probleme'] ?? 'Non spécifié')); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="text-muted small d-block mb-2 text-uppercase fw-bold letter-spacing-1">Action recommandée</label>
                                <div class="alert bg-secondary bg-opacity-10 border-0 text-white rounded-3">
                                    <i class="bi bi-tools text-primary me-2"></i>
                                    <?php echo !empty($diag['resultat']) ? htmlspecialchars($diag['resultat'] ?? '') : 'Analyse technique en cours...'; ?>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="text-muted small d-block mb-1">Gravité</label>
                                    <?php 
                                    $gravite = $diag['gravite'] ?? 'Faible';
                                    $badgeClass = 'bg-success';
                                    if ($gravite === 'Moyen') $badgeClass = 'bg-warning text-dark';
                                    if ($gravite === 'Élevé') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2 fs-6"><?php echo $gravite; ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <label class="text-muted small d-block mb-1">Montant Estimé</label>
                                    <h4 class="text-white fw-bold"><?php echo number_format($diag['montant_estime'] ?? 0, 2, ',', ' '); ?> <span class="fs-6 fw-normal">DT</span></h4>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-dark border-secondary border-opacity-25 p-4 py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <?php if (($diag['status'] ?? '') === 'en attente'): ?>
                                    <form action="" method="POST" class="w-100 d-flex gap-2">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_diagnostic" value="<?php echo $diag['id_diagnostic'] ?? ''; ?>">
                                        <button name="status" value="terminé" type="submit" class="btn btn-success flex-grow-1 rounded-pill">
                                            <i class="bi bi-check-lg me-2"></i>Accepter Devis
                                        </button>
                                        <button type="button" class="btn btn-outline-danger flex-grow-1 rounded-pill" onclick="alert('Demande de révision envoyée.');">
                                            <i class="bi bi-x-lg me-2"></i>Refuser
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="w-100 text-center py-2 bg-success bg-opacity-10 text-success rounded-pill border border-success border-opacity-25">
                                        <i class="bi bi-calendar-check me-2"></i>Devis Accepté & Service Terminé
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['updated'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast show bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i> Votre décision a été enregistrée avec succès !
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.letter-spacing-1 { letter-spacing: 1px; }
.card { transition: transform 0.3s ease; }
.card:hover { transform: translateY(-5px); }
</style>

<?php require __DIR__ . '/layout_footer.php'; ?>
