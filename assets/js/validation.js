// ============================================
// Smart Garage System – Client-Side Validation
// No HTML5 validation attributes used.
// ============================================

/**
 * Validate a vehicle form (add or edit).
 * @param {HTMLFormElement} form - the form element
 * @returns {boolean} true if valid
 */
function validateVehicleForm(form) {
    clearErrors(form);
    let isValid = true;

    const marque = form.querySelector('[name="marque"]');
    const modele = form.querySelector('[name="modele"]');
    const immatriculation = form.querySelector('[name="immatriculation"]');
    const couleur = form.querySelector('[name="couleur"]');
    const annee = form.querySelector('[name="annee"]');
    const kilometrage = form.querySelector('[name="kilometrage"]');
    const carburant = form.querySelector('[name="carburant"]');

    // Marque – required
    if (!marque.value.trim()) {
        showError(marque, 'La marque est obligatoire.');
        isValid = false;
    }

    // Modèle – required
    if (!modele.value.trim()) {
        showError(modele, 'Le modèle est obligatoire.');
        isValid = false;
    }

    // Immatriculation – required + format
    const immatVal = immatriculation.value.trim().toUpperCase();
    if (!immatVal) {
        showError(immatriculation, "L'immatriculation est obligatoire.");
        isValid = false;
    } else if (!/^\d{3}TN\d{4}$/.test(immatVal)) {
        showError(immatriculation, 'Format invalide (ex: 111TN9999).');
        isValid = false;
    }

    // Couleur – required
    if (!couleur.value.trim()) {
        showError(couleur, 'La couleur est obligatoire.');
        isValid = false;
    }

    // Année – required, between 1990 and current year
    const currentYear = new Date().getFullYear();
    const anneeVal = annee.value.trim();
    if (!anneeVal) {
        showError(annee, "L'année est obligatoire.");
        isValid = false;
    } else if (isNaN(anneeVal) || parseInt(anneeVal) < 1990 || parseInt(anneeVal) > currentYear) {
        showError(annee, `L'année doit être entre 1990 et ${currentYear}.`);
        isValid = false;
    }

    // Kilométrage – required, positive number
    const kmVal = kilometrage.value.trim();
    if (kmVal === '') {
        showError(kilometrage, 'Le kilométrage est obligatoire.');
        isValid = false;
    } else if (isNaN(kmVal) || parseInt(kmVal) < 0) {
        showError(kilometrage, 'Le kilométrage doit être un nombre positif.');
        isValid = false;
    }

    // Carburant – required
    if (!carburant.value.trim()) {
        showError(carburant, 'Le type de carburant est obligatoire.');
        isValid = false;
    }

    return isValid;
}

// ---- Helper: show error on a field ----
function showError(input, message) {
    input.classList.add('is-invalid');
    // Look for a sibling .invalid-feedback
    let feedback = input.parentElement.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentElement.appendChild(feedback);
    }
    feedback.textContent = message;
    feedback.style.display = 'block';
}

// ---- Helper: clear all errors ----
function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
}

// ---- Real-time: remove error on input ----
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sg-form-group input, .sg-form-group select').forEach(function (el) {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            const fb = this.parentElement.querySelector('.invalid-feedback');
            if (fb) { fb.textContent = ''; fb.style.display = 'none'; }
        });
    });
});

// ---- Confirm delete ----
function confirmDelete(url) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ? Cette action est irréversible.')) {
        window.location.href = url;
    }
}
