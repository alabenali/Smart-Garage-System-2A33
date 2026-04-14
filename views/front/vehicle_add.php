<?php $pageTitle = 'Ajouter un Véhicule'; $action = 'addVehicle'; ?>
<?php require_once __DIR__ . '/../../helpers/PlateHelper.php'; ?>
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
                <?php
                $currentImmat = (string) ($old['immatriculation'] ?? '');
                $detectedSeries = (stripos(strtoupper($currentImmat), 'RS') !== false) ? 'RS' : 'TU';
                ?>
                <div style="display:flex; gap:0.8rem; margin-bottom:0.45rem; align-items:center; flex-wrap:wrap;">
                    <label style="display:inline-flex; align-items:center; gap:0.35rem; margin:0;">
                        <input type="radio" name="plate_series" value="TU" <?php echo $detectedSeries === 'TU' ? 'checked' : ''; ?>>
                        Série TU
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:0.35rem; margin:0;">
                        <input type="radio" name="plate_series" value="RS" <?php echo $detectedSeries === 'RS' ? 'checked' : ''; ?>>
                        Série RS
                    </label>
                </div>
                <input type="text" name="immatriculation" id="immatriculation" placeholder="<?php echo $detectedSeries === 'RS' ? '123RS4567' : '123TU4567'; ?>"
                       value="<?php echo htmlspecialchars($currentImmat); ?>">
                <div id="platePreview" style="margin-top:0.5rem; min-height:32px;">
                    <?php echo $currentImmat !== '' ? formatPlate($currentImmat) : ''; ?>
                </div>
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

            <!-- Validation -->
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-plus-lg"></i> Ajouter le Véhicule
                </button>
                <a href="index.php?action=showVehicles" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<script>
(function () {
    const form = document.getElementById('vehicleForm');
    if (!form) {
        return;
    }

    const input = form.querySelector('#immatriculation');
    const preview = form.querySelector('#platePreview');
    const radios = form.querySelectorAll('input[name="plate_series"]');

    function escapeHtml(value) {
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function currentSeries() {
        const selected = form.querySelector('input[name="plate_series"]:checked');
        return selected ? selected.value : 'TU';
    }

    function normalizePlate(value) {
        return value.toUpperCase().trim().replace(/\s+/g, '').replace(/[\-.]/g, '');
    }

    function renderPlate(rawValue) {
        const normalized = normalizePlate(rawValue);
        const series = currentSeries();
        const tuMatch = normalized.match(/^(\d{1,3})TU(\d{1,4})$/i);
        const rsMatch = normalized.match(/^(\d{1,3})RS(\d{1,4})$/i);

        if (series === 'TU' && tuMatch) {
            return '<span class="tn-plate">'
                + '<span class="tn-plate-left">' + escapeHtml(tuMatch[1]) + '</span>'
                + '<span class="tn-plate-center">تونس</span>'
                + '<span class="tn-plate-right">' + escapeHtml(tuMatch[2]) + '</span>'
                + '</span>';
        }

        if (series === 'RS' && rsMatch) {
            return '<span class="tn-plate tn-plate-rs" title="Série RS">'
                + '<span class="tn-plate-rs-ar">ن.ت</span>'
                + '<span class="tn-plate-rs-sep"></span>'
                + '<span class="tn-plate-rs-right">' + escapeHtml(rsMatch[2]) + '</span>'
                + '<span class="tn-plate-rs-left">' + escapeHtml(rsMatch[1]) + '</span>'
                + '</span>';
        }

        if (!normalized) {
            return '';
        }

        return '<span class="tn-plate-neutral">' + escapeHtml(rawValue.trim()) + '</span>';
    }

    function refresh() {
        input.placeholder = currentSeries() === 'RS' ? '123RS4567' : '123TU4567';
        preview.innerHTML = renderPlate(input.value || '');
    }

    radios.forEach((radio) => radio.addEventListener('change', refresh));
    input.addEventListener('input', refresh);
    refresh();
})();
</script>

<?php require __DIR__ . '/layout_footer.php'; ?>
