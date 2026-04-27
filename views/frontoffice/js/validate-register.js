// views/frontoffice/js/validate-register.js
// Contrôle de saisie — Formulaire d'inscription

document.addEventListener('DOMContentLoaded', function () {

    // ── Contrôles durs : blocage en temps réel ──────────────────────────────

    ['nom', 'prenom'].forEach(function (id) {
        const input = document.getElementById(id);
        if (!input) return;

        input.addEventListener('keydown', function (e) {
            // Laisser passer les touches de contrôle (Backspace, Tab, flèches, etc.)
            if (e.key.length > 1) return;
            if (/[0-9]/.test(e.key)) {
                e.preventDefault();
                return;
            }
            if (!/[a-zA-ZÀ-ÖØ-öø-ÿ \-']/.test(e.key)) {
                e.preventDefault();
            }
        });

        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-ZÀ-ÖØ-öø-ÿ \-']/g, '');
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const clean  = pasted.replace(/[^a-zA-ZÀ-ÖØ-öø-ÿ \-']/g, '');
            document.execCommand('insertText', false, clean);
        });
    });

    const telInput = document.getElementById('telephone');
    if (telInput) {
        telInput.addEventListener('keydown', function (e) {
            if (e.key.length > 1) return;
            if (!/[\d+\s\-]/.test(e.key)) e.preventDefault();
        });

        telInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^\d+\s\-]/g, '');
        });

        telInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const clean  = pasted.replace(/[^\d+\s\-]/g, '');
            document.execCommand('insertText', false, clean);
        });
    }

    // ── Validation à la soumission ──────────────────────────────────────────

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
            show('nomError', 'Le nom doit contenir au moins 2 caractères (lettres uniquement).');
        if (!prenom || prenom.length < 2)
            show('prenomError', 'Le prénom doit contenir au moins 2 caractères (lettres uniquement).');
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
