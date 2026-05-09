<?php
$pageTitle = 'Messages';
$action = 'client_interventions';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="client-messages-page">
    <div class="client-chat-shell">
        <aside class="client-chat-sidebar">
            <div class="client-chat-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="clientConversationSearch" class="form-control" placeholder="Rechercher une conversation...">
            </div>

            <div class="client-chat-conversation-list" id="clientConversationList">
                <?php if (empty($interventions)): ?>
                    <div class="text-muted p-3">Aucune intervention disponible pour ce vehicule.</div>
                <?php else: ?>
                    <?php foreach ($interventions as $inter): ?>
                        <?php
                            $iid = (int)($inter['id_intervention'] ?? 0);
                            $preview = $conversationPreviews[$iid] ?? [];
                            $isActive = ($iid === (int)$selectedInterventionId);
                            $plate = (string)($inter['immatriculation'] ?? 'N/A');
                            $type = (string)($inter['type_nom'] ?? 'Intervention');
                            $lastContent = (string)($preview['last_content'] ?? 'Aucun message');
                            $lastDate = !empty($preview['last_date']) ? date('d/m/Y H:i', strtotime((string)$preview['last_date'])) : '';
                            $sender = (string)($preview['last_sender'] ?? '');
                            $senderLabel = $sender === 'client' ? 'Vous' : ($sender === 'admin' ? 'Garage' : '');
                            $searchValue = mb_strtolower($plate . ' ' . $type . ' ' . $iid . ' ' . $lastContent, 'UTF-8');
                        ?>
                        <a href="index.php?action=intervention_chat&id=<?php echo $iid; ?>"
                           class="client-conv-item <?php echo $isActive ? 'active' : ''; ?>"
                           data-search="<?php echo htmlspecialchars($searchValue); ?>">
                            <div class="client-conv-top">
                                <span class="client-conv-title">Garage Smart Garage</span>
                                <span class="client-conv-time"><?php echo htmlspecialchars($lastDate); ?></span>
                            </div>
                            <div class="client-conv-meta">Intervention #<?php echo $iid; ?> - <?php echo htmlspecialchars($plate); ?></div>
                            <div class="client-conv-preview">
                                <?php if ($senderLabel !== ''): ?>
                                    <span><?php echo htmlspecialchars($senderLabel); ?>: </span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars(mb_substr($lastContent, 0, 45)); ?><?php echo mb_strlen($lastContent) > 45 ? '...' : ''; ?>
                            </div>
                            <span class="badge bg-primary rounded-pill"><?php echo (int)($preview['count'] ?? 0); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <section class="client-chat-main">
            <?php if (empty($selectedIntervention)): ?>
                <div class="client-chat-empty">
                    <i class="bi bi-chat-square-text"></i>
                    <p>Selectionnez une conversation a gauche.</p>
                </div>
            <?php else: ?>
                <header class="client-chat-header">
                    <div class="client-chat-avatar">g</div>
                    <div>
                        <div class="client-chat-name">Garage Smart Garage</div>
                        <div class="client-chat-sub">
                            Intervention #<?php echo (int)$selectedIntervention['id_intervention']; ?>
                            - <?php echo htmlspecialchars((string)($selectedIntervention['immatriculation'] ?? 'N/A')); ?>
                        </div>
                    </div>
                    <div class="ms-auto text-muted small">
                        <?php echo htmlspecialchars((string)($selectedIntervention['type_nom'] ?? '')); ?>
                    </div>
                </header>

                <div class="modal fade" id="editInterventionInfoModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content client-diagnostic-modal bg-white border-0 shadow-sm rounded-4">
                            <div class="modal-header client-diagnostic-header border-0">
                                <h5 class="modal-title text-dark fw-semibold">Modifier l'intervention</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="index.php?action=intervention_chat">
                                <input type="hidden" name="action_type" value="update_intervention_info">
                                <input type="hidden" name="id_intervention" value="<?php echo (int)$selectedIntervention['id_intervention']; ?>">
                                <input type="hidden" name="vehicle_id" value="<?php echo $vehicleId; ?>">
                                <div class="modal-body client-diagnostic-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label client-diagnostic-label">Type d'intervention</label>
                                            <select name="id_type" class="form-select client-diagnostic-field" required>
                                                <option value="">Selectionner un type</option>
                                                <?php foreach (($typesIntervention ?? []) as $type): ?>
                                                    <option value="<?php echo (int)$type['id_type']; ?>" <?php echo ((int)($selectedIntervention['id_type'] ?? 0) === (int)$type['id_type']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars((string)$type['nom']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label client-diagnostic-label">Statut</label>
                                            <select name="statut" class="form-select client-diagnostic-field" required>
                                                <option value="planifiée" <?php echo (($selectedIntervention['statut'] ?? '') === 'planifiée') ? 'selected' : ''; ?>>Planifiee</option>
                                                <option value="en_cours" <?php echo (($selectedIntervention['statut'] ?? '') === 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                                                <option value="terminée" <?php echo (($selectedIntervention['statut'] ?? '') === 'terminée') ? 'selected' : ''; ?>>Terminee</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label client-diagnostic-label">Description des travaux</label>
                                            <textarea name="description_travail" class="form-control client-diagnostic-field" rows="3" required><?php echo htmlspecialchars((string)($selectedIntervention['description_travail'] ?? '')); ?></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label client-diagnostic-label">Cout initial (DT)</label>
                                            <input type="number" step="0.01" min="0" name="cout_initial" class="form-control client-diagnostic-field" value="<?php echo htmlspecialchars((string)($selectedIntervention['cout_initial'] ?? 0)); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label client-diagnostic-label">Date debut</label>
                                            <input type="date" name="date_debut" class="form-control client-diagnostic-field" value="<?php echo !empty($selectedIntervention['date_debut']) ? htmlspecialchars(date('Y-m-d', strtotime((string)$selectedIntervention['date_debut']))) : ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label client-diagnostic-label">Date fin</label>
                                            <input type="date" name="date_fin" class="form-control client-diagnostic-field" value="<?php echo !empty($selectedIntervention['date_fin']) ? htmlspecialchars(date('Y-m-d', strtotime((string)$selectedIntervention['date_fin']))) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer client-diagnostic-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="uploadInterventionMediaModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content client-diagnostic-modal bg-white border-0 shadow-sm rounded-4">
                            <div class="modal-header client-diagnostic-header border-0">
                                <h5 class="modal-title text-dark fw-semibold">Ajouter un document</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="index.php?action=intervention_chat" enctype="multipart/form-data">
                                <input type="hidden" name="action_type" value="upload_intervention_media">
                                <input type="hidden" name="id_intervention" value="<?php echo (int)$selectedIntervention['id_intervention']; ?>">
                                <input type="hidden" name="vehicle_id" value="<?php echo $vehicleId; ?>">
                                <div class="modal-body client-diagnostic-body">
                                    <label class="form-label client-diagnostic-label">Fichier</label>
                                    <input type="file" name="media_file" class="form-control client-diagnostic-field" accept="image/*,video/*,application/pdf" required>
                                    <div class="form-text client-diagnostic-help">Images, video ou PDF (max 10 Mo).</div>
                                </div>
                                <div class="modal-footer client-diagnostic-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Uploader</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="client-chat-messages" id="clientChatZone">
                    <?php if (empty($messages)): ?>
                        <div class="text-muted text-center mt-5">Aucun message pour cette intervention.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php
                                $isClient = (($msg['expediteur'] ?? '') === 'client');
                                $dateLabel = !empty($msg['date_envoi']) ? date('H:i', strtotime((string)$msg['date_envoi'])) : '';
                            ?>
                            <div class="client-msg-row <?php echo $isClient ? 'right' : 'left'; ?>">
                                <div class="client-msg-bubble <?php echo $isClient ? 'client' : 'admin'; ?>">
                                    <div class="client-msg-author"><?php echo $isClient ? 'Vous' : 'Garage'; ?></div>
                                    <div><?php echo nl2br(htmlspecialchars((string)$msg['contenu'])); ?></div>
                                    <div class="client-msg-time"><?php echo htmlspecialchars($dateLabel); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <footer class="client-chat-input-wrap">
                    <form method="POST" action="index.php?action=intervention_chat" class="client-chat-input-form">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="sender" value="client">
                        <input type="hidden" name="id_intervention" value="<?php echo (int)$selectedIntervention['id_intervention']; ?>">
                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicleId; ?>">
                        <div class="dropdown dropup">
                            <button class="btn btn-link client-attach-btn client-plus-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
                                +
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end client-actions-menu p-2">
                                <li>
                                    <button type="button" class="btn btn-outline-primary rounded-circle client-action-bubble client-action-icon mb-2" data-bs-toggle="modal" data-bs-target="#editInterventionInfoModal" aria-label="Modifier">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </li>
                                <li>
                                    <button type="button" class="btn btn-outline-success rounded-circle client-action-bubble client-action-icon" data-bs-toggle="modal" data-bs-target="#uploadInterventionMediaModal" aria-label="Photo / Document">
                                        <i class="bi bi-image"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <input type="text" name="contenu" class="form-control" placeholder="Ecrire un message..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary client-send-btn" title="Envoyer">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </footer>
            <?php endif; ?>
        </section>
    </div>
</div>

<style>
.front-office .page-wrapper {
    max-width: 100%;
    margin-left: 260px;
    padding: 0;
}

.client-messages-page {
    height: calc(100vh - 64px);
    display: flex;
    flex-direction: column;
}

.client-chat-shell {
    display: grid;
    grid-template-columns: 320px 1fr;
    height: calc(100vh - 64px);
    min-height: 0;
    flex: 1;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
    overflow: hidden;
}

.client-chat-sidebar {
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: var(--surface-3);
    border-right: 1px solid var(--border);
}

.client-chat-search-wrap {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: var(--surface-3);
    border-bottom: 1px solid var(--border);
}

.client-chat-search-wrap i {
    color: var(--text-500);
}

.client-chat-search-wrap .form-control {
    background: var(--surface);
    border: 1px solid var(--border-strong);
    color: var(--text-900) !important;
}

.client-chat-conversation-list {
    flex: 1;
    min-height: 0;
    max-height: none;
    height: auto;
    overflow-y: auto;
}

.client-conv-item {
    position: relative;
    display: block;
    padding: 12px 14px;
    color: var(--text-800);
    text-decoration: none;
    border-bottom: 1px solid var(--border);
    transition: background 0.2s ease;
}

.client-conv-item:hover {
    background: var(--accent-100);
}

.client-conv-item.active {
    background: var(--accent-100);
    border-left: 3px solid var(--accent);
    padding-left: 11px;
}

.client-conv-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.client-conv-title {
    font-size: 0.92rem;
    font-weight: 700;
}

.client-conv-time {
    color: var(--text-500);
    font-size: 0.72rem;
}

.client-conv-meta {
    margin-top: 2px;
    color: var(--text-600);
    font-size: 0.78rem;
}

.client-conv-preview {
    margin-top: 4px;
    color: var(--text-600);
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 230px;
}

.client-conv-item .badge {
    position: absolute;
    right: 12px;
    bottom: 10px;
    font-size: 0.68rem;
}

.client-chat-main {
    display: flex;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
    background: var(--surface);
    overflow: hidden;
}

.client-chat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 64px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
}

.client-chat-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #ffffff;
    background: radial-gradient(circle at 30% 30%, #f07a6a, #c84638);
}

.client-chat-name {
    color: var(--text-900);
    font-weight: 700;
}

.client-chat-sub {
    color: var(--text-500);
    font-size: 0.78rem;
}

.client-chat-messages {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 16px;
    background:
        radial-gradient(circle at 12% 10%, rgba(200, 70, 56, 0.08), transparent 35%),
        radial-gradient(circle at 90% 85%, rgba(200, 70, 56, 0.06), transparent 28%),
        #f8f9fb;
}

.client-chat-messages {
    direction: ltr;
    scroll-behavior: smooth;
}

.client-msg-row {
    display: flex;
    margin-bottom: 12px;
}

.client-msg-row.left {
    justify-content: flex-start;
}

.client-msg-row.right {
    justify-content: flex-end;
}

.client-msg-bubble {
    max-width: 62%;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: 0.93rem;
    box-shadow: 0 3px 10px rgba(31, 41, 55, 0.1);
}

.client-msg-bubble.admin {
    background: var(--surface-3);
    color: var(--text-800);
    border-top-left-radius: 4px;
}

.client-msg-bubble.client {
    background: linear-gradient(135deg, #d65b4c, #b33f31);
    color: #ffffff;
    border-top-right-radius: 4px;
}

.client-msg-author {
    font-size: 0.73rem;
    opacity: 0.82;
    margin-bottom: 3px;
}

.client-msg-time {
    margin-top: 3px;
    font-size: 0.68rem;
    opacity: 0.75;
    text-align: right;
}

.client-chat-input-wrap {
    border-top: 1px solid var(--border);
    padding: 10px 12px;
    background: var(--surface-2);
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    z-index: 3;
}

.client-chat-input-form {
    display: grid;
    grid-template-columns: auto auto 1fr auto;
    gap: 8px;
    align-items: center;
}

.client-attach-btn {
    color: var(--text-600);
    text-decoration: none;
    border: 1px solid var(--border-strong);
    background: var(--surface);
    border-radius: 10px;
    width: 40px;
    height: 40px;
}

.client-chat-input-form .dropdown {
    display: flex;
}

.client-actions-menu {
    min-width: 84px;
    box-shadow: 0 14px 30px rgba(31, 41, 55, 0.14);
    border: 1px solid var(--border-strong);
    border-radius: 18px;
}

.client-action-bubble {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    padding: 0;
}

.client-action-icon i {
    font-size: 1rem;
}

.client-chat-input-form .dropdown .dropdown-menu {
    z-index: 1080;
}

.client-chat-input-form .form-control {
    background: var(--surface);
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    color: var(--text-900) !important;
}

.client-diagnostic-modal {
    border: 1px solid #dbe3ef !important;
    overflow: hidden;
}

.client-diagnostic-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    padding: 1rem 1.25rem;
}

.client-diagnostic-body {
    background: #ffffff;
    padding: 1.25rem;
}

.client-diagnostic-footer {
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
    padding: 1rem 1.25rem;
}

.client-diagnostic-label {
    color: #111827 !important;
    font-weight: 700 !important;
}

#editInterventionInfoModal .client-diagnostic-label,
#uploadInterventionMediaModal .client-diagnostic-label {
    color: #111827 !important;
    font-weight: 700 !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
    margin-bottom: 0.5rem !important;
}

.client-diagnostic-help {
    color: #6b7280;
}

.client-diagnostic-field {
    background-color: #ffffff !important;
    border-color: #d1d5db !important;
    color: #111827 !important;
    box-shadow: none !important;
}

.client-diagnostic-field::placeholder {
    color: #9ca3af !important;
}

.client-diagnostic-field:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
}

.client-diagnostic-modal .form-select option {
    background-color: #ffffff;
    color: #111827;
}

.client-diagnostic-modal .form-control[type="file"] {
    color: #111827 !important;
}

.client-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(200, 70, 56, 0.28);
}

.client-chat-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    color: var(--text-500);
    gap: 8px;
}

.client-chat-empty i {
    font-size: 2.2rem;
}

@media (max-width: 992px) {
    .front-office .page-wrapper {
        margin-left: 0;
    }

    .client-messages-page {
        height: auto;
        min-height: calc(100vh - 64px);
    }

    .client-chat-shell {
        grid-template-columns: 1fr;
        height: auto;
        min-height: calc(100vh - 64px);
    }

    .client-chat-sidebar {
        border-right: 0;
        border-bottom: 1px solid var(--border);
    }

    .client-chat-conversation-list {
        max-height: 260px;
        height: auto;
    }

    .client-msg-bubble {
        max-width: 86%;
    }
}
</style>

<style>
.client-plus-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    border-radius: 50%;
    font-weight: 700;
    line-height: 1;
}
</style>
<script>
    (function(){
        function scrollClientChatToBottom() {
            var zone = document.getElementById('clientChatZone');
            if (!zone) return;
            try { zone.scrollTop = zone.scrollHeight; } catch(e) {}
            setTimeout(function(){ try { zone.scrollTop = zone.scrollHeight; } catch(e) {} }, 50);
            setTimeout(function(){ try { zone.scrollTop = zone.scrollHeight; } catch(e) {} }, 300);
        }

        // On load scroll to bottom
        window.addEventListener('load', function(){ scrollClientChatToBottom(); });

        // Observe new messages appended and scroll
        var observerTarget = document.getElementById('clientChatZone');
        if (observerTarget) {
            var obs = new MutationObserver(function(muts){ scrollClientChatToBottom(); });
            obs.observe(observerTarget, { childList: true, subtree: true });
        }
    })();
</script>

<script>
(function () {
    var zone = document.getElementById('clientChatZone');
    if (zone) {
        zone.scrollTop = zone.scrollHeight;
    }

    var searchInput = document.getElementById('clientConversationSearch');
    var list = document.getElementById('clientConversationList');
    if (!searchInput || !list) {
        return;
    }

    searchInput.addEventListener('input', function () {
        var query = (searchInput.value || '').toLowerCase().trim();
        var items = list.querySelectorAll('.client-conv-item');
        items.forEach(function (item) {
            var haystack = (item.getAttribute('data-search') || '').toLowerCase();
            item.style.display = haystack.indexOf(query) !== -1 ? '' : 'none';
        });
    });
})();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>