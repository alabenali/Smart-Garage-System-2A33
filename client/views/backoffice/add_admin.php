<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Admin - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/integration/client/views/backoffice/style.css">
</head>
<body>
<aside class="sidebar">
    <div class="logo"><h2><i class="fas fa-car" style="color:#00E5FF;"></i> Smart Garage</h2></div>
    <nav><ul>
        <li><a href="/integration/client/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/integration/client/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/integration/client/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/integration/client/controllers/AdminController.php?action=showAddAdmin" class="active"><i class="fas fa-user-shield"></i> Ajouter un admin</a></li>
        <li><a href="/integration/client/controllers/AIController.php?action=showAssistant" style="color:#a78bfa;"><i class="fas fa-robot"></i> AI Helper</a></li>
        <li><a href="/integration/client/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
        <li><a href="/integration/client/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error"><?php foreach($_SESSION['errors'] as $e): ?><div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-shield"></i> Ajouter un administrateur</h2>
        <form id="addAdminForm" method="POST" action="../../controllers/AdminController.php?action=addAdmin" novalidate>
            <div class="row2">
                <div class="form-group">
                    <label>Nom</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i>
                        <input type="text" name="nom" id="nom" placeholder="Ben Ali" value="<?= htmlspecialchars($_SESSION['old']['nom'] ?? '') ?>">
                    </div>
                    <span class="error-msg" id="nomError"></span>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <div class="input-wrap"><i class="fas fa-user icon"></i>
                        <input type="text" name="prenom" id="prenom" placeholder="Ahmed" value="<?= htmlspecialchars($_SESSION['old']['prenom'] ?? '') ?>">
                    </div>
                    <span class="error-msg" id="prenomError"></span>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap"><i class="fas fa-envelope icon"></i>
                    <input type="text" name="email" id="email" placeholder="admin@email.com" value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>">
                </div>
                <span class="error-msg" id="emailError"></span>
            </div>

            <div class="form-group">
                <label>Téléphone</label>
                <div class="input-wrap"><i class="fas fa-phone icon"></i>
                    <input type="text" name="telephone" id="telephone" placeholder="+216 XX XXX XXX" value="<?= htmlspecialchars($_SESSION['old']['telephone'] ?? '') ?>">
                </div>
                <span class="error-msg" id="telephoneError"></span>
            </div>

            <div class="form-group">
                <label>Adresse</label>
                <div class="input-wrap"><i class="fas fa-map-marker-alt icon"></i>
                    <input type="text" name="adresse" placeholder="Tunis, Tunisie" value="<?= htmlspecialchars($_SESSION['old']['adresse'] ?? '') ?>">
                </div>
            </div>

            <div class="row2">
                <div class="form-group">
                    <label>Mot de passe</label>
                    <div class="input-wrap"><i class="fas fa-lock icon"></i>
                        <input type="password" name="mot_de_passe" id="password" placeholder="Min. 6 caractères">
                    </div>
                    <span class="error-msg" id="passwordError"></span>
                </div>
                <div class="form-group">
                    <label>Statut</label>
                    <div class="input-wrap"><i class="fas fa-toggle-on icon"></i>
                        <select name="statut">
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="/integration/client/controllers/AdminController.php?action=showDashboard" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
            </div>
        </form>
        <?php unset($_SESSION['old']); ?>
    </div>
</main>

<script>
(function() {
    const form = document.getElementById('addAdminForm');
    if (!form) return;

    const setErr = (id, msg) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg || '';
        el.style.display = msg ? 'block' : 'none';
    };

    form.addEventListener('submit', function(e) {
        let ok = true;
        const nom = (document.getElementById('nom')?.value || '').trim();
        const prenom = (document.getElementById('prenom')?.value || '').trim();
        const email = (document.getElementById('email')?.value || '').trim();
        const tel = (document.getElementById('telephone')?.value || '').trim();
        const pwd = (document.getElementById('password')?.value || '').trim();

        setErr('nomError', '');
        setErr('prenomError', '');
        setErr('emailError', '');
        setErr('telephoneError', '');
        setErr('passwordError', '');

        if (nom.length < 2) { setErr('nomError', 'Nom invalide (min. 2 caractères).'); ok = false; }
        if (prenom.length < 2) { setErr('prenomError', 'Prénom invalide (min. 2 caractères).'); ok = false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setErr('emailError', 'Email invalide.'); ok = false; }
        if (tel && !/^\+?[0-9\s\-]{8,15}$/.test(tel)) { setErr('telephoneError', 'Téléphone invalide.'); ok = false; }
        if (pwd.length < 6) { setErr('passwordError', 'Mot de passe trop court (min. 6 caractères).'); ok = false; }

        if (!ok) e.preventDefault();
    });
})();
</script>

</body>
</html>
