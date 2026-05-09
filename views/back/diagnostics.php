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

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4" role="alert">
            Diagnostic mis à jour avec succès.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success border-0 rounded-3 mb-4" role="alert">
            Diagnostic supprimé avec succès.
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
            <h1 class="page-title text-white mb-0">Gestion diagnostic</h1>
            <p class="text-muted diagnostics-subtitle">Gérez les diagnostics d'atelier: consulter, modifier ou supprimer les entrées existantes.</p>
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
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau des diagnostics existants -->
    <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
        <h3 class="page-title text-white mb-0">Liste des diagnostics</h3>
    </div>

    <div class="card bg-dark border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            <?php if (!empty($diagnostics)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-dark mb-0">
                        <thead style="background-color: #2c3e50; color: #fff;">
                            <tr>
                                <th class="fw-bold">#ID&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th class="fw-bold">Véhicule&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th class="fw-bold">Description&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th class="fw-bold">Montant (DT)&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th class="fw-bold">Date&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th class="fw-bold">Statut&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th class="fw-bold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnostics as $diag): ?>
                                <tr class="align-middle">
                                    <td><span class="badge bg-info">#<?php echo htmlspecialchars((string)$diag['id_diagnostic']); ?></span></td>
                                    <td><?php echo htmlspecialchars((string)($diag['immatriculation'] ?? 'N/A')); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars(substr((string)($diag['description_probleme'] ?? ''), 0, 50)); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)($diag['montant_estime'] ?? '0')); ?></td>
                                    <td><?php echo htmlspecialchars((string)($diag['date_diagnostic'] ?? 'N/A')); ?></td>
                                    <td>
                                        <?php
                                            $status = strtolower((string)($diag['status'] ?? 'en attente'));
                                            $badgeClass = 'bg-secondary';
                                            if (strpos($status, 'termine') !== false || strpos($status, 'completed') !== false) {
                                                $badgeClass = 'bg-success';
                                            } elseif (strpos($status, 'en attente') !== false || strpos($status, 'pending') !== false) {
                                                $badgeClass = 'bg-warning';
                                            } elseif (strpos($status, 'refuse') !== false || strpos($status, 'rejected') !== false) {
                                                $badgeClass = 'bg-danger';
                                            }
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editDiagnosticModal<?php echo (int)$diag['id_diagnostic']; ?>" title="Modifier">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteDiagnosticModal<?php echo (int)$diag['id_diagnostic']; ?>" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Modification -->
                                <div class="modal fade" id="editDiagnosticModal<?php echo (int)$diag['id_diagnostic']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content bg-dark border-secondary">
                                            <div class="modal-header border-secondary">
                                                <h5 class="modal-title text-white">Modifier diagnostic #<?php echo (int)$diag['id_diagnostic']; ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="index.php?action=diagnostics" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="update_diagnostic">
                                                <input type="hidden" name="id_diagnostic" value="<?php echo (int)$diag['id_diagnostic']; ?>">
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label text-white">Véhicule</label>
                                                            <select name="id_vehicule" class="form-select bg-dark border-secondary text-white" required>
                                                                <option value="">Choisir...</option>
                                                                <?php foreach (($vehicles ?? []) as $vehicle): ?>
                                                                    <option value="<?php echo (int)$vehicle['id']; ?>" <?php echo ((int)$vehicle['id'] === (int)$diag['id_vehicule']) ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars((string)$vehicle['immatriculation']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label text-white">Montant estimé (DT)</label>
                                                            <input type="number" step="0.01" min="0" name="montant_estime" class="form-control bg-dark border-secondary text-white" value="<?php echo htmlspecialchars((string)$diag['montant_estime']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label text-white">Date</label>
                                                            <input type="date" name="date_diagnostic" class="form-control bg-dark border-secondary text-white" value="<?php echo htmlspecialchars((string)$diag['date_diagnostic']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label text-white">Gravité</label>
                                                            <select name="gravite" class="form-select bg-dark border-secondary text-white">
                                                                <option value="Faible" <?php echo ((string)$diag['gravite'] === 'Faible') ? 'selected' : ''; ?>>Faible</option>
                                                                <option value="Moyenne" <?php echo ((string)$diag['gravite'] === 'Moyenne') ? 'selected' : ''; ?>>Moyenne</option>
                                                                <option value="Élevée" <?php echo ((string)$diag['gravite'] === 'Élevée') ? 'selected' : ''; ?>>Élevée</option>
                                                                <option value="Urgent" <?php echo ((string)$diag['gravite'] === 'Urgent') ? 'selected' : ''; ?>>Urgent</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label text-white">Description du problème</label>
                                                            <textarea name="description_probleme" rows="2" class="form-control bg-dark border-secondary text-white" required><?php echo htmlspecialchars((string)$diag['description_probleme']); ?></textarea>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label text-white">Résultat du diagnostic</label>
                                                            <textarea name="resultat" rows="2" class="form-control bg-dark border-secondary text-white" required><?php echo htmlspecialchars((string)$diag['resultat']); ?></textarea>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label text-white">Statut</label>
                                                            <select name="status" class="form-select bg-dark border-secondary text-white">
                                                                <option value="en attente" <?php echo ((string)$diag['status'] === 'en attente') ? 'selected' : ''; ?>>En attente</option>
                                                                <option value="en cours" <?php echo ((string)$diag['status'] === 'en cours') ? 'selected' : ''; ?>>En cours</option>
                                                                <option value="terminé" <?php echo ((string)$diag['status'] === 'terminé') ? 'selected' : ''; ?>>Terminé</option>
                                                                <option value="refusé" <?php echo ((string)$diag['status'] === 'refusé') ? 'selected' : ''; ?>>Refusé</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-secondary">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Suppression -->
                                <div class="modal fade" id="deleteDiagnosticModal<?php echo (int)$diag['id_diagnostic']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content bg-dark border-danger">
                                            <div class="modal-header border-danger">
                                                <h5 class="modal-title text-danger">Supprimer diagnostic?</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-white">Êtes-vous certain de vouloir supprimer le diagnostic #<?php echo (int)$diag['id_diagnostic']; ?> pour le véhicule <strong><?php echo htmlspecialchars((string)$diag['immatriculation']); ?></strong>?</p>
                                                <p class="text-warning small">Cette action est irréversible.</p>
                                            </div>
                                            <form action="index.php?action=diagnostics" method="POST">
                                                <div class="modal-footer border-secondary">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <input type="hidden" name="action" value="delete_diagnostic">
                                                    <input type="hidden" name="id_diagnostic" value="<?php echo (int)$diag['id_diagnostic']; ?>">
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center">
                    <p class="text-muted">Aucun diagnostic trouvé.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<script>
(function() {
    'use strict';
    
    // Formulaire validation
    const form = document.getElementById('workshopDiagnosticForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }

    // Tri du tableau
    const table = document.querySelector('table.table-dark');
    if (table) {
        const headers = table.querySelectorAll('thead th');
        const tbody = table.querySelector('tbody');
        let currentSort = { index: -1, ascending: true };
        
        headers.forEach((header, index) => {
            if (index < headers.length - 1) { // Exclure la dernière colonne "Actions"
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    
                    // Déterminer la direction du tri
                    if (currentSort.index === index) {
                        currentSort.ascending = !currentSort.ascending;
                    } else {
                        currentSort.index = index;
                        currentSort.ascending = true;
                        // Retirer la classe de tri des autres headers
                        headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                    }
                    
                    // Ajouter la classe de tri au header actuel
                    header.classList.remove('sort-asc', 'sort-desc');
                    header.classList.add(currentSort.ascending ? 'sort-asc' : 'sort-desc');
                    
                    rows.sort((a, b) => {
                        // Extraire le texte brut de la cellule (sans HTML)
                        let aCell = a.children[index];
                        let bCell = b.children[index];
                        
                        let aVal = aCell.textContent.trim();
                        let bVal = bCell.textContent.trim();
                        
                        // Supprimer les espaces inutiles et les balises de texte
                        aVal = aVal.replace(/\s+/g, ' ');
                        bVal = bVal.replace(/\s+/g, ' ');
                        
                        // Essayer de convertir en nombre
                        const aNum = parseFloat(aVal.replace(/[^\d.,-]/g, ''));
                        const bNum = parseFloat(bVal.replace(/[^\d.,-]/g, ''));
                        
                        // Si ce sont des nombres
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return currentSort.ascending ? aNum - bNum : bNum - aNum;
                        }
                        
                        // Vérifier si c'est une date (format YYYY-MM-DD)
                        const dateRegex = /(\d{4})-(\d{2})-(\d{2})/;
                        const aMatch = aVal.match(dateRegex);
                        const bMatch = bVal.match(dateRegex);
                        
                        if (aMatch && bMatch) {
                            const aDate = new Date(aMatch[0]);
                            const bDate = new Date(bMatch[0]);
                            return currentSort.ascending ? aDate - bDate : bDate - aDate;
                        }
                        
                        // Sinon, tri alphanumérique (sensible à la casse)
                        const comparison = aVal.localeCompare(bVal, 'fr', { numeric: true });
                        return currentSort.ascending ? comparison : -comparison;
                    });
                    
                    // Réinsérer les lignes triées
                    rows.forEach(row => tbody.appendChild(row));
                });
            }
        });
    }
})();
</script>

<style>
#workshopDiagnosticForm .workshop-label {
    color: var(--text-700) !important;
    font-weight: 700;
}

#workshopDiagnosticForm .invalid-feedback {
    color: var(--danger);
    font-weight: 600;
}

table.table-dark thead th {
    user-select: none;
    cursor: pointer;
}

table.table-dark thead th:hover {
    background-color: var(--surface-2);
    transition: background-color 0.2s;
}

</style>

<?php require __DIR__ . '/layout_footer.php'; ?>
