<?php
$pageTitle = 'Modifier RDV';
$action = 'backRdvList';
$extraCss = ['views/css/calendrier.css'];
require __DIR__ . '/layout_header.php';

$selectedClientId = (int) ($rdv['id_client'] ?? 0);
$selectedVehicleId = (int) ($rdv['id_vehicle'] ?? 0);
$temoins = json_decode((string) ($rdv['temoins_panne'] ?? '[]'), true);
$temoins = is_array($temoins) ? $temoins : [];
?>

<div class="detail-page-head">
    <a href="index.php?action=backRdvList" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title">Modifier le rendez-vous</h1>
        <p class="page-subtitle" style="margin-bottom:0;">RDV #<?php echo (int) ($rdv['id_rdv'] ?? 0); ?></p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i><div><?php foreach ($errors as $err): ?><div><?php echo htmlspecialchars($err); ?></div><?php endforeach; ?></div></div>
<?php endif; ?>

<div class="sg-form-wrap">
    <form method="POST" action="index.php?action=backEditRdv&id=<?php echo (int) ($rdv['id_rdv'] ?? 0); ?>" class="sg-form-grid">
        <input type="hidden" name="id_rdv" value="<?php echo (int) ($rdv['id_rdv'] ?? 0); ?>">
        <div class="sg-form-group">
            <label>ID creneau</label>
            <input type="number" name="id_creneau" value="<?php echo (int) ($rdv['id_creneau'] ?? 0); ?>">
        </div>
        <div class="sg-form-group">
            <label>Date/heure manuelle</label>
            <input type="datetime-local" name="date_heure_manual" value="<?php echo !empty($rdv['date_heure']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime((string) $rdv['date_heure']))) : ''; ?>">
        </div>
        <div class="sg-form-group full-width">
            <label>Client</label>
            <select name="id_client" id="rdvClientSelect">
                <option value="">-- Client non rattache --</option>
                <?php foreach (($clients ?? []) as $client): ?>
                    <?php
                    $clientId = (int) ($client['id'] ?? 0);
                    $label = trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) . ' - ' . ($client['email'] ?? '');
                    ?>
                    <option value="<?php echo $clientId; ?>"
                            data-nom="<?php echo htmlspecialchars($client['nom'] ?? ''); ?>"
                            data-prenom="<?php echo htmlspecialchars($client['prenom'] ?? ''); ?>"
                            data-telephone="<?php echo htmlspecialchars($client['telephone'] ?? ''); ?>"
                            data-email="<?php echo htmlspecialchars($client['email'] ?? ''); ?>"
                            <?php echo $selectedClientId === $clientId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group full-width">
            <label>Vehicule</label>
            <select name="id_vehicule" id="rdvVehicleSelect">
                <option value="">-- Selectionner --</option>
                <?php foreach (($vehicles ?? []) as $vehicle): ?>
                    <?php
                    $vehicleId = (int) ($vehicle['id'] ?? 0);
                    $label = trim(($vehicle['marque'] ?? '') . ' ' . ($vehicle['modele'] ?? '') . ' - ' . ($vehicle['immatriculation'] ?? ''));
                    ?>
                    <option value="<?php echo $vehicleId; ?>" data-client-id="<?php echo (int) ($vehicle['id_client'] ?? 0); ?>" <?php echo $selectedVehicleId === $vehicleId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group">
            <label>Nom</label>
            <input type="text" name="nom_client" value="<?php echo htmlspecialchars($rdv['nom_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Prenom</label>
            <input type="text" name="prenom_client" value="<?php echo htmlspecialchars($rdv['prenom_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Telephone</label>
            <input type="text" name="telephone_client" value="<?php echo htmlspecialchars($rdv['telephone_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Email</label>
            <input type="email" name="email_client" value="<?php echo htmlspecialchars($rdv['email_client'] ?? ''); ?>">
        </div>
        <div class="sg-form-group">
            <label>Type intervention</label>
            <select name="type_intervention" required>
                <?php foreach (['Vidange', 'Révision', 'Changement de pneu', 'Pneumatiques', 'Batterie', 'Freinage', 'Moteur', 'Boîte de vitesse', 'Électrique-Batterie', 'Suspension-Direction', 'Climatisation', 'Carrosserie', 'Diagnostic général', 'Autre'] as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($rdv['type_intervention'] ?? '') === $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group">
            <label>Circonstances</label>
            <select name="circonstances_panne">
                <option value="">-- Non renseigne --</option>
                <?php foreach (['En roulant', 'À l\'arrêt', 'Au démarrage', 'Panne intermittente'] as $circ): ?>
                    <option value="<?php echo htmlspecialchars($circ); ?>" <?php echo (($rdv['circonstances_panne'] ?? '') === $circ) ? 'selected' : ''; ?>><?php echo htmlspecialchars($circ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group">
            <label>Statut</label>
            <select name="statut">
                <?php foreach (['En attente', 'Confirmé', 'En cours', 'Terminé', 'Annulé'] as $status): ?>
                    <option value="<?php echo htmlspecialchars($status); ?>" <?php echo (($rdv['statut'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo htmlspecialchars($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group">
            <label>Temoins</label>
            <select name="temoins_panne[]" multiple size="4">
                <?php foreach (['Voyant moteur', 'Bruit anormal', 'Fumee', 'Odeur', 'Perte puissance', 'Fuite'] as $temoin): ?>
                    <option value="<?php echo htmlspecialchars($temoin); ?>" <?php echo in_array($temoin, $temoins, true) ? 'selected' : ''; ?>><?php echo htmlspecialchars($temoin); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sg-form-group full-width">
            <label>Description</label>
            <textarea name="description_panne" rows="4"><?php echo htmlspecialchars($rdv['description_panne'] ?? ''); ?></textarea>
        </div>
        <div class="sg-form-group full-width">
            <label>Notes admin</label>
            <textarea name="notes" rows="3"><?php echo htmlspecialchars($rdv['notes'] ?? ''); ?></textarea>
        </div>
        <div class="sg-form-actions">
            <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-check-lg"></i> Sauvegarder</button>
            <a href="index.php?action=backRdvList" class="btn-sg btn-sg-outline">Annuler</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('rdvClientSelect');
    const vehicleSelect = document.getElementById('rdvVehicleSelect');
    if (!clientSelect || !vehicleSelect) return;

    function filterVehicles() {
        const selectedClient = clientSelect.value || '';
        Array.from(vehicleSelect.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const vehicleClient = option.getAttribute('data-client-id') || '';
            option.hidden = selectedClient !== '' && vehicleClient !== selectedClient;
        });
        if (vehicleSelect.selectedOptions[0] && vehicleSelect.selectedOptions[0].hidden) {
            vehicleSelect.value = '';
        }
    }

    clientSelect.addEventListener('change', filterVehicles);
    filterVehicles();
});
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
