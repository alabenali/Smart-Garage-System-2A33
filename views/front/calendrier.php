<?php
$pageTitle = 'Prendre un rendez-vous';
$action = 'frontCalendar';
$extraCss = ['views/css/calendrier.css'];
$extraJs = ['views/js/calendrier_front.js'];

$monthDate = DateTime::createFromFormat('!m', (string) $month);
$monthLabel = $monthDate ? $monthDate->format('F') : date('F');
$monthLabel = ucfirst($monthLabel);

$firstDay = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
$startWeekday = (int) $firstDay->format('N');
$totalDays = (int) $firstDay->format('t');

$dayStats = [];
foreach ($monthAvailability as $item) {
    $day = date('Y-m-d', strtotime($item['date_heure']));
    if (!isset($dayStats[$day])) {
        $dayStats[$day] = [
            'remaining' => 0,
            'slots' => 0,
        ];
    }
    $remaining = max(0, (int) $item['places_restantes']);
    $dayStats[$day]['remaining'] += $remaining;
    $dayStats[$day]['slots']++;
}

$today = date('Y-m-d');

require __DIR__ . '/layout_header.php';
?>

<h1 class="page-title">Calendrier des rendez-vous</h1>
<p class="page-subtitle">Sélectionnez un jour, un créneau puis confirmez votre demande en ligne.</p>

<?php if (!empty($errors)): ?>
    <div class="sg-alert sg-alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="calendar-shell" id="frontCalendarApp" data-month="<?php echo (int) $month; ?>" data-year="<?php echo (int) $year; ?>" data-selected-date="<?php echo htmlspecialchars($selectedDate); ?>">
    <div class="stepper">
        <div class="step-item is-active" data-step="1">1. Jour</div>
        <div class="step-item" data-step="2">2. Créneau</div>
        <div class="step-item" data-step="3">3. Formulaire</div>
        <div class="step-item" data-step="4">4. Confirmation</div>
    </div>

    <section class="calendar-block" data-step-panel="1">
        <div class="calendar-head">
            <a class="btn-sg btn-sg-outline btn-sg-sm" href="index.php?action=frontCalendar&month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
            <h3><?php echo htmlspecialchars($monthLabel . ' ' . $year); ?></h3>
            <a class="btn-sg btn-sg-outline btn-sg-sm" href="index.php?action=frontCalendar&month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <div class="legend-row">
            <span><i class="dot dot-green"></i> Disponible</span>
            <span><i class="dot dot-orange"></i> Presque complet</span>
            <span><i class="dot dot-red"></i> Complet</span>
            <span><i class="dot dot-holiday"></i> Jour férié</span>
            <span><i class="dot dot-gray"></i> Non travaillé / passé</span>
        </div>

        <div class="month-grid">
            <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName): ?>
                <div class="week-label"><?php echo $dayName; ?></div>
            <?php endforeach; ?>

            <?php for ($i = 1; $i < $startWeekday; $i++): ?>
                <div class="day-cell empty"></div>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= $totalDays; $day++): ?>
                <?php
                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $isPast = $currentDate < $today;
                $isSunday = ((int) date('N', strtotime($currentDate)) === 7);
                $statusClass = 'status-green';
                if ($isPast || $isSunday) {
                    $statusClass = 'status-gray';
                } elseif (!isset($dayStats[$currentDate]) || $dayStats[$currentDate]['remaining'] <= 0) {
                    $statusClass = 'status-red';
                } elseif ($dayStats[$currentDate]['remaining'] === 1) {
                    $statusClass = 'status-orange';
                }

                $isDisabled = ($statusClass === 'status-gray');
                $isSelected = ($selectedDate === $currentDate);
                ?>
                <button type="button"
                        class="day-cell <?php echo $statusClass; ?> <?php echo $isSelected ? 'selected' : ''; ?>"
                        data-date="<?php echo $currentDate; ?>"
                        <?php echo $isDisabled ? 'disabled' : ''; ?>>
                    <span class="num"><?php echo $day; ?></span>
                    <span class="status-dot"></span>
                </button>
            <?php endfor; ?>
        </div>
    </section>

    <section class="calendar-block" data-step-panel="2">
        <div class="slots-header">
            <h3>Créneaux du <span id="selectedDayLabel"><?php echo htmlspecialchars(date('d/m/Y', strtotime($selectedDate))); ?></span></h3>
            <p>Choisissez un créneau disponible pour continuer.</p>
        </div>

        <div id="slotsContainer" class="slot-list">
            <?php if (empty($daySlots)): ?>
                <div class="empty-inline">Aucun créneau disponible pour cette date.</div>
            <?php else: ?>
                <?php foreach ($daySlots as $slot): ?>
                    <?php
                    $remaining = max(0, (int) $slot['places_restantes']);
                    $isFull = $remaining <= 0 || (int) $slot['capacite_max'] <= 0;
                    ?>
                    <button type="button"
                            class="slot-item <?php echo $isFull ? 'is-full' : ''; ?>"
                            data-slot-id="<?php echo (int) $slot['id_creneau']; ?>"
                            data-slot-datetime="<?php echo htmlspecialchars($slot['date_heure']); ?>"
                            data-offpeak="<?php echo (int) $slot['est_heure_creuse']; ?>"
                            <?php echo $isFull ? 'disabled' : ''; ?>>
                        <strong><?php echo htmlspecialchars(date('H:i', strtotime($slot['date_heure']))); ?></strong>
                        <span><?php echo $remaining; ?> place(s) restante(s)</span>
                        <?php if ((int) $slot['est_heure_creuse'] === 1): ?>
                            <em>🌙 Heure creuse (remise possible)</em>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="calendar-block" data-step-panel="3">
        <h3>Déclaration de panne</h3>
        <form id="rdvForm" method="POST" action="index.php?action=frontCreateRdv" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="id_creneau" id="id_creneau" value="<?php echo htmlspecialchars($old['id_creneau'] ?? ''); ?>">
            <input type="hidden" name="selected_date" id="selected_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

            <?php
            $oldTemoins = $old['temoins_panne'] ?? [];
            if (!is_array($oldTemoins)) {
                $oldTemoins = [];
            }
            ?>

            <div class="separator-label">Déclaration de panne</div>
            <div class="cal-form-grid">
                <div class="sg-form-group">
                    <label>Type de panne *</label>
                    <select name="type_intervention">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach (['Moteur', 'Boîte de vitesse', 'Freinage', 'Électrique-Batterie', 'Suspension-Direction', 'Climatisation', 'Carrosserie', 'Autre'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo (($old['type_intervention'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Circonstances</label>
                    <select name="circonstances_panne" id="circonstances_panne">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach (['En roulant', 'À l\'arrêt', 'Au démarrage', 'Panne intermittente'] as $item): ?>
                            <option value="<?php echo $item; ?>" <?php echo (($old['circonstances_panne'] ?? '') === $item) ? 'selected' : ''; ?>><?php echo $item; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group full-width">
                    <label>Symptômes observés *</label>
                    <textarea name="description_panne" id="symptomes_panne" rows="4" placeholder="Décrivez ce que vous avez observé : bruits, vibrations, comportement anormal..." required><?php echo htmlspecialchars($old['description_panne'] ?? ''); ?></textarea>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group full-width">
                    <label>Témoins de panne</label>
                    <div class="temoins-grid" id="temoinsPanneGroup">
                        <?php foreach (['Voyant allumé au tableau de bord', 'Bruit anormal', 'Fumée', 'Fuite de liquide', 'Véhicule immobilisé'] as $temoin): ?>
                            <label class="temoin-item">
                                <input type="checkbox" name="temoins_panne[]" value="<?php echo $temoin; ?>" <?php echo in_array($temoin, $oldTemoins, true) ? 'checked' : ''; ?>>
                                <span><?php echo $temoin; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="section-divider" aria-hidden="true"></div>

            <div class="separator-label">Photos</div>
            <div class="cal-form-grid">
                <div class="sg-form-group full-width">
                    <label>Zone de panne ou témoin du tableau de bord - max 5 photos - 10 Mo chacune</label>
                    <div id="panneDropzone" class="photo-dropzone" role="button" tabindex="0" aria-label="Ajouter des photos de la panne">
                        <input type="file" id="pannePhotosInput" name="panne_photos[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple hidden>
                        <div class="photo-drop-content">
                            <strong>Cliquez ou glissez vos photos ici</strong>
                            <small>JPG, PNG, WEBP</small>
                        </div>
                    </div>
                    <div id="photosError" class="invalid-feedback photo-feedback"></div>
                    <div id="photosPreview" class="photo-preview-list"></div>
                    <input type="hidden" name="panne_data_json" id="panne_data_json" value="">
                </div>
            </div>
        </form>
    </section>

    <section class="calendar-block" data-step-panel="4">
        <h3>Récapitulatif</h3>
        <div class="recap-box" id="rdvRecap">
            <div><strong>Date:</strong> <span data-recap="date">-</span></div>
            <div><strong>Heure:</strong> <span data-recap="heure">-</span></div>
            <div><strong>Type de panne:</strong> <span data-recap="intervention">-</span></div>
            <div><strong>Circonstances:</strong> <span data-recap="circonstances">-</span></div>
            <div><strong>Remise:</strong> <span data-recap="remise">-</span></div>
        </div>
        <button type="button" id="confirmRdvBtn" class="btn-sg btn-sg-primary">
            <i class="bi bi-check2-circle"></i> Confirmer mon RDV
        </button>
    </section>

    <div class="step-actions">
        <button type="button" id="prevStep" class="btn-sg btn-sg-outline btn-sg-sm">Précédent</button>
        <button type="button" id="nextStep" class="btn-sg btn-sg-primary btn-sg-sm">Suivant</button>
    </div>
</div>

<script>
const holidays = <?php echo json_encode($holidays ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

document.addEventListener('DOMContentLoaded', function () {
    const dayCells = document.querySelectorAll('.day-cell[data-date]');

    dayCells.forEach(function (cell) {
        const dateValue = cell.getAttribute('data-date') || '';
        if (!dateValue || !Object.prototype.hasOwnProperty.call(holidays, dateValue)) {
            return;
        }

        const holidayName = holidays[dateValue];

        cell.disabled = true;
        cell.classList.add('status-holiday');
        cell.classList.remove('selected');
        cell.setAttribute('title', holidayName);

        const label = document.createElement('small');
        label.className = 'holiday-name';
        label.textContent = holidayName;
        cell.appendChild(label);
    });
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
