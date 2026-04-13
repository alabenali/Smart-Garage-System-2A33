// public/js/validate-login.js
// Contrôle de saisie — Formulaire de connexion (client et admin)

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm') || document.getElementById('adminLoginForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;

        const emailInput    = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError    = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');

        // Reset
        emailError.textContent    = '';
        passwordError.textContent = '';
        emailError.style.display    = 'none';
        passwordError.style.display = 'none';

        // Email
        const email = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email) {
            emailError.textContent  = "L'email est obligatoire.";
            emailError.style.display = 'block';
            valid = false;
        } else if (!emailRegex.test(email)) {
            emailError.textContent  = "Format d'email invalide (ex: nom@domaine.com).";
            emailError.style.display = 'block';
            valid = false;
        }

        // Mot de passe
        const password = passwordInput.value;
        if (!password) {
            passwordError.textContent  = "Le mot de passe est obligatoire.";
            passwordError.style.display = 'block';
            valid = false;
        } else if (password.length < 6) {
            passwordError.textContent  = "Minimum 6 caractères.";
            passwordError.style.display = 'block';
            valid = false;
        }

        if (!valid) e.preventDefault();
    });
});
