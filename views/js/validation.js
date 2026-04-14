// ============================================
// Système Smart Garage – Validation Côté Client
// Aucun attribut de validation HTML5 utilisé.
// ============================================

/**
 * Valider un formulaire de véhicule (ajout ou modification).
 * @param {HTMLFormElement} form - l'élément formulaire
 * @returns {boolean} true si valide
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

    // Marque – obligatoire
    if (!marque.value.trim()) {
        showError(marque, 'La marque est obligatoire.');
        isValid = false;
    }

    // Modèle – obligatoire
    if (!modele.value.trim()) {
        showError(modele, 'Le modèle est obligatoire.');
        isValid = false;
    }

    // Immatriculation – obligatoire + format
    const immatVal = immatriculation.value.trim();
    const normalizedImmat = immatVal.toUpperCase().replace(/\s+/g, '').replace(/[\-.]/g, '');
    if (!immatVal) {
        showError(immatriculation, "L'immatriculation est obligatoire.");
        isValid = false;
    } else if (!/^\d{1,3}TU\d{1,4}$/i.test(normalizedImmat) && !/^\d{1,3}RS\d{1,4}$/i.test(normalizedImmat)) {
        showError(immatriculation, 'Format invalide. Utilisez le format 123TU4567 ou 123RS4567.');
        isValid = false;
    }

    // Couleur – obligatoire
    if (!couleur.value.trim()) {
        showError(couleur, 'La couleur est obligatoire.');
        isValid = false;
    }

    // Année – obligatoire, entre 1990 et l'année en cours
    const currentYear = new Date().getFullYear();
    const anneeVal = annee.value.trim();
    if (!anneeVal) {
        showError(annee, "L'année est obligatoire.");
        isValid = false;
    } else if (isNaN(anneeVal) || parseInt(anneeVal) < 1990 || parseInt(anneeVal) > currentYear) {
        showError(annee, `L'année doit être entre 1990 et ${currentYear}.`);
        isValid = false;
    }

    // Kilométrage – obligatoire, nombre positif
    const kmVal = kilometrage.value.trim();
    if (kmVal === '') {
        showError(kilometrage, 'Le kilométrage est obligatoire.');
        isValid = false;
    } else if (isNaN(kmVal) || parseInt(kmVal) < 0) {
        showError(kilometrage, 'Le kilométrage doit être un nombre positif.');
        isValid = false;
    }

    // Carburant – obligatoire
    if (!carburant.value.trim()) {
        showError(carburant, 'Le type de carburant est obligatoire.');
        isValid = false;
    }

    return isValid;
}

// ---- Helper : afficher une erreur sur un champ ----
function showError(input, message) {
    input.classList.add('is-invalid');
    // Chercher un élément .invalid-feedback parmi les frères
    let feedback = input.parentElement.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentElement.appendChild(feedback);
    }
    feedback.textContent = message;
    feedback.style.display = 'block';
}

// ---- Helper : effacer toutes les erreurs ----
function clearErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
}

// ---- Temps réel : enlever l'erreur lors de la saisie ----
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sg-form-group input, .sg-form-group select').forEach(function (el) {
        el.addEventListener('input', function () {
            this.classList.remove('is-invalid');
            const fb = this.parentElement.querySelector('.invalid-feedback');
            if (fb) { fb.textContent = ''; fb.style.display = 'none'; }
        });
    });
});

// ---- Confirmer la suppression ----
function confirmDelete(url) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ? Cette action est irréversible.')) {
        window.location.href = url;
    }
}
