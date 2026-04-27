// public/js/validate-profile.js
// Contrôle de saisie — Formulaire de modification de profil

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('profileForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;

        const errorIds = ['nomError', 'prenomError', 'emailError', 'telephoneError'];
        errorIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.textContent = ''; el.style.display = 'none'; }
        });

        const show = (id, msg) => {
            const el = document.getElementById(id);
            if (el) { el.textContent = msg; el.style.display = 'block'; }
            valid = false;
        };

        const nom       = document.getElementById('nom').value.trim();
        const prenom    = document.getElementById('prenom').value.trim();
        const email     = document.getElementById('email').value.trim();
        const telephone = document.getElementById('telephone').value.trim();

        if (!nom || nom.length < 2)
            show('nomError', 'Le nom doit contenir au moins 2 caractères.');
        if (!prenom || prenom.length < 2)
            show('prenomError', 'Le prénom doit contenir au moins 2 caractères.');
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
            show('emailError', 'Email invalide.');
        if (telephone && !/^\+?[\d\s\-]{8,15}$/.test(telephone))
            show('telephoneError', 'Téléphone invalide.');

        if (!valid) e.preventDefault();
    });
});
