<?php $pageTitle = 'Modifier le Véhicule'; $action = 'editVehicle'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div style="display:flex; align-items:center; gap:1rem; margin-bottom: 2rem;">
    <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title" style="margin-bottom:0.1rem;">Modifier le Véhicule</h1>
        <p class="page-subtitle" style="margin-bottom:0;">
            <?php echo htmlspecialchars($vehicle['marque'] . ' ' . $vehicle['modele']); ?> – #<?php echo $vehicle['id']; ?>
        </p>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="sg-alert sg-alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?php echo $err; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="sg-form-wrap">
    <form method="POST" action="index.php?action=editVehicle&id=<?php echo $vehicle['id']; ?>" id="vehicleForm" novalidate
          onsubmit="return validateVehicleForm(this);">

        <div class="sg-form-grid">
            <!-- Marque -->
            <div class="sg-form-group">
                <label for="marque">Marque</label>
                <input type="text" name="marque" id="marque" placeholder="Ex: Peugeot"
                       value="<?php echo htmlspecialchars($vehicle['marque']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Modèle -->
            <div class="sg-form-group">
                <label for="modele">Modèle</label>
                <input type="text" name="modele" id="modele" placeholder="Ex: 208"
                       value="<?php echo htmlspecialchars($vehicle['modele']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Immatriculation -->
            <div class="sg-form-group">
                <label for="immatriculation">Immatriculation</label>
                <input type="text" name="immatriculation" id="immatriculation" placeholder="Ex: 123 TU 4567"
                       value="<?php echo htmlspecialchars($vehicle['immatriculation']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Couleur -->
            <div class="sg-form-group">
                <label for="couleur">Couleur</label>
                <input type="text" name="couleur" id="couleur" placeholder="Ex: Blanc"
                       value="<?php echo htmlspecialchars($vehicle['couleur']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Année -->
            <div class="sg-form-group">
                <label for="annee">Année</label>
                <input type="text" name="annee" id="annee" placeholder="Ex: 2022"
                       value="<?php echo htmlspecialchars($vehicle['annee']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Kilométrage -->
            <div class="sg-form-group">
                <label for="kilometrage">Kilométrage</label>
                <input type="text" name="kilometrage" id="kilometrage" placeholder="Ex: 35000"
                       value="<?php echo htmlspecialchars($vehicle['kilometrage']); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Carburant -->
            <div class="sg-form-group full-width">
                <label for="carburant">Carburant</label>
                <select name="carburant" id="carburant">
                    <option value="">-- Sélectionner --</option>
                    <?php
                    $fuels = ['Essence', 'Diesel', 'Hybride', 'Electrique', 'GPL'];
                    foreach ($fuels as $f):
                        $selected = ($vehicle['carburant'] === $f) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $f; ?>" <?php echo $selected; ?>><?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <!-- Validation -->
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-check-lg"></i> Sauvegarder les Modifications
                </button>
                <a href="index.php?action=manageVehicles" class="btn-sg btn-sg-outline">Annuler</a>
                <button type="button" class="btn-sg btn-sg-danger" style="margin-left:auto;"
                        onclick="confirmDelete('index.php?action=deleteVehicle&id=<?php echo $vehicle['id']; ?>')">
                    <i class="bi bi-trash3"></i> Supprimer
                </button>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
