<?php
/**
 * Vue: Formulaire diagnostic client
 * Affiche le formulaire pour soumettre un diagnostic
 * Variables disponibles: $vehicles (liste des véhicules)
 */
?>
<div class="container mt-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card bg-dark border-light">
                <div class="card-header" style="background: linear-gradient(135deg, #d65b4c 0%, #b33f31 100%);">
                    <h2 class="mb-0 text-white">
                        <i class="fas fa-clipboard-list me-2"></i>Demander un Diagnostic
                    </h2>
                </div>
                
                <div class="card-body">
                    <!-- Messages d'erreur/succès -->
                    <?php if (isset($_GET['created']) && $_GET['created'] == 1): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Succès!</strong> Votre demande de diagnostic a été envoyée avec succès.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error']) && $_GET['error'] == 'media'): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Erreur!</strong> Impossible de télécharger le fichier. Vérifiez le format et la taille.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php" enctype="multipart/form-data" id="diagnosticForm">
                        <input type="hidden" name="action" value="create_diagnostic">

                        <!-- Véhicule -->
                        <div class="mb-4">
                            <label for="vehicleSelect" class="form-label text-light fw-bold">
                                <i class="fas fa-car me-2"></i>Véhicule
                            </label>
                            <select class="form-select form-select-lg bg-secondary text-light border-light" 
                                    id="vehicleSelect" name="id_vehicule" required>
                                <option value="">-- Sélectionner un véhicule --</option>
                                <?php if (!empty($vehicles)): ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['id']; ?>">
                                            <?php echo htmlspecialchars($vehicle['immatriculation']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option disabled>Aucun véhicule disponible</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Sélectionnez le véhicule concerné par le diagnostic</small>
                        </div>

                        <!-- Description du problème -->
                        <div class="mb-4">
                            <label for="descriptionProbleme" class="form-label text-light fw-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i>Description du Problème
                            </label>
                            <textarea class="form-control form-control-lg bg-secondary text-light border-light" 
                                      id="descriptionProbleme" name="description_probleme" rows="5" required
                                      placeholder="Décrivez en détail le problème rencontré avec votre véhicule...">
                            </textarea>
                            <small class="text-muted">Soyez aussi précis que possible pour un meilleur diagnostic</small>
                        </div>

                        <!-- Gravité -->
                        <div class="mb-4">
                            <label for="gravite" class="form-label text-light fw-bold">
                                <i class="fas fa-signal me-2"></i>Niveau de Gravité
                            </label>
                            <select class="form-select form-select-lg bg-secondary text-light border-light" 
                                    id="gravite" name="gravite" required>
                                <option value="Faible">Faible - Problème mineur</option>
                                <option value="Moyen">Moyen - Problème modéré</option>
                                <option value="Élevé">Élevé - Problème urgent</option>
                            </select>
                        </div>

                        <!-- Upload média -->
                        <div class="mb-4">
                            <label for="mediaFile" class="form-label text-light fw-bold">
                                <i class="fas fa-image me-2"></i>Photo/Vidéo (Optionnel)
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="file" class="form-control bg-secondary text-light border-light" 
                                       id="mediaFile" name="media_file" accept="image/*,video/*">
                            </div>
                            <small class="text-muted">
                                Formats acceptés: JPG, PNG, GIF, WebP, MP4, WebM, OGG (Max: 10 MB)
                            </small>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-lg" 
                                    style="background: linear-gradient(135deg, #d65b4c 0%, #b33f31 100%);">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer la Demande
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations utiles -->
            <div class="card bg-dark border-light mt-4">
                <div class="card-header bg-secondary">
                    <h5 class="mb-0 text-light">
                        <i class="fas fa-info-circle me-2"></i>Informations Utiles
                    </h5>
                </div>
                <div class="card-body text-light">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Réponse rapide:</strong> Vous recevrez une réponse sous 24h
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Devis détaillé:</strong> Nous vous proposerons un devis précis
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Photos utiles:</strong> Joindre des photos aide au diagnostic
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            <strong>Suivi gratuit:</strong> Consultez l'historique de vos diagnostics
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation du formulaire avec Bootstrap
(function() {
    'use strict';
    const form = document.getElementById('diagnosticForm');
    
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
.form-control, .form-select {
    font-size: 1rem;
    border-width: 1px;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    background-color: var(--surface) !important;
    color: var(--text-900) !important;
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(200, 70, 56, 0.15) !important;
}

.form-control::placeholder {
    color: var(--text-500);
    opacity: 1;
}

.btn {
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-outline-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(200, 70, 56, 0.2);
}

.card {
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
}

.card-header {
    border-radius: var(--radius) var(--radius) 0 0;
    padding: 20px;
}
</style>
