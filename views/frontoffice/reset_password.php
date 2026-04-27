<?php
require_once __DIR__ . '/../../config.php';
if (empty($_SESSION['reset_verified'])) {
    header('Location: /projet_final/controllers/UserController.php?action=showForgotPassword');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
    <style>
        .strength-bar { height:4px; border-radius:2px; margin-top:6px; background:#1a1a2e; transition:all .3s; }
        .strength-text { font-size:0.78rem; margin-top:4px; color:#6B6B6B; }
        .eye-toggle {
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            cursor:pointer; color:#6B6B6B; transition:color .2s;
        }
        .eye-toggle:hover { color:#00E5FF; }
    </style>
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="auth-logo">
        <i class="fas fa-car" style="font-size:2.5rem;color:#00E5FF;"></i>
        <h2>Smart Garage</h2>
        <p>Nouveau mot de passe</p>
    </div>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error">
            <?php foreach($_SESSION['errors'] as $e): ?>
                <div><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <form id="resetForm" method="POST" action="/projet_final/controllers/UserController.php?action=resetPassword" novalidate>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Nouveau mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="mot_de_passe" id="password" placeholder="Min. 6 caractères" required autocomplete="new-password">
                <span class="eye-toggle" onclick="togglePwd('password',this)"><i class="fas fa-eye"></i></span>
            </div>
            <div class="strength-bar" id="strengthBar" style="width:0%"></div>
            <div class="strength-text" id="strengthText"></div>
            <span class="error-msg" id="passwordError"></span>
        </div>
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Confirmer le mot de passe</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••" required autocomplete="new-password">
                <span class="eye-toggle" onclick="togglePwd('confirmPassword',this)"><i class="fas fa-eye"></i></span>
            </div>
            <span class="error-msg" id="confirmError"></span>
        </div>
        <button type="submit" class="btn-primary full">
            <i class="fas fa-save"></i> Enregistrer le mot de passe
        </button>
    </form>
</div>
<script>
function togglePwd(id, el) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    el.innerHTML = isHidden ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
}
document.getElementById('password').addEventListener('input', function() {
    const v = this.value, bar = document.getElementById('strengthBar'), txt = document.getElementById('strengthText');
    let s = 0;
    if (v.length >= 6) s++; if (v.length >= 10) s++;
    if (/[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
    const lvls = [
        {c:'#e74c3c',l:'Très faible'},{c:'#e67e22',l:'Faible'},
        {c:'#f1c40f',l:'Moyen'},{c:'#2ecc71',l:'Fort'},{c:'#00E5FF',l:'Très fort'}
    ];
    const lvl = lvls[Math.min(s, 4)];
    bar.style.background = lvl.c; bar.style.width = (s * 20) + '%';
    txt.textContent = v.length ? lvl.l : ''; txt.style.color = lvl.c;
});
document.getElementById('resetForm').addEventListener('submit', function(e) {
    let ok = true;
    const pwd = document.getElementById('password'), conf = document.getElementById('confirmPassword');
    document.getElementById('passwordError').textContent = '';
    document.getElementById('confirmError').textContent  = '';
    if (pwd.value.length < 6) { document.getElementById('passwordError').textContent = 'Au moins 6 caractères.'; document.getElementById('passwordError').style.display='block'; ok=false; }
    if (pwd.value !== conf.value) { document.getElementById('confirmError').textContent = 'Les mots de passe ne correspondent pas.'; document.getElementById('confirmError').style.display='block'; ok=false; }
    if (!ok) e.preventDefault();
});
</script>
</body>
</html>