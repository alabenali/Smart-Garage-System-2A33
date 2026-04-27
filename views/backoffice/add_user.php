<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Client - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
=======
    <link rel="stylesheet" href="style.css">
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
</head>
<body>
<aside class="sidebar">
    <div class="logo"><h2><i class="fas fa-car" style="color:#00E5FF;"></i> Smart Garage</h2></div>
    <nav><ul>
<<<<<<< HEAD
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser" class="active"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showStatistics"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
            <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
=======
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="users_list.php?action=listUsers"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="add_user.php?action=showAddUser" class="active"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-error"><?php foreach($_SESSION['errors'] as $e): ?><div><i class="fas fa-times-circle"></i> <?= htmlspecialchars($e) ?></div><?php endforeach; unset($_SESSION['errors']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-plus"></i> Ajouter un client</h2>
        <form id="addForm" method="POST" action="../../controllers/AdminController.php?action=addUser" novalidate>
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
                    <input type="text" name="email" id="email" placeholder="client@email.com" value="<?= htmlspecialchars($_SESSION['old']['email'] ?? '') ?>">
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
<<<<<<< HEAD
                <a href="/projet_final/controllers/AdminController.php?action=listUsers" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
=======
                <a href="users_list.php?action=listUsers" class="btn-back"><i class="fas fa-arrow-left"></i> Annuler</a>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
            </div>
        </form>
        <?php unset($_SESSION['old']); ?>
    </div>
</main>
<<<<<<< HEAD
<script src="/projet_final/views/backoffice/js/validate-add-user.js"></script>
=======
<script src="../../public/js/validate-add-user.js"></script>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
</body>
</html>
