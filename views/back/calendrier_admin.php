<?php
$pageTitle = 'Calendrier RDV';
$action = 'backCalendar';
$extraCss = ['views/css/calendrier.css'];
$extraJs = ['views/js/calendrier_back.js'];
require __DIR__ . '/layout_header.php';
?>

<h1 class="page-title">Calendrier hebdomadaire des rendez-vous</h1>
<p class="page-subtitle">Vue semaine (lundi à samedi) avec gestion rapide des statuts et des créneaux.</p>

<div class="stats-grid rdv-stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-calendar-day"></i></div>
        <div class="stat-value"><?php echo (int) $stats['rdv_jour']; ?></div>
        <div class="stat-label">RDV du jour</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="bi bi-calendar-week"></i></div>
        <div class="stat-value"><?php echo (int) $stats['rdv_semaine']; ?></div>
        <div class="stat-label">RDV de la semaine</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-value"><?php echo (int) $stats['rdv_attente']; ?></div>
        <div class="stat-label">En attente</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-bar-chart-line"></i></div>
        <div class="stat-value"><?php echo (float) $stats['taux_remplissage']; ?>%</div>
        <div class="stat-label">Taux de remplissage</div>
    </div>
</div>

<div class="week-toolbar">
    <a class="btn-sg btn-sg-outline btn-sg-sm" href="index.php?action=backCalendar&week_date=<?php echo $weekStart->modify('-7 days')->format('Y-m-d'); ?>">
        <i class="bi bi-chevron-left"></i> Semaine précédente
    </a>
    <h3><?php echo $weekStart->format('d/m/Y'); ?> - <?php echo $weekEnd->format('d/m/Y'); ?></h3>
    <a class="btn-sg btn-sg-outline btn-sg-sm" href="index.php?action=backCalendar&week_date=<?php echo $weekStart->modify('+7 days')->format('Y-m-d'); ?>">
        Semaine suivante <i class="bi bi-chevron-right"></i>
    </a>
</div>

<div class="admin-calendar-wrap" id="adminCalendar" data-details-url="index.php?action=backSlotDetails" data-status-url="index.php?action=backUpdateStatus">
    <div class="week-grid-wrap">
        <table class="week-grid-table">
            <thead>
                <tr>
                    <th>Heure</th>
                    <?php foreach ($weekDays as $day): ?>
                        <th><?php echo ucfirst($day->format('D')); ?><br><?php echo $day->format('d/m'); ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th></th>
                    <?php foreach ($weekDays as $day): ?>
                        <?php
                        $dayKey = $day->format('Y-m-d');
                        $holidayName = $holidays[$dayKey] ?? '';
                        ?>
                        <th class="holiday-head-cell">
                            <?php if ($holidayName !== ''): ?>
                                <div class="holiday-banner" title="<?php echo htmlspecialchars($holidayName); ?>">
                                    <?php echo htmlspecialchars($holidayName); ?>
                                </div>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hours as $hour): ?>
                    <tr>
                        <td class="time-col"><?php echo $hour; ?></td>
                        <?php foreach ($weekDays as $day): ?>
                            <?php
                            $dayKey = $day->format('Y-m-d');
                            $holidayName = $holidays[$dayKey] ?? '';
                            $isHoliday = ($holidayName !== '');
                            $cell = $grid[$hour][$dayKey] ?? null;
                            $idCreneau = $cell['id_creneau'] ?? 0;
                            $cap = isset($cell['capacite_max']) ? (int) $cell['capacite_max'] : 0;
                            $active = isset($cell['nb_actifs']) ? (int) $cell['nb_actifs'] : 0;
                            $isBlocked = $cap === 0;
                            ?>
                            <td>
                                <?php if ($isHoliday): ?>
                                    <div class="grid-cell holiday-unavailable" title="<?php echo htmlspecialchars($holidayName); ?>">
                                        <span>Férié</span>
                                    </div>
                                <?php elseif ($idCreneau > 0): ?>
                                    <button type="button" class="grid-cell <?php echo $isBlocked ? 'blocked' : ''; ?>" data-id-creneau="<?php echo (int) $idCreneau; ?>">
                                        <?php if ($isBlocked): ?>
                                            <span>Bloqué</span>
                                        <?php else: ?>
                                            <span><?php echo $active; ?>/<?php echo $cap; ?></span>
                                        <?php endif; ?>
                                    </button>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <aside class="slot-sidebar" id="slotSidebar">
        <div class="sidebar-placeholder">Cliquez sur une cellule pour afficher le détail du créneau.</div>
    </aside>
</div>

<script>
const holidays = <?php echo json_encode($holidays ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>

<?php if (!empty($manualErrors)): ?>
    <div class="sg-alert sg-alert-danger mt-3">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <?php foreach ($manualErrors as $err): ?>
                <div><?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="sg-form-wrap mt-4">
    <h3 style="margin-bottom:1rem;">Créer un RDV manuellement</h3>
    <form action="index.php?action=backCreateManualRdv" method="POST" class="cal-form-grid">
        <div class="sg-form-group">
            <label>ID créneau existant (optionnel)</label>
            <input type="number" name="id_creneau" value="<?php echo htmlspecialchars($manualOld['id_creneau'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Date/heure manuelle (optionnel)</label>
            <input type="datetime-local" name="date_heure_manual" value="<?php echo htmlspecialchars($manualOld['date_heure_manual'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Nom *</label>
            <input type="text" name="nom_client" value="<?php echo htmlspecialchars($manualOld['nom_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Prénom *</label>
            <input type="text" name="prenom_client" value="<?php echo htmlspecialchars($manualOld['prenom_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Téléphone *</label>
            <input type="text" name="telephone_client" maxlength="8" value="<?php echo htmlspecialchars($manualOld['telephone_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Email</label>
            <input type="email" name="email_client" value="<?php echo htmlspecialchars($manualOld['email_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Immatriculation *</label>
            <input type="text" name="immatriculation" value="<?php echo htmlspecialchars($manualOld['immatriculation'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Marque</label>
            <input type="text" name="marque" value="<?php echo htmlspecialchars($manualOld['marque'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Modèle</label>
            <input type="text" name="modele" value="<?php echo htmlspecialchars($manualOld['modele'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Année</label>
            <input type="number" name="annee" value="<?php echo htmlspecialchars($manualOld['annee'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Kilométrage</label>
            <input type="number" name="kilometrage" value="<?php echo htmlspecialchars($manualOld['kilometrage'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Carburant</label>
            <select name="carburant">
                <option value="">-- Sélectionner --</option>
                <?php foreach (['Essence', 'Diesel', 'Hybride', 'Electrique', 'GPL'] as $fuel): ?>
                    <option value="<?php echo $fuel; ?>" <?php echo (($manualOld['carburant'] ?? '') === $fuel) ? 'selected' : ''; ?>><?php echo $fuel; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group">
            <label>Type intervention *</label>
            <select name="type_intervention" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach (['Vidange', 'Révision', 'Changement de pneu', 'Pneumatiques', 'Batterie', 'Freinage', 'Moteur', 'Boîte de vitesse', 'Électrique-Batterie', 'Suspension-Direction', 'Climatisation', 'Carrosserie', 'Diagnostic général', 'Autre'] as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo (($manualOld['type_intervention'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group full-width">
            <label>Description</label>
            <textarea name="description_panne" rows="3"><?php echo htmlspecialchars($manualOld['description_panne'] ?? ''); ?></textarea>
        </div>
        <div class="sg-form-actions">
            <button class="btn-sg btn-sg-primary" type="submit"><i class="bi bi-plus-circle"></i> Créer RDV</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
