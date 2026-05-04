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
                        <button type="button" class="btn btn-link client-attach-btn" title="Piece jointe non active">
                            <i class="bi bi-paperclip"></i>
                        </button>
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
.page-wrapper {
    max-width: 100%;
    margin: 0;
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
    min-height: 0;
    flex: 1;
    border-top: 1px solid rgba(189, 208, 234, 0.2);
    background: #f5f7fb;
    overflow: hidden;
}

.client-chat-sidebar {
    background: linear-gradient(180deg, #f0f3f8 0%, #e8edf5 100%);
    border-right: 1px solid #d6dfeb;
}

.client-chat-search-wrap {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #f0f3f8;
    border-bottom: 1px solid #d6dfeb;
}

.client-chat-search-wrap i {
    color: #73849a;
}

.client-chat-search-wrap .form-control {
    background: #ffffff;
    border: 1px solid #cfd9e8;
    color: #233243 !important;
}

.client-chat-conversation-list {
    max-height: none;
    height: calc(100vh - 64px - 58px);
    overflow-y: auto;
}

.client-conv-item {
    position: relative;
    display: block;
    padding: 12px 14px;
    color: #1f2e40;
    text-decoration: none;
    border-bottom: 1px solid #dde5f0;
    transition: background 0.2s ease;
}

.client-conv-item:hover {
    background: #eaf2ff;
}

.client-conv-item.active {
    background: #dfeeff;
    border-left: 3px solid #2585ff;
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
    color: #7b8ea6;
    font-size: 0.72rem;
}

.client-conv-meta {
    margin-top: 2px;
    color: #425b79;
    font-size: 0.78rem;
}

.client-conv-preview {
    margin-top: 4px;
    color: #4f657f;
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
    min-width: 0;
    background: #ffffff;
}

.client-chat-header {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 64px;
    padding: 10px 16px;
    border-bottom: 1px solid #e1e8f2;
    background: #f7f9fc;
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
    background: radial-gradient(circle at 30% 30%, #4cb0ff, #0f4eb6);
}

.client-chat-name {
    color: #17283c;
    font-weight: 700;
}

.client-chat-sub {
    color: #6b7f99;
    font-size: 0.78rem;
}

.client-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background:
        radial-gradient(circle at 10% 10%, rgba(59, 130, 246, 0.08), transparent 35%),
        radial-gradient(circle at 90% 85%, rgba(2, 132, 199, 0.08), transparent 28%),
        #f6f9ff;
}

/* Ensure scrollbar on the right and enable smooth auto-scroll */
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
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
}

.client-msg-bubble.admin {
    background: #edf2f9;
    color: #1f2c3d;
    border-top-left-radius: 4px;
}

.client-msg-bubble.client {
    background: linear-gradient(135deg, #2f8cff, #2563eb);
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
    border-top: 1px solid #e1e8f2;
    padding: 10px 12px;
    background: #f7f9fc;
}

.client-chat-input-form {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 8px;
    align-items: center;
}

.client-attach-btn {
    color: #647a95;
    text-decoration: none;
    border: 1px solid #c8d4e6;
    background: #ffffff;
    border-radius: 10px;
    width: 40px;
    height: 40px;
}

.client-chat-input-form .form-control {
    background: #ffffff;
    border: 1px solid #cfd9e8;
    border-radius: 12px;
    color: #223448 !important;
}

.client-send-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.28);
}

.client-chat-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    color: #6a7f98;
    gap: 8px;
}

.client-chat-empty i {
    font-size: 2.2rem;
}

@media (max-width: 992px) {
    .client-messages-page {
        height: auto;
        min-height: calc(100vh - 64px);
    }

    .client-chat-shell {
        grid-template-columns: 1fr;
        min-height: calc(100vh - 64px);
    }

    .client-chat-sidebar {
        border-right: 0;
        border-bottom: 1px solid #d6dfeb;
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