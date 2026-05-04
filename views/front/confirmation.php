<?php
$pageTitle = 'Confirmation rendez-vous';
$action = 'frontCalendar';
$extraCss = ['views/css/calendrier.css'];
require __DIR__ . '/layout_header.php';
?>

<div class="confirm-page">
    <div class="confirm-icon"><i class="bi bi-patch-check-fill"></i></div>
    <h1>Votre rendez-vous est enregistré</h1>
    <p>Merci. Votre demande a bien été prise en compte.</p>

    <div class="confirm-card">
        <div><strong>Numéro RDV:</strong> #<?php echo (int) $rdv['id_rdv']; ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($rdv['date_heure']))); ?></div>
        <div><strong>Heure:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($rdv['date_heure']))); ?></div>
        <div><strong>Type de panne:</strong> <?php echo htmlspecialchars($rdv['type_intervention']); ?></div>
        <div><strong>Circonstances:</strong> <?php echo htmlspecialchars($rdv['circonstances_panne'] ?? '-'); ?></div>
        <div><strong>Symptômes:</strong> <?php echo htmlspecialchars($rdv['description_panne'] ?? '-'); ?></div>
        <div><strong>Statut:</strong> <?php echo htmlspecialchars($rdv['statut']); ?></div>
        <div><strong>Remise éco:</strong> <?php echo (float) $rdv['remise_eco_appliquee']; ?>%</div>

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
        ?>

        <div><strong>Photos:</strong></div>
        <?php if (is_array($photos) && !empty($photos)): ?>
            <div class="rdv-images-grid">
                <?php foreach ($photos as $photo): ?>
                    <?php $imgPath = isset($photo['path']) ? (string) $photo['path'] : ''; ?>
                    <?php if ($imgPath === '') { continue; } ?>
                    <a href="<?php echo htmlspecialchars($imgPath); ?>" target="_blank" rel="noopener noreferrer" class="rdv-image-link">
                        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Photo panne RDV <?php echo (int) $rdv['id_rdv']; ?>" class="rdv-image-thumb">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="rdv-images-empty">Aucune image</div>
        <?php endif; ?>
    </div>

    <?php if (!empty($loyaltyWidget)): ?>
        <?php require __DIR__ . '/../components/loyalty_widget.php'; ?>
    <?php endif; ?>

    <a href="index.php?action=frontCalendar" class="btn-sg btn-sg-primary">Prendre un autre rendez-vous</a>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
