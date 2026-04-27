<?php
$pageTitle = 'Gestion des Diagnostics';
$action = 'diagnostics';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4" role="alert">
            Diagnostic atelier cree avec succes.
        </div>
    <?php endif; ?>

    <?php if (($_GET['error'] ?? '') === 'media'): ?>
        <div class="alert alert-warning border-0 rounded-3 mb-4" role="alert">
            Le fichier joint est invalide (image/video, max 10 Mo).
        </div>
    <?php endif; ?>

    <?php if (($_GET['error'] ?? '') === 'validation'): ?>
        <div class="alert alert-warning border-0 rounded-3 mb-4" role="alert">
            Veuillez vérifier les champs du formulaire (données invalides).
        </div>
    <?php endif; ?>

    <?php if (($_GET['error'] ?? '') === 'no_intervention_type'): ?>
        <div class="alert alert-warning border-0 rounded-3 mb-4" role="alert">
            Impossible de créer une intervention automatique: ajoutez d'abord des types dans la table type_intervention.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['refused'])): ?>
        <div class="alert alert-info border-0 rounded-3 mb-4" role="alert">
            La demande a été refusée.
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title text-white mb-0">Ajouter diagnostic</h1>
            <p class="text-muted diagnostics-subtitle">Saisissez les informations du diagnostic atelier puis enregistrez ou créez directement une intervention.</p>
        </div>
    </div>

    <div class="card bg-dark border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-secondary bg-opacity-10 border-0 py-3 text-white">
            <strong class="text-white">Saisie diagnostic atelier (mecanicien)</strong>
        </div>
        <div class="card-body p-3">
            <form action="index.php?action=diagnostics" method="POST" enctype="multipart/form-data" id="workshopDiagnosticForm" novalidate>
                <input type="hidden" name="action" value="add_admin_diagnostic">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label workshop-label">Vehicule</label>
                        <select name="id_vehicule" class="form-select bg-dark border-secondary text-white" required>
                            <option value="">Choisir...</option>
                            <?php foreach (($vehicles ?? []) as $vehicle): ?>
                                <option value="<?php echo (int)$vehicle['id']; ?>"><?php echo htmlspecialchars((string)$vehicle['immatriculation']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez selectionner un vehicule.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label workshop-label">Montant estime (DT)</label>
                        <input type="number" step="0.01" min="0" name="montant_estime" class="form-control bg-dark border-secondary text-white" placeholder="Exemple: 280.00" required>
                        <div class="invalid-feedback">Veuillez saisir un montant valide.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label workshop-label">Date</label>
                        <input type="date" name="date_diagnostic" class="form-control bg-dark border-secondary text-white" value="<?php echo date('Y-m-d'); ?>" required>
                        <div class="invalid-feedback">Veuillez saisir la date du diagnostic.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label workshop-label">Description du probleme</label>
                        <textarea name="description_probleme" rows="3" class="form-control bg-dark border-secondary text-white" placeholder="Exemple: Bruit metallique au demarrage, perte de puissance." required></textarea>
                        <div class="invalid-feedback">Veuillez decrire le probleme.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label workshop-label">Resultat du diagnostic</label>
                        <textarea name="resultat" rows="3" class="form-control bg-dark border-secondary text-white" placeholder="Exemple: Usure des plaquettes avant, remplacement recommande." required></textarea>
                        <div class="invalid-feedback">Veuillez saisir le resultat du diagnostic.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label workshop-label">Media (optionnel)</label>
                        <input type="file" name="media_file" class="form-control bg-dark border-secondary text-white" accept="image/*,video/*">
                    </div>
                    <div class="col-md-8 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-clipboard-check me-1"></i>Enregistrer diagnostic
                        </button>
                        <button type="submit" name="create_intervention_now" value="1" class="btn btn-outline-info">
                            <i class="bi bi-tools me-1"></i>Enregistrer + creer intervention
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
<script>
(function() {
    'use strict';
    const form = document.getElementById('workshopDiagnosticForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>

<style>
#workshopDiagnosticForm .workshop-label {
    color: #f2f8ff !important;
    font-weight: 700;
}

#workshopDiagnosticForm .form-control,
#workshopDiagnosticForm .form-select {
    background-color: #1a2535 !important;
    color: #f4f8ff !important;
    border-color: #5e7289 !important;
}

#workshopDiagnosticForm .form-control::placeholder,
#workshopDiagnosticForm .form-select::placeholder {
    color: #c8d9ee !important;
    opacity: 1;
}

#workshopDiagnosticForm .invalid-feedback {
    color: #ff6b6b;
    font-weight: 600;
}
</style>

<?php require __DIR__ . '/layout_footer.php'; ?>
