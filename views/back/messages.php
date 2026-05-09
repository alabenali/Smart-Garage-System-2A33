<?php
$pageTitle = 'Messagerie Admin';
$action = 'messages';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="admin-messages-page">
    <div class="admin-chat-shell">
        <aside class="admin-chat-sidebar">
            <div class="admin-chat-search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="adminConversationSearch" class="form-control" placeholder="Rechercher une conversation...">
            </div>

            <div class="admin-chat-conversation-list" id="adminConversationList">
                <?php if (empty($interventions)): ?>
                    <div class="text-muted p-3">Aucune intervention trouvee.</div>
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
                            $senderLabel = $sender === 'client' ? 'Client' : ($sender === 'admin' ? 'Admin' : '');
                            $searchValue = mb_strtolower($plate . ' ' . $type . ' ' . $iid . ' ' . $lastContent, 'UTF-8');
                        ?>
                        <a href="index.php?action=messages&id=<?php echo $iid; ?>"
                           class="admin-conv-item <?php echo $isActive ? 'active' : ''; ?>"
                           data-search="<?php echo htmlspecialchars($searchValue); ?>">
                            <div class="admin-conv-top">
                                <span class="admin-conv-title">Garage Smart Garage</span>
                                <span class="admin-conv-time"><?php echo htmlspecialchars($lastDate); ?></span>
                            </div>
                            <div class="admin-conv-meta">Intervention #<?php echo $iid; ?> - <?php echo htmlspecialchars($plate); ?></div>
                            <div class="admin-conv-preview">
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

        <section class="admin-chat-main">
            <?php if (empty($selectedIntervention)): ?>
                <div class="admin-chat-empty">
                    <i class="bi bi-chat-square-text"></i>
                    <p>Selectionnez une conversation a gauche.</p>
                </div>
            <?php else: ?>
                <header class="admin-chat-header">
                    <div class="admin-chat-avatar">g</div>
                    <div>
                        <div class="admin-chat-name">Garage Smart Garage</div>
                        <div class="admin-chat-sub">
                            Intervention #<?php echo (int)$selectedIntervention['id_intervention']; ?>
                            - <?php echo htmlspecialchars((string)($selectedIntervention['immatriculation'] ?? 'N/A')); ?>
                        </div>
                    </div>
                    <div class="ms-auto text-muted small">
                        <?php echo htmlspecialchars((string)($selectedIntervention['type_nom'] ?? '')); ?>
                    </div>
                </header>

                <div class="admin-chat-messages" id="adminChatZone">
                    <?php if (empty($messages)): ?>
                        <div class="text-muted text-center mt-5">Aucun message pour cette intervention.</div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php
                                $isClient = (($msg['expediteur'] ?? '') === 'client');
                                $dateLabel = !empty($msg['date_envoi']) ? date('H:i', strtotime((string)$msg['date_envoi'])) : '';
                            ?>
                            <div class="admin-msg-row <?php echo $isClient ? 'left' : 'right'; ?>">
                                <div class="admin-msg-bubble <?php echo $isClient ? 'client' : 'admin'; ?>">
                                    <div class="admin-msg-author"><?php echo $isClient ? 'Client' : 'Admin'; ?></div>
                                    <div><?php echo nl2br(htmlspecialchars((string)$msg['contenu'])); ?></div>
                                    <div class="admin-msg-time"><?php echo htmlspecialchars($dateLabel); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <footer class="admin-chat-input-wrap">
                    <form method="POST" action="index.php?action=messages" class="admin-chat-input-form">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="sender" value="admin">
                        <input type="hidden" name="id_intervention" value="<?php echo (int)$selectedIntervention['id_intervention']; ?>">
                        <div class="dropdown dropup">
                            <button type="button" class="btn btn-link admin-attach-btn admin-plus-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions">
                                +
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end admin-actions-menu p-2">
                                <li>
                                    <button type="button"
                                        class="btn btn-outline-primary rounded-circle admin-action-bubble admin-action-icon mb-2"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editInterventionInfoModalAdmin"
                                        data-id="<?php echo (int)$selectedIntervention['id_intervention']; ?>"
                                        data-description="<?php echo htmlspecialchars((string)($selectedIntervention['description_travail'] ?? ''), ENT_QUOTES); ?>"
                                        data-type-id="<?php echo (int)($selectedIntervention['id_type'] ?? 0); ?>"
                                        data-cout="<?php echo htmlspecialchars((string)($selectedIntervention['cout_initial'] ?? 0), ENT_QUOTES); ?>"
                                        data-statut="<?php echo htmlspecialchars((string)($selectedIntervention['statut'] ?? ''), ENT_QUOTES); ?>"
                                        data-date-debut="<?php echo htmlspecialchars((string)($selectedIntervention['date_debut'] ?? ''), ENT_QUOTES); ?>"
                                        data-date-fin="<?php echo htmlspecialchars((string)($selectedIntervention['date_fin'] ?? ''), ENT_QUOTES); ?>"
                                        onclick="setAdminEditInterventionData(this)"
                                        aria-label="Modifier">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </li>
                                <li>
                                    <button type="button"
                                        class="btn btn-outline-success rounded-circle admin-action-bubble admin-action-icon"
                                        data-bs-toggle="modal"
                                        data-bs-target="#uploadInterventionMediaModalAdmin"
                                        data-id="<?php echo (int)$selectedIntervention['id_intervention']; ?>"
                                        onclick="setAdminUploadInterventionData(this)"
                                        aria-label="Photo / Document">
                                        <i class="bi bi-image"></i>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <input type="text" name="contenu" class="form-control" placeholder="Ecrire un message..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary admin-send-btn" title="Envoyer">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </footer>
            <?php endif; ?>
        </section>
    </div>
</div>

<style>
.page-wrapper {
    max-width: 100%;
    margin: 0 !important;
    padding: 0 !important;
}

.back-office .page-wrapper {
    margin-left: 260px !important;
    padding: 0 !important;
}

.admin-messages-page {
    height: 100vh;
    display: flex;
    flex-direction: column;
}

.admin-chat-shell {
    display: grid;
    grid-template-columns: 320px 1fr;
    min-height: 0;
    flex: 1;
    border-radius: 0;
    overflow: hidden;
    border: 0;
    border-top: 1px solid var(--border);
    background: var(--surface-2);
    box-shadow: none;
}

.admin-chat-sidebar {
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: var(--surface-3);
    border-right: 1px solid var(--border);
}

.admin-chat-search-wrap {
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

.admin-chat-search-wrap i {
    color: var(--text-500);
}

.admin-chat-search-wrap .form-control {
    background: var(--surface);
    border: 1px solid var(--border-strong);
    color: var(--text-900) !important;
}

.admin-chat-conversation-list {
    flex: 1;
    min-height: 0;
    max-height: none;
    overflow-y: auto;
}

.admin-conv-item {
    position: relative;
    display: block;
    padding: 12px 14px;
    color: var(--text-800);
    text-decoration: none;
    border-bottom: 1px solid var(--border);
    transition: background 0.2s ease;
}

.admin-conv-item:hover {
    background: var(--accent-100);
}

.admin-conv-item.active {
    background: var(--accent-100);
    border-left: 3px solid var(--accent);
    padding-left: 11px;
}

.admin-conv-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.admin-conv-title {
    font-size: 0.92rem;
    font-weight: 700;
}

.admin-conv-time {
    color: var(--text-500);
    font-size: 0.72rem;
}

.admin-conv-meta {
    margin-top: 2px;
    color: var(--text-600);
    font-size: 0.78rem;
}

.admin-conv-preview {
    margin-top: 4px;
    color: var(--text-600);
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 230px;
}

.admin-conv-item .badge {
    position: absolute;
    right: 12px;
    bottom: 10px;
    font-size: 0.68rem;
}

.admin-chat-main {
    display: flex;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
    background: var(--surface);
    overflow: hidden;
}

.admin-chat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 64px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
}

.admin-chat-avatar {
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

.admin-chat-name {
    color: var(--text-900);
    font-weight: 700;
}

.admin-chat-sub {
    color: var(--text-500);
    font-size: 0.78rem;
}

.admin-chat-messages {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 16px;
    background:
        radial-gradient(circle at 10% 10%, rgba(200, 70, 56, 0.08), transparent 35%),
        radial-gradient(circle at 90% 85%, rgba(200, 70, 56, 0.06), transparent 28%),
        #f8f9fb;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    scrollbar-color: var(--border-strong) #f8f9fb;
}

.admin-chat-messages::-webkit-scrollbar {
    width: 8px;
}

.admin-chat-messages::-webkit-scrollbar-track {
    background: #f8f9fb;
}

.admin-chat-messages::-webkit-scrollbar-thumb {
    background: var(--border-strong);
    border-radius: 4px;
}

.admin-chat-messages::-webkit-scrollbar-thumb:hover {
    background: #c3cbd8;
}

.admin-msg-row {
    display: flex;
    margin-bottom: 12px;
}

.admin-msg-row.left {
    justify-content: flex-start;
}

.admin-msg-row.right {
    justify-content: flex-end;
}

.admin-msg-bubble {
    max-width: 62%;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: 0.93rem;
    box-shadow: 0 3px 10px rgba(31, 41, 55, 0.1);
}

.admin-msg-bubble.client {
    background: var(--surface-3);
    color: var(--text-800);
    border-top-left-radius: 4px;
}

.admin-msg-bubble.admin {
    background: linear-gradient(135deg, #d65b4c, #b33f31);
    color: #ffffff;
    border-top-right-radius: 4px;
}

.admin-msg-author {
    font-size: 0.73rem;
    opacity: 0.82;
    margin-bottom: 3px;
}

.admin-msg-time {
    margin-top: 3px;
    font-size: 0.68rem;
    opacity: 0.75;
    text-align: right;
}

.admin-chat-input-wrap {
    border-top: 1px solid var(--border);
    padding: 10px 12px;
    background: var(--surface-2);
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    z-index: 3;
}

.admin-chat-input-form {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 8px;
    align-items: center;
}

.admin-attach-btn {
    color: var(--text-600);
    text-decoration: none;
    border: 1px solid var(--border-strong);
    background: var(--surface);
    border-radius: 10px;
    width: 40px;
    height: 40px;
}

.admin-chat-input-form .dropdown {
    display: flex;
}

.admin-actions-menu {
    min-width: 84px;
    box-shadow: 0 14px 30px rgba(31, 41, 55, 0.14);
    border: 1px solid var(--border-strong);
    border-radius: 18px;
}

.admin-action-bubble {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    padding: 0;
}

.admin-action-icon i {
    font-size: 1rem;
}

.admin-chat-input-form .form-control {
    background: var(--surface);
    border: 1px solid var(--border-strong);
    border-radius: 12px;
    color: var(--text-900) !important;
}

.admin-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(200, 70, 56, 0.28);
}

.admin-chat-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    color: var(--text-500);
    gap: 8px;
}

.admin-chat-empty i {
    font-size: 2.2rem;
}

@media (max-width: 992px) {
    .back-office .page-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }

    .admin-messages-page {
        height: 100vh;
    }

    .admin-chat-shell {
        grid-template-columns: 1fr;
    }

    .admin-chat-sidebar {
        border-right: 0;
        border-bottom: 1px solid var(--border);
    }

    .admin-chat-conversation-list {
        max-height: 260px;
        height: auto;
    }

    .admin-msg-bubble {
        max-width: 86%;
    }
}
</style>

<script>
(function () {
    // Scroll to bottom function
    function scrollAdminChatToBottom() {
        var zone = document.getElementById('adminChatZone');
        if (!zone) return;
        try { zone.scrollTop = zone.scrollHeight; } catch(e) {}
        setTimeout(function(){ try { zone.scrollTop = zone.scrollHeight; } catch(e) {} }, 50);
        setTimeout(function(){ try { zone.scrollTop = zone.scrollHeight; } catch(e) {} }, 300);
    }

    // On load scroll to bottom
    window.addEventListener('load', function(){ scrollAdminChatToBottom(); });

    // Observe new messages appended and scroll
    var observerTarget = document.getElementById('adminChatZone');
    if (observerTarget) {
        var obs = new MutationObserver(function(muts){ scrollAdminChatToBottom(); });
        obs.observe(observerTarget, { childList: true, subtree: true });
    }
})();
</script>

<div class="modal fade" id="editInterventionInfoModalAdmin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-light">
            <div class="modal-header">
                <h5 class="modal-title text-white">Modifier l'intervention</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?action=messages">
                <input type="hidden" name="action_type" value="update_intervention_info">
                <input type="hidden" name="id_intervention" id="adminEditInterventionId">
                <div class="modal-body text-light">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type d'intervention</label>
                            <select name="id_type" class="form-select bg-dark text-white border-secondary" required>
                                <option value="">Selectionner un type</option>
                                <?php foreach (($typesIntervention ?? []) as $type): ?>
                                    <option value="<?php echo (int)($type['id_type'] ?? 0); ?>"><?php echo htmlspecialchars((string)($type['nom'] ?? '')); ?></option>
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
            <form method="POST" action="index.php?action=messages" enctype="multipart/form-data">
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

<script>
(function () {
    var zone = document.getElementById('adminChatZone');
    if (zone) {
        zone.scrollTop = zone.scrollHeight;
    }

    var searchInput = document.getElementById('adminConversationSearch');
    var list = document.getElementById('adminConversationList');
    if (!searchInput || !list) {
        return;
    }

    searchInput.addEventListener('input', function () {
        var query = (searchInput.value || '').toLowerCase().trim();
        var items = list.querySelectorAll('.admin-conv-item');
        items.forEach(function (item) {
            var haystack = (item.getAttribute('data-search') || '').toLowerCase();
            item.style.display = haystack.indexOf(query) !== -1 ? '' : 'none';
        });
    });
})();

function setAdminEditInterventionData(button) {
    if (!button || !button.dataset) return;
    var modal = document.getElementById('editInterventionInfoModalAdmin');
    if (!modal) return;

    var idInput = modal.querySelector('#adminEditInterventionId');
    var typeSelect = modal.querySelector('select[name="id_type"]');
    var statutSelect = modal.querySelector('select[name="statut"]');
    var descriptionField = modal.querySelector('textarea[name="description_travail"]');
    var coutField = modal.querySelector('input[name="cout_initial"]');
    var dateDebutField = modal.querySelector('input[name="date_debut"]');
    var dateFinField = modal.querySelector('input[name="date_fin"]');

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
    var input = document.getElementById('adminUploadInterventionId');
    if (input) input.value = button.dataset.id || '';
}
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>