<?php 
$pageTitle = 'Gestion des Diagnostics';
$action = 'diagnostics';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title text-white mb-0">Gestion des Diagnostics</h1>
            <p class="text-muted diagnostics-subtitle">Consulter et gérer tous les rapports techniques.</p>
        </div>
        <button id="newDiagnosticBtn" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDiagnosticModal">
            <i class="bi bi-plus-lg me-2"></i>Nouveau Diagnostic
        </button>
    </div>

    <!-- Filters -->
    <div class="card bg-dark border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="search-field">
                        <i class="bi bi-search search-field-icon" aria-hidden="true"></i>
                        <input type="text" id="searchInput" class="form-control bg-transparent border-secondary text-white search-field-input" placeholder="Rechercher un véhicule...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filterGravite" class="form-select bg-dark border-secondary text-white shadow-none">
                        <option value="" class="bg-dark text-white">Toutes les gravités</option>
                        <option value="Faible" class="bg-dark text-white">Faible</option>
                        <option value="Moyen" class="bg-dark text-white">Moyen</option>
                        <option value="Élevé" class="bg-dark text-white">Élevé</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnostics Table -->
    <div class="card bg-dark border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="bg-secondary bg-opacity-10">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Véhicule</th>
                        <th>Description</th>
                        <th>Résultat</th>
                        <th>Gravité</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="diagnosticTable">
                    <?php if (empty($diagnostics)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Aucun diagnostic trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($diagnostics as $diag): ?>
                            <tr>
                                <td class="ps-4 text-white small">#<?php echo $diag['id_diagnostic'] ?? ''; ?></td>
                                <td>
                                    <div class="fw-bold text-white"><?php echo htmlspecialchars(($diag['marque'] ?? '') . ' ' . ($diag['modele'] ?? '')); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($diag['immatriculation'] ?? ''); ?></div>
                                </td>
                                <td><div class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($diag['description_probleme'] ?? 'Non spécifié'); ?></div></td>
                                <td><div class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($diag['resultat'] ?? 'En attente'); ?></div></td>
                                <td>
                                    <?php 
                                    $gravite = $diag['gravite'] ?? 'Faible';
                                    $badgeClass = 'bg-success';
                                    if ($gravite === 'Moyen') $badgeClass = 'bg-warning text-dark';
                                    if ($gravite === 'Élevé') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3"><?php echo $gravite; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status = $diag['status'] ?? 'en attente';
                                    $statusClass = $status === 'terminé' ? 'sg-status-badge sg-status-done' : 'sg-status-badge sg-status-waiting';
                                    ?>
                                    <span class="badge rounded-pill px-3 <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                                </td>
                                <td class="text-white"><?php echo isset($diag['date_diagnostic']) ? date('d/m/Y', strtotime($diag['date_diagnostic'])) : ''; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-light border-0" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                                            <li>
                                                <a
                                                    class="dropdown-item js-edit-diagnostic"
                                                    href="#"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#addDiagnosticModal"
                                                    data-id="<?php echo (int)($diag['id_diagnostic'] ?? 0); ?>"
                                                    data-id-vehicule="<?php echo (int)($diag['id_vehicule'] ?? 0); ?>"
                                                    data-gravite="<?php echo htmlspecialchars($diag['gravite'] ?? 'Faible', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-description="<?php echo htmlspecialchars($diag['description_probleme'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-resultat="<?php echo htmlspecialchars($diag['resultat'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-montant="<?php echo htmlspecialchars((string)($diag['montant_estime'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-date="<?php echo htmlspecialchars($diag['date_diagnostic'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-status="<?php echo htmlspecialchars($diag['status'] ?? 'en attente', ENT_QUOTES, 'UTF-8'); ?>"
                                                ><i class="bi bi-pencil me-2"></i>Modifier</a>
                                            </li>
                                            <li><a class="dropdown-item" href="index.php?action=generateDiagnosticPdf&id=<?php echo (int)($diag['id_diagnostic'] ?? 0); ?>"><i class="bi bi-file-earmark-pdf me-2"></i>Générer Devis PDF</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="" method="POST" onsubmit="return confirm('Supprimer ce diagnostic ?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_diagnostic" value="<?php echo $diag['id_diagnostic'] ?? ''; ?>">
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Supprimer</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Diagnostic Modal -->
<div class="modal fade" id="addDiagnosticModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-0 shadow">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Nouveau Diagnostic</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="diagForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id_diagnostic" value="">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Véhicule</label>
                            <select name="id_vehicule" class="form-select bg-secondary bg-opacity-10 border-0 text-white p-3" required>
                                <option value="">Sélectionner un véhicule</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['marque'] . ' ' . $v['modele'] . ' - ' . $v['immatriculation']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Gravité</label>
                            <select name="gravite" class="form-select bg-secondary bg-opacity-10 border-0 text-white p-3" required>
                                <option value="Faible">Faible</option>
                                <option value="Moyen">Moyen</option>
                                <option value="Élevé">Élevé</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Description du problème</label>
                            <textarea name="description_probleme" class="form-control bg-secondary bg-opacity-10 border-0 text-white p-3" rows="3" required placeholder="Décrivez les symptômes remarqués..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small">Résultat du diagnostic</label>
                            <textarea name="resultat" class="form-control bg-secondary bg-opacity-10 border-0 text-white p-3" rows="3" placeholder="Causes identifiées et réparations nécessaires..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Montant estimé (DT)</label>
                            <input type="number" step="0.01" name="montant_estime" class="form-control bg-secondary bg-opacity-10 border-0 text-white p-3" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small">Date</label>
                            <input type="date" name="date_diagnostic" class="form-control bg-secondary bg-opacity-10 border-0 text-white p-3" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6 d-none" id="statusFieldWrap">
                            <label class="form-label text-muted small">Status</label>
                            <select name="status" class="form-select bg-secondary bg-opacity-10 border-0 text-white p-3">
                                <option value="en attente">En attente</option>
                                <option value="terminé">Terminé</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary p-4 pt-0">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="diagSubmitBtn" class="btn btn-primary rounded-pill px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#diagnosticTable tr');
    rows.forEach(row => {
        let text = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

document.getElementById('filterGravite').addEventListener('change', function() {
    let filter = this.value;
    let rows = document.querySelectorAll('#diagnosticTable tr');
    rows.forEach(row => {
        let gravite = row.querySelector('td:nth-child(5) .badge').innerText.trim();
        row.style.display = (filter === "" || gravite === filter) ? '' : 'none';
    });
});

const diagModalElement = document.getElementById('addDiagnosticModal');
const diagForm = document.getElementById('diagForm');
const modalTitle = diagModalElement.querySelector('.modal-title');
const submitBtn = document.getElementById('diagSubmitBtn');
const statusFieldWrap = document.getElementById('statusFieldWrap');
const newDiagnosticBtn = document.getElementById('newDiagnosticBtn');

function resetDiagFormToAdd() {
    diagForm.reset();
    diagForm.querySelector('input[name="action"]').value = 'add';
    diagForm.querySelector('input[name="id_diagnostic"]').value = '';
    diagForm.querySelector('input[name="date_diagnostic"]').value = '<?php echo date('Y-m-d'); ?>';
    statusFieldWrap.classList.add('d-none');
    modalTitle.innerHTML = '<i class="bi bi-plus-circle me-2 text-primary"></i>Nouveau Diagnostic';
    submitBtn.textContent = 'Enregistrer';
}

document.querySelectorAll('.js-edit-diagnostic').forEach((btn) => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();

        diagForm.querySelector('input[name="action"]').value = 'edit';
        diagForm.querySelector('input[name="id_diagnostic"]').value = this.dataset.id || '';
        diagForm.querySelector('select[name="id_vehicule"]').value = this.dataset.idVehicule || '';
        diagForm.querySelector('select[name="gravite"]').value = this.dataset.gravite || 'Faible';
        diagForm.querySelector('textarea[name="description_probleme"]').value = this.dataset.description || '';
        diagForm.querySelector('textarea[name="resultat"]').value = this.dataset.resultat || '';
        diagForm.querySelector('input[name="montant_estime"]').value = this.dataset.montant || '0';
        diagForm.querySelector('input[name="date_diagnostic"]').value = this.dataset.date || '<?php echo date('Y-m-d'); ?>';
        diagForm.querySelector('select[name="status"]').value = this.dataset.status || 'en attente';

        statusFieldWrap.classList.remove('d-none');
        modalTitle.innerHTML = '<i class="bi bi-pencil-square me-2 text-primary"></i>Modifier Diagnostic';
        submitBtn.textContent = 'Mettre à jour';
    });
});

newDiagnosticBtn.addEventListener('click', resetDiagFormToAdd);
diagModalElement.addEventListener('hidden.bs.modal', resetDiagFormToAdd);
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
