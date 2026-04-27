<?php
/**
 * Vue: Gestion des Interventions - Admin
 * Affiche la liste complète des interventions avec gestion du cycle de vie
 * Variables: $interventions (list), $statistiques (stats)
 */
?>
<?php
$pageTitle = 'Gestion des Interventions';
$action = 'admin_interventions';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid mt-4 admin-interventions-page">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
            <h1 class="text-light"><i class="fas fa-tools me-2"></i>Gestion des Interventions</h1>
            <p class="text-muted">Gerez le cycle de vie des interventions</p>
            </div>
            <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#addInterventionModal"
                    <?php echo empty($diagnosticsDisponibles ?? []) ? 'disabled' : ''; ?>>
                <i class="bi bi-plus-circle me-1"></i>Ajouter une intervention
            </button>
        </div>
    </div>

    <?php if (empty($diagnosticsDisponibles ?? [])): ?>
        <div class="alert alert-secondary" role="alert">
            <i class="bi bi-info-circle me-1"></i>
            Aucun diagnostic disponible sans intervention.
        </div>
    <?php endif; ?>

    <div class="modal fade" id="addInterventionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-dark border-light">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="modal-title text-white"><i class="bi bi-plus-circle me-2"></i>Ajouter une intervention</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-light">
                    <div class="mb-3">
                        <label for="modalDiagnosticSelect" class="form-label">Choisir un diagnostic existant</label>
                        <select id="modalDiagnosticSelect" class="form-select bg-dark text-white border-secondary" required>
                            <option value="">Selectionner un diagnostic...</option>
                            <?php foreach (($diagnosticsDisponibles ?? []) as $diag): ?>
                                <option value="<?php echo (int)($diag['id_diagnostic'] ?? 0); ?>">
                                    #<?php echo (int)($diag['id_diagnostic'] ?? 0); ?> - <?php echo htmlspecialchars((string)($diag['immatriculation'] ?? 'Vehicule inconnu')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="card diag-info-card mb-4">
                        <div class="card-body">
                            <h6 class="diag-info-title mb-3">Informations du diagnostic</h6>
                            <div class="row g-3 small">
                                <div class="col-md-4"><span class="diag-info-label">ID Diagnostic:</span><br><strong id="diagInfoId">-</strong></div>
                                <div class="col-md-4"><span class="diag-info-label">Vehicule:</span><br><strong id="diagInfoVehicle">-</strong></div>
                                <div class="col-md-4"><span class="diag-info-label">Gravite:</span><br><strong id="diagInfoSeverity">-</strong></div>
                                <div class="col-md-4"><span class="diag-info-label">Date:</span><br><strong id="diagInfoDate">-</strong></div>
                                <div class="col-md-4"><span class="diag-info-label">Montant estime:</span><br><strong id="diagInfoAmount">-</strong></div>
                                <div class="col-12"><span class="diag-info-label">Probleme:</span><br><span id="diagInfoProblem">-</span></div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="index.php?action=create_intervention" id="quickInterventionForm" novalidate>
                        <input type="hidden" name="action" value="create_intervention">
                        <input type="hidden" name="id_diagnostic" id="modalDiagnosticId" value="">

                        <div class="mb-3">
                            <label for="modalInterventionType" class="form-label">Type d'intervention</label>
                            <select class="form-select bg-dark text-white border-secondary" id="modalInterventionType" name="id_type" required>
                                <option value="">Selectionner un type</option>
                                <?php foreach (($types_intervention ?? []) as $type): ?>
                                    <option value="<?php echo (int)($type['id_type'] ?? 0); ?>"><?php echo htmlspecialchars((string)($type['nom'] ?? '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="modalDescriptionTravail" class="form-label">Description du travail</label>
                            <textarea class="form-control bg-dark text-white border-secondary" id="modalDescriptionTravail" name="description_travail" rows="4" placeholder="Decrivez les travaux a effectuer..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="modalCoutInitial" class="form-label">Cout initial estime (DT)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" id="modalCoutInitial" name="cout_initial" step="0.01" min="0" required>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" <?php echo empty($diagnosticsDisponibles ?? []) ? 'disabled' : ''; ?>><i class="bi bi-check2-circle me-1"></i>Creer l'intervention</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($statistiques)): ?>
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-info h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-info mb-2">Planifiees</h6>
                        <h2 class="text-light"><?php echo $statistiques['planifiees'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-warning h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-warning mb-2">En Cours</h6>
                        <h2 class="text-light"><?php echo $statistiques['en_cours'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-success h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-success mb-2">Terminees</h6>
                        <h2 class="text-light"><?php echo $statistiques['terminees'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title text-secondary mb-2">Total</h6>
                        <h2 class="text-light"><?php echo $statistiques['total'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['intervention_created'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><strong>Intervention creee!</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['intervention_updated']) || isset($_GET['status_updated'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i><strong>Mise a jour effectuee!</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['quote_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-file-invoice-dollar me-2"></i><strong>Devis mis a jour avec succes.</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['quote_email_sent'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-envelope me-2"></i><strong>Email devis envoye au client.</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mail_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-triangle-exclamation me-2"></i>
            <strong>Echec d'envoi email.</strong>
            <?php echo htmlspecialchars((string)($_GET['mail_msg'] ?? 'Verifiez la configuration SMTP de PHP/XAMPP.')); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['message_sent'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-comment-dots me-2"></i><strong>Message envoye au client.</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card bg-dark border-light">
        <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="mb-0 text-white"><i class="fas fa-list me-2"></i>Liste des Interventions</h5>
        </div>

        <div class="card-body p-0">
            <?php if (!empty($interventions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-dark mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>#ID</th>
                                <th>Diagnostic</th>
                                <th>Type</th>
                                <th>Vehicule</th>
                                <th>Couts</th>
                                <th>Statut</th>
                                <th>Dates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interventions as $inter): ?>
                                <?php
                                $statut = (string)($inter['statut'] ?? '');
                                $statusLabel = ucwords(str_replace('_', ' ', $statut));
                                $badgeClass = 'bg-secondary';
                                $icon = 'fa-clock';
                                if ($statut === 'planifiée') {
                                    $badgeClass = 'bg-info';
                                    $icon = 'fa-calendar';
                                    $statusLabel = 'Planifiee';
                                } elseif ($statut === 'en_cours') {
                                    $badgeClass = 'bg-warning';
                                    $icon = 'fa-spinner';
                                    $statusLabel = 'En cours';
                                } elseif ($statut === 'terminée') {
                                    $badgeClass = 'bg-success';
                                    $icon = 'fa-check-circle';
                                    $statusLabel = 'Terminee';
                                }
                                ?>
                                <tr class="align-middle">
                                    <td><span class="badge bg-info">#<?php echo (int)$inter['id_intervention']; ?></span></td>
                                    <td><strong class="text-light">#<?php echo (int)$inter['id_diagnostic']; ?></strong></td>
                                    <td><small class="text-info fw-semibold"><?php echo htmlspecialchars((string)($inter['type_nom'] ?? '')); ?></small></td>
                                    <td>
                                        <small class="text-light">
                                            <strong><?php echo htmlspecialchars((string)($inter['immatriculation'] ?? '')); ?></strong><br>
                                            <?php echo htmlspecialchars((string)($inter['vehicle_marque'] ?? '')); ?>
                                            <?php echo htmlspecialchars((string)($inter['vehicle_modele'] ?? '')); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-info"><strong>Initial:</strong> <?php echo number_format((float)($inter['cout_initial'] ?? 0), 2, ',', ' '); ?> DT</span><br>
                                            <span class="text-success"><strong>Final:</strong>
                                                <?php if (isset($inter['cout_final']) && $inter['cout_final'] !== null): ?>
                                                    <?php echo number_format((float)$inter['cout_final'], 2, ',', ' '); ?> DT
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </span>
                                            <br>
                                            <span class="text-warning"><strong>Devis:</strong> <?php echo htmlspecialchars((string)($inter['statut_devis'] ?? 'en_attente')); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badgeClass; ?>">
                                            <i class="fas <?php echo $icon; ?> me-1"></i><?php echo htmlspecialchars($statusLabel); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="inter-dates-cell">
                                            <?php if (!empty($inter['date_debut'])): ?>
                                                <span class="inter-date-label">Debut:</span>
                                                <span class="inter-date-value"><?php echo date('d/m/Y', strtotime((string)$inter['date_debut'])); ?></span>
                                            <?php else: ?>
                                                <span class="inter-date-empty">-</span>
                                            <?php endif; ?><br>
                                            <?php if (!empty($inter['date_fin'])): ?>
                                                <span class="inter-date-label">Fin:</span>
                                                <span class="inter-date-value inter-date-finish"><?php echo date('d/m/Y', strtotime((string)$inter['date_fin'])); ?></span>
                                            <?php else: ?>
                                                <span class="inter-date-empty">-</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2 inter-actions" role="group" aria-label="Actions intervention">
                                            <a href="index.php?action=export_quote_pdf&id=<?php echo (int)$inter['id_intervention']; ?>"
                                               class="btn btn-sm btn-outline-info inter-action-btn"
                                               title="Generer le devis PDF client">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>Devis PDF
                                            </a>

                                            <button type="button" class="btn btn-sm btn-outline-warning inter-action-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#statutModal"
                                                title="Modifier le statut"
                                                onclick="setStatutData(<?php echo (int)$inter['id_intervention']; ?>, '<?php echo htmlspecialchars((string)$inter['statut'], ENT_QUOTES); ?>', '<?php echo !empty($inter['date_debut']) ? date('Y-m-d', strtotime((string)$inter['date_debut'])) : ''; ?>', '<?php echo !empty($inter['date_fin']) ? date('Y-m-d', strtotime((string)$inter['date_fin'])) : ''; ?>')">
                                                <i class="bi bi-pencil-square me-1"></i>Statut
                                            </button>

                                            <?php if (($inter['statut'] ?? '') !== 'terminée'): ?>
                                                <button type="button" class="btn btn-sm btn-success inter-action-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#terminateModal"
                                                    title="Terminer l'intervention"
                                                    onclick="setTerminateData(<?php echo (int)$inter['id_intervention']; ?>, <?php echo (float)($inter['cout_initial'] ?? 0); ?>)">
                                                    <i class="bi bi-check2-circle me-1"></i>Terminer
                                                </button>
                                            <?php else: ?>
                                                    <a href="index.php?action=export_intervention_pdf&id=<?php echo (int)$inter['id_intervention']; ?>"
                                                   class="btn btn-sm btn-outline-info inter-action-btn"
                                                   title="Exporter la fiche PDF intervention">
                                                    <i class="bi bi-download me-1"></i>Exporter PDF
                                                </a>
                                            <?php endif; ?>

                                            <form method="POST" action="index.php?action=admin_interventions" class="d-flex align-items-center gap-1">
                                                <input type="hidden" name="action_type" value="send_quote_email">
                                                <input type="hidden" name="id_intervention" value="<?php echo (int)$inter['id_intervention']; ?>">
                                                <input type="email" name="client_email" class="form-control form-control-sm bg-dark text-white border-secondary" style="max-width: 180px;" placeholder="client@email.com" required>
                                                <button type="submit" class="btn btn-sm btn-outline-primary inter-action-btn" title="Envoyer le devis par email">
                                                    <i class="bi bi-envelope"></i>
                                                </button>
                                            </form>

                                            <?php $msgCount = isset($interventionMessages[(int)$inter['id_intervention']]) ? count($interventionMessages[(int)$inter['id_intervention']]) : 0; ?>
                                            <a href="index.php?action=messages&id=<?php echo (int)$inter['id_intervention']; ?>"
                                                    class="btn btn-sm btn-outline-light inter-action-btn"
                                                    title="Voir messages client/admin">
                                                <i class="bi bi-chat-dots me-1"></i>Messages (<?php echo (int)$msgCount; ?>)
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3 mb-0"><i class="fas fa-info-circle me-2"></i>Aucune intervention actuellement.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($interventions)): ?>
        <?php foreach ($interventions as $inter): ?>
            <?php
            $iid = (int)($inter['id_intervention'] ?? 0);
            $msgs = $interventionMessages[$iid] ?? [];
            ?>
            <div class="modal fade" id="messagesModal<?php echo $iid; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content bg-dark border-light">
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h5 class="modal-title text-white">
                                <i class="bi bi-chat-dots me-2"></i>Messages - Intervention #<?php echo $iid; ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="max-height: 420px; overflow-y: auto;">
                            <?php if (empty($msgs)): ?>
                                <div class="text-muted">Aucun message pour cette intervention.</div>
                            <?php else: ?>
                                <?php foreach ($msgs as $msg): ?>
                                    <?php $isClient = (($msg['expediteur'] ?? '') === 'client'); ?>
                                    <div class="d-flex mb-3 <?php echo $isClient ? 'justify-content-start' : 'justify-content-end'; ?>">
                                        <div class="p-3 rounded-3" style="max-width: 78%; <?php echo $isClient ? 'background: #eef2f7; color:#1b2430;' : 'background: linear-gradient(135deg, #1f8fff, #2563eb); color:#fff;'; ?>">
                                            <div class="small mb-1" style="opacity:.85;">
                                                <?php echo $isClient ? 'Client' : 'Admin'; ?>
                                                - <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime((string)$msg['date_envoi']))); ?>
                                            </div>
                                            <div><?php echo nl2br(htmlspecialchars((string)$msg['contenu'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer border-top border-secondary">
                            <form method="POST" action="index.php?action=admin_interventions" class="w-100 d-flex gap-2">
                                <input type="hidden" name="action" value="send_message">
                                <input type="hidden" name="sender" value="admin">
                                <input type="hidden" name="id_intervention" value="<?php echo $iid; ?>">
                                <input type="text" name="contenu" class="form-control bg-dark text-white border-secondary" placeholder="Repondre au client..." required>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-send"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="statutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white"><i class="fas fa-edit me-2"></i>Modifier le Statut</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_interventions">
                <div class="modal-body text-light">
                    <input type="hidden" name="id_intervention" id="statutInterId">
                    <input type="hidden" name="action_type" value="update_statut">

                    <div class="mb-3">
                        <label for="newStatut" class="form-label">Nouveau Statut</label>
                        <select class="form-select bg-secondary text-light border-light" id="newStatut" name="statut" required>
                            <option value="planifiée">Planifiee</option>
                            <option value="en_cours">En cours</option>
                            <option value="terminée">Terminee</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="dateDebut" class="form-label">Date de Debut</label>
                        <input type="date" class="form-control bg-secondary text-light border-light" id="dateDebut" name="date_debut" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="dateFinStatus" class="form-label">Date de Fin</label>
                        <input type="date" class="form-control bg-secondary text-light border-light" id="dateFinStatus" name="date_fin" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Mettre a Jour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="terminateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                <h5 class="modal-title text-white"><i class="fas fa-check-circle me-2"></i>Terminer l'Intervention</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_interventions">
                <div class="modal-body text-light">
                    <input type="hidden" name="id_intervention" id="terminateInterId">
                    <input type="hidden" name="action_type" value="terminate">
                    <input type="hidden" name="statut" value="terminée">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Remplissez les informations finales de l'intervention.
                    </div>

                    <div class="mb-3">
                        <label for="coutFinal" class="form-label">Cout Final (DT)</label>
                        <input type="number" class="form-control bg-secondary text-light border-light" id="coutFinal" name="cout_final" step="0.01" min="0" required placeholder="Cout reel de l'intervention">
                        <small class="text-muted" id="coutMinMessage"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Statut final</label>
                        <input type="text" class="form-control bg-secondary text-light border-light" value="Terminee" readonly>
                        <small class="text-muted">Le statut final est fixe a Terminee.</small>
                    </div>

                    <div class="mb-3">
                        <label for="dateFin" class="form-label">Date de Fin</label>
                        <input type="date" class="form-control bg-secondary text-light border-light" id="dateFin" name="date_fin" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i>Terminer l'Intervention</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const diagnosticsNoIntervention = <?php echo json_encode(array_values($diagnosticsDisponibles ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

function formatDiagnosticDate(value) {
    if (!value) {
        return '-';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return date.toLocaleDateString('fr-FR');
}

function applyDiagnosticSelection(diagnosticId) {
    const diag = diagnosticsNoIntervention.find((item) => Number(item.id_diagnostic) === Number(diagnosticId));
    const idField = document.getElementById('modalDiagnosticId');
    const problemField = document.getElementById('modalDescriptionTravail');
    const amountField = document.getElementById('modalCoutInitial');

    if (!diag) {
        idField.value = '';
        document.getElementById('diagInfoId').textContent = '-';
        document.getElementById('diagInfoVehicle').textContent = '-';
        document.getElementById('diagInfoSeverity').textContent = '-';
        document.getElementById('diagInfoDate').textContent = '-';
        document.getElementById('diagInfoAmount').textContent = '-';
        document.getElementById('diagInfoProblem').textContent = '-';
        problemField.value = '';
        amountField.value = '';
        return;
    }

    idField.value = String(diag.id_diagnostic || '');
    document.getElementById('diagInfoId').textContent = '#' + String(diag.id_diagnostic || '-');
    document.getElementById('diagInfoVehicle').textContent = String(diag.immatriculation || 'Vehicule inconnu');
    document.getElementById('diagInfoSeverity').textContent = String(diag.gravite || '-');
    document.getElementById('diagInfoDate').textContent = formatDiagnosticDate(diag.date_diagnostic || '');
    const amount = Number(diag.montant_estime || 0);
    document.getElementById('diagInfoAmount').textContent = Number.isNaN(amount) ? '-' : amount.toFixed(2) + ' DT';
    document.getElementById('diagInfoProblem').textContent = String(diag.description_probleme || '-');

    problemField.value = 'Intervention suite au diagnostic: ' + String(diag.description_probleme || '');
    amountField.value = Number.isNaN(amount) ? '' : amount.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function () {
    const modalSelect = document.getElementById('modalDiagnosticSelect');
    const quickForm = document.getElementById('quickInterventionForm');
    const addModal = document.getElementById('addInterventionModal');

    if (modalSelect) {
        modalSelect.addEventListener('change', function () {
            applyDiagnosticSelection(modalSelect.value);
        });
    }

    if (addModal) {
        addModal.addEventListener('show.bs.modal', function () {
            const hasItems = diagnosticsNoIntervention.length > 0;
            if (hasItems && modalSelect && !modalSelect.value) {
                modalSelect.value = String(diagnosticsNoIntervention[0].id_diagnostic || '');
            }
            applyDiagnosticSelection(modalSelect ? modalSelect.value : '');
        });
    }

    if (quickForm) {
        quickForm.addEventListener('submit', function (event) {
            if (!quickForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            quickForm.classList.add('was-validated');
        }, false);
    }
});

function setStatutData(interventionId, currentStatut, currentDateDebut, currentDateFin) {
    document.getElementById('statutInterId').value = interventionId;
    document.getElementById('newStatut').value = currentStatut;
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateDebut').value = currentDateDebut || today;
    document.getElementById('dateFinStatus').value = currentDateFin || today;
}

function setTerminateData(interventionId, coutInitial) {
    document.getElementById('terminateInterId').value = interventionId;
    document.getElementById('coutFinal').setAttribute('min', coutInitial);
    document.getElementById('coutMinMessage').textContent = 'Cout minimum: ' + parseFloat(coutInitial).toFixed(2) + ' DT';
    document.getElementById('dateFin').value = new Date().toISOString().split('T')[0];
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(8, 188, 232, 0.12) !important;
}

.badge {
    font-size: 0.85rem;
    padding: 0.4rem 0.6rem;
}

.inter-actions {
    min-width: 210px;
}

.inter-action-btn {
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 8px;
    padding: 0.36rem 0.62rem;
    white-space: nowrap;
}

.inter-action-btn i {
    font-size: 0.85rem;
}

.inter-dates-cell {
    color: #dce9ff;
    font-size: 0.95rem;
}

.inter-date-label {
    color: #b8cced;
    font-weight: 700;
}

.inter-date-value {
    color: #f3f8ff;
    font-weight: 700;
}

.inter-date-finish {
    color: #8ef0b3;
}

.inter-date-empty {
    color: #8fb0d8;
    font-weight: 600;
}

#addInterventionModal .modal-content {
    border-radius: 14px;
}

#addInterventionModal .diag-info-card {
    background: rgba(87, 168, 255, 0.08);
    border: 1px solid rgba(87, 168, 255, 0.25);
    border-radius: 12px;
}

#addInterventionModal .diag-info-title {
    color: #d9e9ff;
    font-weight: 700;
}

#addInterventionModal .diag-info-label {
    color: #b8cced;
    font-weight: 600;
}

#addInterventionModal #diagInfoProblem,
#addInterventionModal #diagInfoId,
#addInterventionModal #diagInfoVehicle,
#addInterventionModal #diagInfoSeverity,
#addInterventionModal #diagInfoDate,
#addInterventionModal #diagInfoAmount {
    color: #f3f8ff;
}
</style>

<?php require __DIR__ . '/layout_footer.php'; ?>
