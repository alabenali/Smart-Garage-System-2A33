<?php $pageTitle = 'Ajouter le Vehicule'; $action = 'addVehicleBack'; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom: 2rem;">
    <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title" style="margin-bottom:0.1rem;">Ajouter un vehicule</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Creation admin avec assignation client.</p>
    </div>
</div>

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

<div class="sg-form-wrap">
    <form method="POST" action="index.php?action=addVehicleBack" id="vehicleForm" novalidate onsubmit="return validateVehicleForm(this);">
        <div class="sg-form-grid">
            <div class="sg-form-group full-width">
                <label for="id_client">Client proprietaire</label>
                <select name="id_client" id="id_client">
                    <option value="">-- Non assigne --</option>
                    <?php foreach (($clients ?? []) as $client): ?>
                        <?php
                        $clientId = (int) ($client['id'] ?? 0);
                        $selected = ((int) ($vehicle['id_client'] ?? 0) === $clientId) ? 'selected' : '';
                        $clientLabel = trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? '')) . ' - ' . ($client['email'] ?? '');
                        ?>
                        <option value="<?php echo $clientId; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($clientLabel); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="sg-form-group">
                <label for="marque">Marque</label>
                <input type="text" name="marque" id="marque" value="<?php echo htmlspecialchars((string) ($vehicle['marque'] ?? '')); ?>" list="vehicle-brand-suggestions" autocomplete="off">
                <datalist id="vehicle-brand-suggestions">
                    <?php foreach (($brandSuggestions ?? []) as $brand): ?>
                        <option value="<?php echo htmlspecialchars($brand); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-group">
                <label for="modele">Modele</label>
                <input type="text" name="modele" id="modele" value="<?php echo htmlspecialchars((string) ($vehicle['modele'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-group">
                <label for="immatriculation">Immatriculation</label>
                <input type="text" name="immatriculation" id="immatriculation" value="<?php echo htmlspecialchars((string) ($vehicle['immatriculation'] ?? '')); ?>" placeholder="123TU4567 ou RS12345">
                <div id="platePreview" style="margin-top:0.5rem; min-height:32px;"></div>
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-group">
                <label for="couleur">Couleur</label>
                <input type="text" name="couleur" id="couleur" value="<?php echo htmlspecialchars((string) ($vehicle['couleur'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-group">
                <label for="annee">Annee</label>
                <input type="text" name="annee" id="annee" value="<?php echo htmlspecialchars((string) ($vehicle['annee'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-group">
                <label for="kilometrage">Kilometrage</label>
                <input type="text" name="kilometrage" id="kilometrage" value="<?php echo htmlspecialchars((string) ($vehicle['kilometrage'] ?? '')); ?>">
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-group full-width">
                <label for="carburant">Carburant</label>
                <select name="carburant" id="carburant">
                    <option value="">-- Selectionner --</option>
                    <?php foreach (['Essence', 'Diesel', 'Hybride', 'Electrique', 'GPL'] as $fuel): ?>
                        <option value="<?php echo $fuel; ?>" <?php echo (($vehicle['carburant'] ?? '') === $fuel) ? 'selected' : ''; ?>><?php echo $fuel; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
