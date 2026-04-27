<?php $pageTitle = 'Ajouter un Véhicule'; $action = 'addVehicle'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<h1 class="page-title">Ajouter un Véhicule</h1>
<p class="page-subtitle">Remplissez le formulaire ci-dessous pour enregistrer un nouveau véhicule.</p>

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
    <form method="POST" action="index.php?action=addVehicle" id="vehicleForm" novalidate
          onsubmit="return validateVehicleForm(this);">

        <div class="sg-form-grid">
            <!-- Marque -->
            <div class="sg-form-group">
                <label for="marque">Marque</label>
                <input type="text" name="marque" id="marque" placeholder="Ex: Peugeot"
                       value="<?php echo htmlspecialchars($old['marque'] ?? ''); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Modèle -->
            <div class="sg-form-group">
                <label for="modele">Modèle</label>
                <input type="text" name="modele" id="modele" placeholder="Ex: 208"
                       value="<?php echo htmlspecialchars($old['modele'] ?? ''); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Immatriculation -->
            <div class="sg-form-group">
                <label for="immatriculation">Immatriculation</label>
                <input type="text" name="immatriculation" id="immatriculation" placeholder="Ex: 123 TU 4567"
                       value="<?php echo htmlspecialchars($old['immatriculation'] ?? ''); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Couleur -->
            <div class="sg-form-group">
                <label for="couleur">Couleur</label>
                <input type="text" name="couleur" id="couleur" placeholder="Ex: Blanc"
                       value="<?php echo htmlspecialchars($old['couleur'] ?? ''); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Année -->
            <div class="sg-form-group">
                <label for="annee">Année</label>
                <input type="text" name="annee" id="annee" placeholder="Ex: 2022"
                       value="<?php echo htmlspecialchars($old['annee'] ?? ''); ?>">
                <div class="invalid-feedback"></div>
            </div>

            <!-- Kilométrage -->
            <div class="sg-form-group">
                <label for="kilometrage">Kilométrage</label>
                <input type="text" name="kilometrage" id="kilometrage" placeholder="Ex: 35000"
                       value="<?php echo htmlspecialchars($old['kilometrage'] ?? ''); ?>">
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
                        $selected = (isset($old['carburant']) && $old['carburant'] === $f) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $f; ?>" <?php echo $selected; ?>><?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <!-- Submit -->
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-plus-lg"></i> Ajouter le Véhicule
                </button>
                <a href="index.php?action=showVehicles" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
