<?php
$pageTitle = 'Messages';
$action = 'client_messages';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$vehicleLabel = '';
if (!empty($vehicles) && $vehicleId > 0) {
    foreach ($vehicles as $vehicle) {
        if ((int)($vehicle['id'] ?? 0) === $vehicleId) {
            $vehicleLabel = (string)($vehicle['immatriculation'] ?? '');
            break;
        }
    }
}
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid p-0 d-flex flex-column messages-page" style="min-height: calc(100vh - 68px);">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 messages-sticky-header">
        <div>
            <h1 class="page-title text-white mb-1">Messages</h1>
            <p class="text-muted mb-0">Retrouvez vos conversations avec le garage par intervention.</p>
        </div>
        <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
            <input type="hidden" name="action" value="client_messages">
            <select name="vehicle_id" class="form-select bg-dark text-white border-secondary" style="min-width:220px;">
                <option value="">Tous les vehicules</option>
                <?php foreach (($vehicles ?? []) as $v): ?>
                    <option value="<?php echo (int)$v['id']; ?>" <?php echo ((int)$vehicleId === (int)$v['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)$v['immatriculation']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-light">Filtrer</button>
        </form>
    </div>

    <?php if ($vehicleId > 0): ?>
        <div class="card bg-dark border-0 shadow-sm rounded-4 flex-grow-1 d-flex flex-column" style="margin: 0; border-radius: 0;">
            <div class="card-header bg-secondary bg-opacity-10 border-0 py-3 messages-sticky-subheader">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="text-white fw-semibold">Conversation du vehicule</div>
                        <div class="text-muted small"><?php echo htmlspecialchars($vehicleLabel !== '' ? $vehicleLabel : ('ID ' . (int)$vehicleId)); ?></div>
                    </div>
                    <span class="badge bg-secondary"><?php echo (int)count($messages ?? []); ?> message(s)</span>
                </div>
            </div>
            <div class="card-body flex-grow-1 messages-scroll" id="messagesScroll" style="overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-4">Aucun message pour cette intervention.</div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $isClient = (($msg['expediteur'] ?? '') === 'client');
                        $bubbleClass = $isClient ? 'bg-primary text-white' : 'bg-secondary text-dark';
                        $alignClass = $isClient ? 'justify-content-end' : 'justify-content-start';
                        $authorLabel = $isClient ? 'Vous' : 'Garage';
                        $msgDate = !empty($msg['date_envoi']) ? date('d/m/Y H:i', strtotime((string)$msg['date_envoi'])) : '-';
                        ?>
                        <div class="d-flex <?php echo $alignClass; ?> mb-3">
                            <div class="px-3 py-2 rounded-3 <?php echo $bubbleClass; ?>" style="max-width: 70%;">
                                <div class="small opacity-75 mb-1"><?php echo htmlspecialchars($authorLabel); ?> · <?php echo htmlspecialchars($msgDate); ?></div>
                                <div><?php echo htmlspecialchars((string)($msg['contenu'] ?? '')); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-secondary bg-opacity-10 border-0" style="position: sticky; bottom: 0; z-index: 2; padding: 12px 16px;">
                <?php if (!empty($selectedInterventionId)): ?>
                    <form method="POST" action="index.php?action=client_messages" class="d-flex gap-2 align-items-center" id="messagesSendForm">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="sender" value="client">
                        <input type="hidden" name="id_intervention" value="<?php echo (int)$selectedInterventionId; ?>">
                        <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicleId; ?>">
                        <input type="text" name="contenu" class="form-control" placeholder="Ecrire un message..." autocomplete="off" required>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </form>
                <?php else: ?>
                    <div class="text-muted">Aucune intervention active pour ce vehicule.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card bg-dark border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                Selectionnez un vehicule pour afficher la conversation.
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.page-wrapper {
    padding: 0 !important;
}

.container-fluid.p-0 {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.messages-page {
    padding-top: 84px;
    height: calc(100vh - 68px);
    overflow: hidden;
    box-sizing: border-box;
    padding-bottom: 0;
}

.messages-page .card {
    min-height: 0;
    height: 100%;
    margin-bottom: 0;
}

.messages-page .card-body {
    min-height: 0;
    overflow-y: auto;
}

.messages-scroll {
    padding: 94px 16px 24px;
    scroll-padding-top: 90px;
    scroll-padding-bottom: 24px;
}

.messages-sticky-header {
    position: fixed;
    top: 0;
    right: 0;
    left: 260px;
    z-index: 4;
    background: var(--bg);
    padding: 12px 16px;
    margin: 0;
    border-bottom: 1px solid var(--border);
}

.messages-sticky-subheader {
    position: sticky;
    top: 84px;
    z-index: 2;
    background: var(--surface-2);
    margin-top: 8px;
}

@media (max-width: 992px) {
    .messages-sticky-header {
        left: 0;
    }
}
</style>

<script>
(function () {
    function scrollToBottom() {
        var box = document.getElementById('messagesScroll');
        if (!box) {
            return;
        }
        box.scrollTop = box.scrollHeight;
    }

    window.addEventListener('load', scrollToBottom);

    var form = document.getElementById('messagesSendForm');
    if (form) {
        form.addEventListener('submit', function () {
            setTimeout(scrollToBottom, 50);
        });
    }
})();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
