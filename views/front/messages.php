<?php
$pageTitle = 'Messages';
$action = 'client_messages';
$vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
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

    <?php if (empty($threads)): ?>
        <div class="card bg-dark border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-5">
                Aucune conversation disponible pour le moment.
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($threads as $thread): ?>
                <?php
                $inter = $thread['intervention'];
                $last = $thread['last_message'];
                $msgDate = !empty($last['date_envoi']) ? date('d/m/Y H:i', strtotime((string)$last['date_envoi'])) : '-';
                $isClient = (($last['expediteur'] ?? '') === 'client');
                ?>
                <div class="col-12">
                    <div class="card bg-dark border-0 shadow-sm rounded-4">
                        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <span class="badge bg-primary">Intervention #<?php echo (int)($inter['id_intervention'] ?? 0); ?></span>
                                    <span class="badge bg-warning text-dark"><?php echo htmlspecialchars((string)($inter['statut_devis'] ?? 'en_attente')); ?></span>
                                    <span class="badge bg-secondary"><?php echo (int)($thread['messages_count'] ?? 0); ?> message(s)</span>
                                </div>
                                <div class="text-white fw-semibold mb-1">
                                    <?php echo htmlspecialchars((string)($inter['immatriculation'] ?? '-')); ?>
                                    - <?php echo htmlspecialchars((string)($inter['type_nom'] ?? 'Intervention')); ?>
                                </div>
                                <div class="text-muted">
                                    <span class="me-2"><?php echo $isClient ? 'Vous' : 'Garage'; ?>:</span>
                                    <?php echo htmlspecialchars((string)($last['contenu'] ?? '')); ?>
                                </div>
                            </div>
                            <div class="text-lg-end w-100 w-lg-auto">
                                <div class="text-muted small mb-2">Dernier message: <?php echo htmlspecialchars($msgDate); ?></div>
                                <a href="index.php?action=intervention_chat&id=<?php echo (int)($inter['id_intervention'] ?? 0); ?>&vehicle_id=<?php echo (int)($inter['id_vehicule'] ?? 0); ?>" class="btn btn-primary">
                                    Ouvrir la conversation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
