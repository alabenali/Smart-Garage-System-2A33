<div class="slot-detail-head">
    <h4>Créneau du <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($slot['date_heure']))); ?></h4>
    <form action="index.php?action=backBlockSlot" method="POST" class="block-slot-form">
        <input type="hidden" name="id_creneau" value="<?php echo (int) $slot['id_creneau']; ?>">
        <label>Capacité</label>
        <input type="number" min="0" name="capacite_max" value="<?php echo (int) $slot['capacite_max']; ?>">
        <button type="submit" class="btn-sg btn-sg-outline btn-sg-sm">Mettre à jour</button>
    </form>
</div>

<?php if (empty($rdvs)): ?>
    <div class="empty-inline">Aucun RDV pour ce créneau.</div>
<?php else: ?>
    <div class="rdv-detail-list">
        <?php foreach ($rdvs as $rdv): ?>
            <?php
            $statusMap = [
                'en attente' => 'en-attente',
                'confirme' => 'confirme',
                'en cours' => 'en-cours',
                'termine' => 'termine',
                'annule' => 'annule',
            ];
            $statusLabelMap = [
                'en attente' => 'En attente',
                'confirme' => 'Confirmé',
                'en cours' => 'En cours',
                'termine' => 'Terminé',
                'annule' => 'Annulé',
            ];

            $statusRaw = trim((string) ($rdv['statut'] ?? ''));
            $statusKey = mb_strtolower($statusRaw, 'UTF-8');
            $statusKey = strtr($statusKey, [
                'à' => 'a', 'â' => 'a', 'ä' => 'a',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'î' => 'i', 'ï' => 'i',
                'ô' => 'o', 'ö' => 'o',
                'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                'ç' => 'c',
            ]);

            $statusClass = $statusMap[$statusKey] ?? 'en-attente';
            $statusLabel = $statusLabelMap[$statusKey] ?? ($statusRaw !== '' ? $statusRaw : 'En attente');
            ?>
            <div class="rdv-card" data-rdv-id="<?php echo (int) $rdv['id_rdv']; ?>">
                <div class="rdv-main">
                    <strong><?php echo htmlspecialchars($rdv['type_intervention']); ?></strong>
                    <span><?php echo htmlspecialchars($rdv['circonstances_panne'] ?? '-'); ?></span>
                    <span><?php echo htmlspecialchars($rdv['description_panne'] ?? '-'); ?></span>
                    <span>
                        <?php
                        $temoins = json_decode((string) ($rdv['temoins_panne'] ?? ''), true);
                        echo htmlspecialchars(is_array($temoins) && !empty($temoins) ? implode(', ', $temoins) : '-');
                        ?>
                    </span>
                    <?php
                    $photos = json_decode((string) ($rdv['photos_json'] ?? ''), true);
                    if (!is_array($photos) || empty($photos)) {
                        $rdvId = (int) ($rdv['id_rdv'] ?? 0);
                        $diskPhotos = glob(__DIR__ . '/../images/pannes/rdv_' . $rdvId . '_*');
                        if (is_array($diskPhotos) && !empty($diskPhotos)) {
                            $photos = array_map(static function ($absPath) {
                                return ['path' => 'views/images/pannes/' . basename((string) $absPath)];
                            }, $diskPhotos);
                        }
                    }
                    if (is_array($photos) && !empty($photos)):
                    ?>
                        <span>
                            Photos: <?php echo count($photos); ?>
                        </span>
                    <?php endif; ?>
                    <span class="status-badge status-<?php echo $statusClass; ?>" data-status-label><?php echo htmlspecialchars($statusLabel); ?></span>
                </div>
                <div class="rdv-actions-inline">
                    <button type="button" class="btn-sg btn-sg-outline btn-sg-sm status-action" data-status="En cours">En cours</button>
                    <button type="button" class="btn-sg btn-sg-success btn-sg-sm status-action" data-status="Terminé">Terminé</button>
                    <button type="button" class="btn-sg btn-sg-danger btn-sg-sm status-action" data-status="Annulé">Annulé</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
