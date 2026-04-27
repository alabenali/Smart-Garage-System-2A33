<?php
/**
 * Vue: Formulaire de création d'intervention
 * Affiche après acceptation d'un diagnostic
 * Variables: $diagnostic (infos du diagnostic accepté), $types_intervention (liste)
 */
?>
<?php
$pageTitle = 'Créer une intervention';
$action = 'admin_interventions';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-warning border-0 rounded-3 mb-4" role="alert">
            Impossible de créer l'intervention. Vérifiez les champs du formulaire.
        </div>
    <?php endif; ?>

    <?php if (empty($types_intervention)): ?>
        <div class="alert alert-warning border-0 rounded-3 mb-4" role="alert">
            Aucun type d'intervention disponible. Ajoutez d'abord des types dans la table type_intervention.
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title text-white mb-0">Créer une intervention</h1>
            <p class="text-muted diagnostics-subtitle">Renseignez les travaux à planifier pour ce diagnostic accepté.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card bg-dark border-0 shadow-sm rounded-4">
                <div class="card-header border-0 bg-secondary bg-opacity-10 text-white py-3">
                    Formulaire d'intervention
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 rounded-3 mb-4">
                        <div class="row g-2">
                            <div class="col-md-4 small">
                                <strong>Référence:</strong> #<?php echo htmlspecialchars($diagnostic['id_diagnostic']); ?>
                            </div>
                            <div class="col-md-4 small">
                                <strong>Véhicule:</strong> <?php echo htmlspecialchars($diagnostic['immatriculation']); ?>
                            </div>
                            <div class="col-md-4 small">
                                <strong>Gravité:</strong>
                                <span class="badge bg-warning text-dark ms-1"><?php echo htmlspecialchars($diagnostic['gravite']); ?></span>
                            </div>
                            <div class="col-md-4 small">
                                <strong>Marque:</strong> <?php echo htmlspecialchars((string)($diagnostic['vehicle_marque'] ?? $diagnostic['marque'] ?? 'N/A')); ?>
                            </div>
                            <div class="col-md-4 small">
                                <strong>Modèle:</strong> <?php echo htmlspecialchars((string)($diagnostic['vehicle_modele'] ?? $diagnostic['modele'] ?? 'N/A')); ?>
                            </div>
                            <div class="col-md-4 small">
                                <strong>Date diagnostic:</strong> <?php echo htmlspecialchars((string)($diagnostic['date_diagnostic'] ?? 'N/A')); ?>
                            </div>
                            <div class="col-12 small">
                                <strong>Problème client:</strong>
                                <?php echo htmlspecialchars((string)($diagnostic['description_probleme'] ?? 'Non précisé')); ?>
                            </div>
                        </div>
                    </div>

                    <?php $mediaPath = trim((string)($diagnostic['media_path'] ?? '')); ?>
                    <?php $mediaType = strtolower(trim((string)($diagnostic['media_type'] ?? ''))); ?>
                    <?php if ($mediaPath !== ''): ?>
                        <div class="card bg-black bg-opacity-25 border-secondary mb-4">
                            <div class="card-body">
                                <h2 class="h6 text-white mb-3">Photo/Media du problème</h2>
                                <?php if (strpos($mediaType, 'video/') === 0): ?>
                                    <video src="<?php echo htmlspecialchars($mediaPath); ?>" controls class="w-100 rounded-3" style="max-height: 320px;"></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($mediaPath); ?>" alt="Media du problème" class="img-fluid rounded-3 border border-secondary" style="max-height: 320px; object-fit: cover; width: 100%;">
                                <?php endif; ?>
                                <div class="small text-muted mt-2">Fichier: <?php echo htmlspecialchars($mediaPath); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?action=create_intervention" id="interventionForm" novalidate>
                        <input type="hidden" name="action" value="create_intervention">
                        <input type="hidden" name="id_diagnostic" value="<?php echo (int)$diagnostic['id_diagnostic']; ?>">

                        <div class="mb-3">
                            <label for="typeIntervention" class="form-label text-white">Type d'intervention</label>
                            <select class="form-select bg-dark text-white border-secondary" id="typeIntervention" name="id_type" required>
                                <option value="">Sélectionner un type</option>
                                <?php if (!empty($types_intervention)): ?>
                                    <?php foreach ($types_intervention as $type): ?>
                                        <option value="<?php echo (int)$type['id_type']; ?>">
                                            <?php echo htmlspecialchars($type['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="descriptionTravail" class="form-label text-white">Description du travail</label>
                            <textarea class="form-control bg-dark text-white border-secondary" id="descriptionTravail" name="description_travail" rows="5" placeholder="Décrivez les travaux à effectuer..." required><?php echo htmlspecialchars('Intervention suite au diagnostic: ' . (string)($diagnostic['description_probleme'] ?? '')); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="coutInitial" class="form-label text-white">Coût initial estimé (DT)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" id="coutInitial" name="cout_initial" step="0.01" min="0" required value="<?php echo htmlspecialchars((string)($diagnostic['montant_estime'] ?? 0)); ?>">
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="index.php?action=diagnostics" class="btn btn-outline-light rounded-3">
                                <i class="bi bi-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary rounded-3" <?php echo empty($types_intervention) ? 'disabled' : ''; ?>>
                                <i class="bi bi-check2-circle me-1"></i>Confirmer et créer l'intervention
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card bg-dark border-0 shadow-sm rounded-4 h-100">
                <div class="card-header border-0 bg-secondary bg-opacity-10 text-white py-3">
                    Conseils
                </div>
                <div class="card-body text-light">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Statut initial: planifiée.</li>
                        <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Le coût final sera saisi en fin d'intervention.</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i>L'intervention reste liée au diagnostic accepté.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation du formulaire
(function() {
    'use strict';
    const form = document.getElementById('interventionForm');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
