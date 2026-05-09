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
                        <div class="modal-header" style="background: linear-gradient(135deg, #d65b4c 0%, #b33f31 100%);">
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
                                    <?php echo htmlspecialchars((string)($diag['immatriculation'] ?? 'Vehicule inconnu')); ?>
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
                            <label class="form-label">Type d'intervention</label>
                            <div class="bg-dark border border-secondary rounded p-3">
                                <div class="d-flex flex-wrap gap-3">
                                    <?php foreach (($types_intervention ?? []) as $type): ?>
                                        <div class="form-check">
                                            <input class="form-check-input intervention-checkbox" type="checkbox" id="type_<?php echo (int)($type['id_type'] ?? 0); ?>" name="id_type[]" value="<?php echo (int)($type['id_type'] ?? 0); ?>" data-type-name="<?php echo htmlspecialchars((string)($type['nom'] ?? '')); ?>" data-price="<?php echo htmlspecialchars(number_format((float)($type['prix'] ?? 0), 2, '.', '')); ?>">
                                            <label class="form-check-label text-white" for="type_<?php echo (int)($type['id_type'] ?? 0); ?>">
                                                <?php echo htmlspecialchars((string)($type['nom'] ?? '')); ?>
                                                <span class="text-muted">(<?php echo number_format((float)($type['prix'] ?? 0), 2, ',', ' '); ?> DT)</span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 p-3 bg-dark border border-info rounded">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label text-info fw-bold mb-0">Estimation de prix</label>
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="h5 text-info" id="totalEstimation">0 DT</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="modalDescriptionTravail" class="form-label">Description du travail</label>
                            <textarea class="form-control bg-dark text-white border-secondary" id="modalDescriptionTravail" name="description_travail" rows="4" placeholder="Decrivez les travaux a effectuer..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="modalCoutInitial" class="form-label">Cout initial estime (DT)</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" id="modalCoutInitial" name="cout_initial" step="0.01" min="0.01" required>
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

    <?php if (isset($_GET['type_prices_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-tags me-2"></i><strong>Prix des types mis a jour avec succes.</strong>
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

    <!-- Titre Liste des Interventions -->
    <div class="d-flex justify-content-between align-items-center mb-3 mt-5">
        <h3 class="page-title text-white mb-0">Liste des Interventions</h3>
    </div>

    <div class="card bg-dark border-light">
        <div class="card-body p-0">
            <?php if (!empty($interventions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-dark mb-0">
                        <thead style="background-color: #2c3e50; color: #fff;">
                            <tr>
                                <th>#ID&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th>Diagnostic&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th>Type&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th>Vehicule&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th>Couts&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th>Statut&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
                                <th>Dates&nbsp;<span style="font-size: 0.65rem; opacity: 0.7;">▼</span></th>
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

                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-outline-primary inter-action-btn inter-action-plus" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                    +
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editInterventionInfoModalAdmin"
                                                            data-id="<?php echo (int)$inter['id_intervention']; ?>"
                                                            data-description="<?php echo htmlspecialchars((string)($inter['description_travail'] ?? ''), ENT_QUOTES); ?>"
                                                            data-type-id="<?php echo (int)($inter['id_type'] ?? 0); ?>"
                                                            data-cout="<?php echo htmlspecialchars((string)($inter['cout_initial'] ?? 0), ENT_QUOTES); ?>"
                                                            data-statut="<?php echo htmlspecialchars((string)($inter['statut'] ?? ''), ENT_QUOTES); ?>"
                                                            data-date-debut="<?php echo htmlspecialchars((string)($inter['date_debut'] ?? ''), ENT_QUOTES); ?>"
                                                            data-date-fin="<?php echo htmlspecialchars((string)($inter['date_fin'] ?? ''), ENT_QUOTES); ?>"
                                                            onclick="setAdminEditInterventionData(this)">
                                                            <i class="bi bi-pencil-square me-2"></i>Modifier
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#uploadInterventionMediaModalAdmin"
                                                            data-id="<?php echo (int)$inter['id_intervention']; ?>"
                                                            onclick="setAdminUploadInterventionData(this)">
                                                            <i class="bi bi-image me-2"></i>Photo / Document
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>

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

                                                <form method="POST" action="index.php?action=admin_interventions" class="d-flex align-items-center gap-1">
                                                    <input type="hidden" name="action_type" value="send_intervention_info">
                                                    <input type="hidden" name="id_intervention" value="<?php echo (int)$inter['id_intervention']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary inter-action-btn" title="Envoyer les informations de l'intervention au client">
                                                        <i class="bi bi-info-circle me-1"></i>Infos
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
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content bg-dark border-light" style="max-height: calc(100vh - 2rem); display: flex; flex-direction: column;">
                        <div class="modal-header" style="background: linear-gradient(135deg, #d65b4c 0%, #b33f31 100%);">
                            <h5 class="modal-title text-white">
                                <i class="bi bi-chat-dots me-2"></i>Messages - Intervention #<?php echo $iid; ?>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body messages-modal-body" style="flex: 1 1 auto; min-height: 0; overflow-y: auto;">
                            <?php if (empty($msgs)): ?>
                                <div class="text-muted">Aucun message pour cette intervention.</div>
                            <?php else: ?>
                                <?php foreach ($msgs as $msg): ?>
                                    <?php $isClient = (($msg['expediteur'] ?? '') === 'client'); ?>
                                    <div class="d-flex mb-3 <?php echo $isClient ? 'justify-content-start' : 'justify-content-end'; ?>">
                                        <div class="p-3 rounded-3" style="max-width: 78%; <?php echo $isClient ? 'background: #eef2f7; color:#1b2430;' : 'background: linear-gradient(135deg, #d65b4c, #b33f31); color:#fff;'; ?>">
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
                        <div class="modal-footer border-top border-secondary" style="flex-shrink: 0;">
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
            <div class="modal fade" id="editQuoteModal<?php echo $iid; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content bg-dark border-light">
                        <div class="modal-header">
                            <h5 class="modal-title text-white">Modifier le devis - Intervention #<?php echo $iid; ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="index.php?action=admin_interventions">
                            <input type="hidden" name="action_type" value="update_quote">
                            <input type="hidden" name="id_intervention" value="<?php echo $iid; ?>">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Coût initial (DT)</label>
                                    <input type="number" step="0.01" min="0" name="cout_initial" class="form-control bg-dark text-white" value="<?php echo htmlspecialchars((string)($inter['cout_initial'] ?? 0)); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Batterie (DT)</label>
                                    <input type="number" step="0.01" min="0" name="type_batterie" class="form-control bg-dark text-white" value="<?php echo htmlspecialchars((string)(isset($inter['type_prices']) ? (json_decode($inter['type_prices'], true)['batterie'] ?? 0) : 0)); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Climatisation (DT)</label>
                                    <input type="number" step="0.01" min="0" name="type_climatisation" class="form-control bg-dark text-white" value="<?php echo htmlspecialchars((string)(isset($inter['type_prices']) ? (json_decode($inter['type_prices'], true)['climatisation'] ?? 0) : 0)); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Diagnostic électronique (DT)</label>
                                    <input type="number" step="0.01" min="0" name="type_diagnostic_electronique" class="form-control bg-dark text-white" value="<?php echo htmlspecialchars((string)(isset($inter['type_prices']) ? (json_decode($inter['type_prices'], true)['diagnostic_electronique'] ?? 0) : 0)); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Note pour le client (optionnel)</label>
                                    <textarea name="note_admin" class="form-control bg-dark text-white" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer le devis</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <script>
            (function() {
                function scrollToBottom(el) {
                    if (!el) return;
                    var doScroll = function() { el.scrollTop = el.scrollHeight; };
                    // try immediate, then after short delays to allow rendering
                    doScroll();
                    setTimeout(doScroll, 50);
                    setTimeout(doScroll, 300);
                }

                // When modal fully shown, scroll body to bottom
                document.querySelectorAll('[id^="messagesModal"]').forEach(function(modalEl) {
                    modalEl.addEventListener('shown.bs.modal', function () {
                        var body = modalEl.querySelector('.messages-modal-body');
                        scrollToBottom(body);
                    });
                });

                // On page load, ensure any open modals bodies are scrolled (in case messages pre-rendered)
                window.addEventListener('load', function() {
                    document.querySelectorAll('.messages-modal-body').forEach(function(b) {
                        scrollToBottom(b);
                    });
                });

                // Observe DOM changes inside message containers and scroll when children change
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(m) {
                        var target = m.target;
                        if (target && target.classList && target.classList.contains('messages-modal-body')) {
                            scrollToBottom(target);
                        }
                    });
                });
                document.querySelectorAll('.messages-modal-body').forEach(function(b) {
                    observer.observe(b, { childList: true, subtree: true });
                });
            })();
        </script>
    <?php endif; ?>
</div>

<div class="modal fade" id="editInterventionInfoModalAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header">
                <h5 class="modal-title text-white">Modifier l'intervention</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_interventions">
                <input type="hidden" name="action_type" value="update_intervention_info">
                <input type="hidden" name="id_intervention" id="adminEditInterventionId">
                <div class="modal-body text-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type d'intervention</label>
                            <select name="id_type" class="form-select bg-dark text-white border-secondary" required>
                                <option value="">Selectionner un type</option>
                                <?php foreach (($types_intervention ?? []) as $type): ?>
                                    <option value="<?php echo (int)($type['id_type'] ?? 0); ?>">
                                        <?php echo htmlspecialchars((string)($type['nom'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select bg-dark text-white border-secondary" required>
                                <option value="planifiée">Planifiee</option>
                                <option value="en_cours">En cours</option>
                                <option value="terminée">Terminee</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description des travaux</label>
                            <textarea name="description_travail" class="form-control bg-dark text-white border-secondary" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cout initial (DT)</label>
                            <input type="number" step="0.01" min="0" name="cout_initial" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date debut</label>
                            <input type="date" name="date_debut" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_fin" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadInterventionMediaModalAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header">
                <h5 class="modal-title text-white">Ajouter un document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=admin_interventions" enctype="multipart/form-data">
                <input type="hidden" name="action_type" value="upload_intervention_media">
                <input type="hidden" name="id_intervention" id="adminUploadInterventionId">
                <div class="modal-body text-light">
                    <label class="form-label">Fichier</label>
                    <input type="file" name="media_file" class="form-control bg-dark text-white border-secondary" accept="image/*,video/*,application/pdf" required>
                    <div class="form-text text-muted">Images, video ou PDF (max 10 Mo).</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Uploader</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="statutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header" style="background: linear-gradient(135deg, #d65b4c 0%, #b33f31 100%);">
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

function setAdminEditInterventionData(button) {
    if (!button || !button.dataset) return;
    const modal = document.getElementById('editInterventionInfoModalAdmin');
    if (!modal) return;

    const idInput = modal.querySelector('#adminEditInterventionId');
    const typeSelect = modal.querySelector('select[name="id_type"]');
    const statutSelect = modal.querySelector('select[name="statut"]');
    const descriptionField = modal.querySelector('textarea[name="description_travail"]');
    const coutField = modal.querySelector('input[name="cout_initial"]');
    const dateDebutField = modal.querySelector('input[name="date_debut"]');
    const dateFinField = modal.querySelector('input[name="date_fin"]');

    if (idInput) idInput.value = button.dataset.id || '';
    if (typeSelect) typeSelect.value = button.dataset.typeId || '';
    if (statutSelect) statutSelect.value = button.dataset.statut || '';
    if (descriptionField) descriptionField.value = button.dataset.description || '';
    if (coutField) coutField.value = button.dataset.cout || '';
    if (dateDebutField) dateDebutField.value = button.dataset.dateDebut ? button.dataset.dateDebut.split(' ')[0] : '';
    if (dateFinField) dateFinField.value = button.dataset.dateFin ? button.dataset.dateFin.split(' ')[0] : '';
}

function setAdminUploadInterventionData(button) {
    if (!button || !button.dataset) return;
    const input = document.getElementById('adminUploadInterventionId');
    if (input) input.value = button.dataset.id || '';
}
</script>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(200, 70, 56, 0.06) !important;
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

.inter-action-plus {
    width: 30px;
    height: 30px;
    padding: 0;
    border-radius: 50%;
    font-weight: 700;
    line-height: 1;
}

.inter-dates-cell {
    color: var(--text-700);
    font-size: 0.95rem;
}

.inter-date-label {
    color: var(--text-500);
    font-weight: 700;
}

.inter-date-value {
    color: var(--text-900);
    font-weight: 700;
}

.inter-date-finish {
    color: var(--success);
}

.inter-date-empty {
    color: var(--text-500);
    font-weight: 600;
}

#addInterventionModal .modal-content {
    border-radius: 14px;
}

#addInterventionModal .diag-info-card {
    background: var(--accent-100);
    border: 1px solid var(--accent-200);
    border-radius: 12px;
}

#addInterventionModal .diag-info-title {
    color: var(--text-800);
    font-weight: 700;
}

#addInterventionModal .diag-info-label {
    color: var(--text-600);
    font-weight: 600;
}

#addInterventionModal #diagInfoProblem,
#addInterventionModal #diagInfoId,
#addInterventionModal #diagInfoVehicle,
#addInterventionModal #diagInfoSeverity,
#addInterventionModal #diagInfoDate,
#addInterventionModal #diagInfoAmount {
    color: var(--text-900);
}

.admin-interventions-page input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
}

.admin-interventions-page input[type="number"]::-webkit-outer-spin-button,
.admin-interventions-page input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.admin-interventions-page .input-group .form-control[type="number"] {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
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

<script>
(function() {
    'use strict';

    function parseMoney(value) {
        var number = parseFloat(String(value || '0').replace(',', '.'));
        return Number.isFinite(number) ? number : 0;
    }
    
    // Initialiser les prix
    function initializePrices() {
        const checkboxes = document.querySelectorAll('.intervention-checkbox');
        checkboxes.forEach(checkbox => {
            const price = parseMoney(checkbox.getAttribute('data-price'));
            checkbox.setAttribute('data-price', price.toFixed(2));
        });
    }
    
    // Calculer le prix total
    function calculateTotalPrice() {
        let total = 0;
        const checkedBoxes = document.querySelectorAll('.intervention-checkbox:checked');
        
        checkedBoxes.forEach(checkbox => {
            total += parseMoney(checkbox.getAttribute('data-price'));
        });
        
        // Afficher le total
        const totalEl = document.getElementById('totalEstimation');
        if (totalEl) {
            if (total > 0) {
                totalEl.textContent = total.toFixed(2) + ' DT';
            } else {
                totalEl.textContent = '0 DT';
            }
        }
        
        // Mettre à jour le champ cout_initial avec le total exact des types
        const coutInput = document.getElementById('modalCoutInitial');
        if (coutInput && total > 0) {
            coutInput.value = total.toFixed(2);
        } else if (coutInput) {
            coutInput.value = '';
        }
    }
    
    // Attacher les événements
    document.addEventListener('DOMContentLoaded', function() {
        initializePrices();
        
        const checkboxes = document.querySelectorAll('.intervention-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', calculateTotalPrice);
        });
        
        // Gérer la soumission du formulaire d'intervention via AJAX
        const interventionForm = document.getElementById('quickInterventionForm');
        if (interventionForm) {
            interventionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Collecter les données du formulaire
                const formData = new FormData(this);
                
                // Envoyer via AJAX
                fetch('index.php?action=create_intervention', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur HTTP: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Réponse:', data);
                    if (data.success) {
                        // Fermer la modal
                        const modalElement = document.getElementById('addInterventionModal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                        
                        // Afficher un toast de succès
                        showSuccessToast('Intervention créée avec succès!');
                        
                        // Réinitialiser le formulaire
                        this.reset();
                        
                        // Recharger la page après 2 secondes
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        alert('Erreur: ' + (data.message || 'Impossible de créer l\'intervention'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur: ' + error.message);
                });
            });
        }
        
        // Fonction pour afficher un toast de succès
        function showSuccessToast(message) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.innerHTML = toastHtml;
            document.body.appendChild(toastContainer);
            
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
            toast.show();
            
            // Nettoyer après que le toast disparaisse
            setTimeout(() => {
                toastContainer.remove();
            }, 4000);
        }
    });
    
    // Tri du tableau des interventions
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
                        
                        // Vérifier si c'est une date (format YYYY-MM-DD ou DD/MM/YYYY)
                        const dateRegex = /(\d{4})-(\d{2})-(\d{2})|(\d{2})\/(\d{2})\/(\d{4})/;
                        const aMatch = aVal.match(dateRegex);
                        const bMatch = bVal.match(dateRegex);
                        
                        if (aMatch && bMatch) {
                            let aDate, bDate;
                            if (aMatch[1]) {
                                aDate = new Date(aMatch[0]);
                            } else {
                                aDate = new Date(aMatch[6], aMatch[5] - 1, aMatch[4]);
                            }
                            if (bMatch[1]) {
                                bDate = new Date(bMatch[0]);
                            } else {
                                bDate = new Date(bMatch[6], bMatch[5] - 1, bMatch[4]);
                            }
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

<?php require __DIR__ . '/layout_footer.php'; ?>
