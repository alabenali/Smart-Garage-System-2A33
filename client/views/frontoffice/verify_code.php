<?php
require_once __DIR__ . '/../../config.php';
if (empty($_SESSION['reset_email'])) {
    header('Location: /integration/client/controllers/UserController.php?action=showForgotPassword');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du code - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php $styleVersion = @filemtime(__DIR__ . '/../../../vehicule et rdv/views/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="/integration/vehicule%20et%20rdv/views/css/style.css?v=<?php echo $styleVersion; ?>">
    <link rel="stylesheet" href="/integration/client/views/frontoffice/auth_sg.css?v=<?php echo @filemtime(__DIR__ . '/auth_sg.css') ?: time(); ?>">
    <style>
        .code-inputs { display:flex; gap:10px; justify-content:center; margin:20px 0; }
        .code-inputs input {
            width:52px; height:62px; text-align:center; font-size:1.8rem; font-weight:700;
                background:var(--bg-card); border:1px solid var(--border-color);
            border-radius:12px; color:var(--accent); outline:none;
            transition:border-color .2s, background .2s, box-shadow .2s;
        }
            .code-inputs input:focus  { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-glow); }
        .code-inputs input.filled { border-color:var(--accent); }
        .hint { color:#6B6B6B; font-size:0.88rem; text-align:center; margin-bottom:1.2rem; line-height:1.5; }
        .hint strong { color:var(--accent); }
    </style>
</head>
<body class="auth-body">
<div class="client-auth-shell">
<div class="auth-card">
    <div class="auth-logo">
        <i class="fas fa-car" style="font-size:2.5rem;color:#00E5FF;"></i>
        <h2>Smart Garage</h2>
        <p>Vérification du code</p>
    </div>

    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error">
            <?php foreach($_SESSION['errors'] as $e): ?>
                <div><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($e) ?></div>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <p class="hint">
        Un code à 6 chiffres a été envoyé à<br>
        <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>
    </p>

    <form method="POST" action="/integration/client/controllers/UserController.php?action=verifyResetCode" id="codeForm">
        <div class="code-inputs">
            <input type="text" maxlength="1" class="code-digit" inputmode="numeric" autocomplete="off">
            <input type="text" maxlength="1" class="code-digit" inputmode="numeric" autocomplete="off">
            <input type="text" maxlength="1" class="code-digit" inputmode="numeric" autocomplete="off">
            <input type="text" maxlength="1" class="code-digit" inputmode="numeric" autocomplete="off">
            <input type="text" maxlength="1" class="code-digit" inputmode="numeric" autocomplete="off">
            <input type="text" maxlength="1" class="code-digit" inputmode="numeric" autocomplete="off">
        </div>
        <input type="hidden" name="reset_code" id="reset_code">
        <button type="submit" class="btn-primary full" id="submitBtn" disabled>
            <i class="fas fa-check-circle"></i> Vérifier le code
        </button>
    </form>

    <div class="links">
        <p>Pas reçu le code ? <a href="/integration/client/controllers/UserController.php?action=resendResetCode">Renvoyer</a></p>
        <p><a href="/integration/client/views/frontoffice/forgot_password.php"><i class="fas fa-arrow-left"></i> Retour</a></p>
    </div>
</div>
<script>
const digits    = document.querySelectorAll('.code-digit');
const hidden    = document.getElementById('reset_code');
const submitBtn = document.getElementById('submitBtn');
function updateHidden() {
    const code = [...digits].map(d => d.value).join('');
    hidden.value = code;
    submitBtn.disabled = code.length !== 6;
    digits.forEach(d => d.classList.toggle('filled', d.value !== ''));
}
digits.forEach((input, i) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/[^0-9]/g, '').slice(-1);
        if (input.value && i < digits.length - 1) digits[i + 1].focus();
        updateHidden();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && i > 0) {
            digits[i - 1].value = ''; digits[i - 1].focus(); updateHidden();
        }
    });
    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...paste].slice(0, 6).forEach((char, j) => { if (digits[j]) digits[j].value = char; });
        updateHidden();
        digits[Math.min(paste.length, 5)].focus();
    });
});
document.getElementById('codeForm').addEventListener('submit', function(e) {
    if (hidden.value.length !== 6) { e.preventDefault(); }
});
digits[0].focus();
</script>
</div>
</body>
</html>