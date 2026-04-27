// public/js/validate-register.js
// Contrôle de saisie — Formulaire d'inscription

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;

        const errorIds = ['nomError', 'prenomError', 'emailError', 'telephoneError', 'passwordError', 'confirmError'];
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
        const password  = document.getElementById('password').value;
        const confirm   = document.getElementById('confirmPassword').value;

        if (!nom || nom.length < 2)
            show('nomError', 'Le nom doit contenir au moins 2 caractères.');
        if (!prenom || prenom.length < 2)
            show('prenomError', 'Le prénom doit contenir au moins 2 caractères.');
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
            show('emailError', 'Email invalide (ex: nom@domaine.com).');
        if (telephone && !/^\+?[\d\s\-]{8,15}$/.test(telephone))
            show('telephoneError', 'Téléphone invalide (8-15 chiffres).');
        if (!password || password.length < 6)
            show('passwordError', 'Minimum 6 caractères.');
        if (password !== confirm)
            show('confirmError', 'Les mots de passe ne correspondent pas.');

        if (!valid) e.preventDefault();
    });
});
