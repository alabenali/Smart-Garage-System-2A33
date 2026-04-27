<?php
$pageTitle = 'Espace Client';
$action = 'mes_diagnostics';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-5 d-flex flex-column" style="min-height: 100vh;">
    <div class="mb-4 text-center">
        <h1 class="display-6 text-white fw-bold">Espace Client</h1>
        <p class="text-soft">Déclarez un problème et suivez le statut de votre véhicule.</p>
    </div>

    <div class="row g-4 flex-grow-1">
        <div class="col-lg-3">
            <div class="card bg-dark text-white border-0 shadow rounded-4">
                <div class="card-header bg-secondary bg-opacity-10 border-0 py-3">
                    Remplir le formulaire diagnostic
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_GET['created'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert" id="createdAlert">Votre demande a été envoyée avec succès.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <script>
                            setTimeout(function() {
                                const alert = document.getElementById('createdAlert');
                                if (alert) {
                                    const bsAlert = new bootstrap.Alert(alert);
                                    bsAlert.close();
                                }
                            }, 3000);
                        </script>
                    <?php endif; ?>
                    <?php if (isset($_GET['media_updated'])): ?>
                        <div class="alert alert-success">Le média a été ajouté avec succès.</div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error']) && $_GET['error'] !== 'media'): ?>
                        <div class="alert alert-danger">Veuillez vérifier les champs du formulaire.</div>
                    <?php endif; ?>
                    <?php if (($_GET['error'] ?? '') === 'media'): ?>
                        <div class="alert alert-danger">Le fichier joint doit être une image ou une vidéo valide (max 10 Mo).</div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_client">

                        <div class="mb-3">
                            <label class="form-label">Véhicule</label>
                            <select name="id_vehicule" class="form-select bg-dark text-white border-secondary" required>
                                <option value="">Choisir un véhicule</option>
                                <?php foreach (($vehicles ?? []) as $vehicle): ?>
                                    <option value="<?php echo (int)$vehicle['id']; ?>" <?php echo ($vehicleId > 0 && (int)$vehicleId === (int)$vehicle['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vehicle['immatriculation'] ?? ('Véhicule #' . (int)$vehicle['id'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-soft">Liste chargée depuis la base de données.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description du problème</label>
                            <textarea name="description_probleme" rows="4" class="form-control bg-dark text-white border-secondary" placeholder="Décrivez le problème rencontré..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Niveau de gravité</label>
                            <select name="gravite" class="form-select bg-dark text-white border-secondary">
                                <option value="Faible">Faible</option>
                                <option value="Moyen">Moyen</option>
                                <option value="Élevé">Élevé</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Photo ou vidéo</label>
                            <input type="file" name="media_file" class="form-control bg-dark text-white border-secondary" accept="image/*,video/*">
                            <small class="text-muted">Formats acceptés: image ou vidéo, max 10 Mo.</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3">Envoyer la demande</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9 d-flex flex-column">
            <!-- Suivre un véhicule -->
            <div class="card bg-dark text-white border-0 shadow rounded-4 mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="">
                        <input type="hidden" name="action" value="mes_diagnostics">
                        <label class="form-label mb-3">Suivre un véhicule</label>
                        <div class="input-group">
                            <select name="vehicle_id" class="form-select bg-dark text-white border-secondary" required>
                                <option value="">Choisir un véhicule</option>
                                <?php foreach (($vehicles ?? []) as $vehicle): ?>
                                    <option value="<?php echo (int)$vehicle['id']; ?>" <?php echo ($vehicleId > 0 && (int)$vehicleId === (int)$vehicle['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vehicle['immatriculation'] ?? ('Véhicule #' . (int)$vehicle['id'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline-light">Afficher</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historique -->
            <div class="card bg-dark text-white border-0 shadow rounded-4 overflow-hidden flex-grow-1 d-flex flex-column">
                <div class="card-header bg-secondary bg-opacity-10 border-0 py-3 d-flex justify-content-between">
                    <span>Historique</span>
                    <span class="text-soft small">
                        Véhicule:
                        <?php
                        if ($vehicleId > 0 && !empty($diagnostics) && !empty($diagnostics[0]['immatriculation'])) {
                            echo htmlspecialchars($diagnostics[0]['immatriculation']);
                        } elseif ($vehicleId > 0) {
                            echo 'ID ' . (int)$vehicleId;
                        } else {
                            echo '-';
                        }
                        ?>
                    </span>
                </div>

                <?php if (isset($_GET['media_updated'])): ?>
                    <div class="alert alert-success m-3 mb-0 alert-dismissible fade show" role="alert" id="mediaAlert">
                        Photo ajoutée avec succès.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <script>
                        // Auto-dismiss après 3 secondes
                        setTimeout(function() {
                            const alert = document.getElementById('mediaAlert');
                            if (alert) {
                                const bsAlert = new bootstrap.Alert(alert);
                                bsAlert.close();
                            }
                        }, 3000);
                    </script>
                <?php endif; ?>

                <div class="card-body p-0 flex-grow-1 d-flex flex-column">
                    <?php if (empty($diagnostics)): ?>
                        <div class="p-4 text-center text-soft flex-grow-1 d-flex align-items-center justify-content-center">Aucun diagnostic à afficher pour ce véhicule.</div>
                    <?php else: ?>
                        <div class="table-responsive flex-grow-1 d-flex flex-column">
                            <table class="table table-dark table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th class="ps-4">#ID</th>
                                        <th style="min-width: 360px;">Information voiture</th>
                                        <th>Image problème</th>
                                        <th>Gravité</th>
                                        <th>Statut</th>
                                        <th class="text-end pe-4">Ajouter média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diagnostics as $diag): ?>
                                        <tr>
                                            <td class="ps-4">#<?php echo (int)$diag['id_diagnostic']; ?></td>
                                            <td style="min-width: 360px;">
                                                <div class="d-flex align-items-start gap-3">
                                                    <?php if (!empty($diag['vehicle_photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($diag['vehicle_photo']); ?>" alt="Photo voiture" style="width:90px; height:70px; object-fit:cover; border-radius:10px;">
                                                    <?php endif; ?>

                                                    <div class="flex-grow-1">
                                                        <div class="fw-semibold text-white"><?php echo htmlspecialchars($diag['immatriculation'] ?? ('ID ' . (int)($diag['id_vehicule'] ?? 0))); ?></div>
                                                        <div class="small text-info mb-1">
                                                            <?php
                                                            $marque = trim((string)($diag['vehicle_marque'] ?? ''));
                                                            $modele = trim((string)($diag['vehicle_modele'] ?? ''));
                                                            echo htmlspecialchars(trim($marque . ' ' . $modele));
                                                            ?>
                                                        </div>
                                                        <small class="text-light d-block" style="opacity:0.95; white-space: normal;">
                                                            <?php echo htmlspecialchars($diag['description_probleme'] ?? ''); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($diag['media_path'])): ?>
                                                    <?php
                                                    $mediaPath = (string)$diag['media_path'];
                                                    $mediaType = (string)($diag['media_type'] ?? '');
                                                    $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                                                    $isVideo = (strpos($mediaType, 'video/') === 0) || in_array($ext, ['mp4', 'webm', 'ogg'], true);
                                                    ?>
                                                    <?php if ($isVideo): ?>
                                                        <a href="<?php echo htmlspecialchars($mediaPath); ?>" target="_blank" class="text-info">Voir la vidéo du problème</a>
                                                    <?php else: ?>
                                                        <a href="<?php echo htmlspecialchars($mediaPath); ?>" target="_blank">
                                                            <img src="<?php echo htmlspecialchars($mediaPath); ?>" alt="Image du problème" style="width:160px; height:100px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,0.15);">
                                                        </a>
                                                        <div class="small text-success mt-1">Image du problème</div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-soft small">Aucune image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($diag['gravite'] ?? 'Faible'); ?></td>
                                            <td>
                                                <?php $isDone = (($diag['status'] ?? '') === 'terminé'); ?>
                                                <span class="badge rounded-pill <?php echo $isDone ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                    <?php echo $isDone ? 'Terminé' : 'En cours'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <form method="POST" action="" enctype="multipart/form-data" class="d-inline-flex align-items-center gap-2">
                                                    <input type="hidden" name="action" value="add_media">
                                                    <input type="hidden" name="id_diagnostic" value="<?php echo (int)$diag['id_diagnostic']; ?>">
                                                    <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicleId; ?>">
                                                    <input type="file" name="media_file" accept="image/*,video/*" class="form-control form-control-sm bg-dark text-white border-secondary" style="max-width: 190px;">
                                                    <button type="submit" class="btn btn-sm btn-link text-light p-0" title="Ajouter média" aria-label="Ajouter média">
                                                        <i class="bi bi-paperclip fs-5"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-soft {
    color: #c9daee !important;
}

#diagnosticForm .form-label,
.form-label {
    color: #e7f1ff;
}

.form-control::placeholder,
.form-select::placeholder,
textarea::placeholder,
input::placeholder {
    color: #b6cbe4 !important;
    opacity: 1;
}
</style>

<?php require __DIR__ . '/layout_footer.php'; ?>
