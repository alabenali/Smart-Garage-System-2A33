<?php
$pageTitle = 'Prendre un rendez-vous';
$action = 'frontCalendar';
$extraCss = ['assets/css/calendrier.css'];
$extraJs = ['assets/js/calendrier_front.js'];

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
        <h3>Informations client et véhicule</h3>
        <form id="rdvForm" method="POST" action="index.php?action=frontCreateRdv" novalidate>
            <input type="hidden" name="id_creneau" id="id_creneau" value="<?php echo htmlspecialchars($old['id_creneau'] ?? ''); ?>">
            <input type="hidden" name="selected_date" id="selected_date" value="<?php echo htmlspecialchars($selectedDate); ?>">

            <div class="cal-form-grid">
                <div class="sg-form-group">
                    <label>Nom *</label>
                    <input type="text" name="nom_client" value="<?php echo htmlspecialchars($old['nom_client'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Prénom *</label>
                    <input type="text" name="prenom_client" value="<?php echo htmlspecialchars($old['prenom_client'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Téléphone *</label>
                    <input type="text" name="telephone_client" maxlength="8" value="<?php echo htmlspecialchars($old['telephone_client'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Email</label>
                    <input type="email" name="email_client" value="<?php echo htmlspecialchars($old['email_client'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <div class="separator-label">Véhicule</div>
            <div class="cal-form-grid">
                <div class="sg-form-group">
                    <label>Immatriculation *</label>
                    <input type="text" name="immatriculation" id="immatriculation" value="<?php echo htmlspecialchars($old['immatriculation'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Marque</label>
                    <input type="text" name="marque" value="<?php echo htmlspecialchars($old['marque'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Modèle</label>
                    <input type="text" name="modele" value="<?php echo htmlspecialchars($old['modele'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Année</label>
                    <input type="number" name="annee" value="<?php echo htmlspecialchars($old['annee'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Kilométrage</label>
                    <input type="number" name="kilometrage" value="<?php echo htmlspecialchars($old['kilometrage'] ?? ''); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group">
                    <label>Carburant</label>
                    <select name="carburant">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach (['Essence', 'Diesel', 'Hybride', 'Electrique', 'GPL'] as $fuel): ?>
                            <option value="<?php echo $fuel; ?>" <?php echo (($old['carburant'] ?? '') === $fuel) ? 'selected' : ''; ?>><?php echo $fuel; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <div class="separator-label">Intervention</div>
            <div class="cal-form-grid">
                <div class="sg-form-group">
                    <label>Type d'intervention *</label>
                    <select name="type_intervention">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach (['Vidange', 'Révision', 'Freinage', 'Climatisation', 'Carrosserie', 'Autre'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo (($old['type_intervention'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="sg-form-group full-width">
                    <label>Description / précision</label>
                    <textarea name="description_panne" rows="3"><?php echo htmlspecialchars($old['description_panne'] ?? ''); ?></textarea>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </form>
    </section>

    <section class="calendar-block" data-step-panel="4">
        <h3>Récapitulatif</h3>
        <div class="recap-box" id="rdvRecap">
            <div><strong>Date:</strong> <span data-recap="date">-</span></div>
            <div><strong>Heure:</strong> <span data-recap="heure">-</span></div>
            <div><strong>Véhicule:</strong> <span data-recap="vehicle">-</span></div>
            <div><strong>Intervention:</strong> <span data-recap="intervention">-</span></div>
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

<?php require __DIR__ . '/layout_footer.php'; ?>
