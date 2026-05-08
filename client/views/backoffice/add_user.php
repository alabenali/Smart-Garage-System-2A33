<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$old = $_SESSION['old'] ?? [];
$pageTitle = 'Ajouter Client';
$currentAction = 'addClient';
require __DIR__ . '/layout_header.php';
?>

<div class="detail-page-head">
    <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title">Ajouter un client</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Creation admin compatible avec les relations vehicules et RDV.</p>
    </div>
</div>

<div class="sg-form-wrap">
    <form id="addForm" method="POST" action="/integration/client/controllers/AdminController.php?action=addUser" novalidate>
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($old['nom'] ?? ''); ?>" placeholder="Ben Ali">
                <div class="invalid-feedback" id="nomError"></div>
            </div>
            <div class="sg-form-group">
                <label for="prenom">Prenom</label>
                <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($old['prenom'] ?? ''); ?>" placeholder="Ahmed">
                <div class="invalid-feedback" id="prenomError"></div>
            </div>
            <div class="sg-form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" placeholder="client@email.com">
                <div class="invalid-feedback" id="emailError"></div>
            </div>
            <div class="sg-form-group">
                <label for="telephone">Telephone</label>
                <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars($old['telephone'] ?? ''); ?>" placeholder="+216 XX XXX XXX">
                <div class="invalid-feedback" id="telephoneError"></div>
            </div>
            <div class="sg-form-group full-width">
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" id="adresse" value="<?php echo htmlspecialchars($old['adresse'] ?? ''); ?>" placeholder="Tunis, Tunisie">
            </div>
            <div class="sg-form-group">
                <label for="password">Mot de passe</label>
                <input type="password" name="mot_de_passe" id="password" placeholder="Min. 6 caracteres">
                <div class="invalid-feedback" id="passwordError"></div>
            </div>
            <div class="sg-form-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="actif" <?php echo (($old['statut'] ?? 'actif') === 'actif') ? 'selected' : ''; ?>>Actif</option>
                    <option value="inactif" <?php echo (($old['statut'] ?? '') === 'inactif') ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<?php unset($_SESSION['old']); ?>
<?php
$extraScripts = '<script src="/integration/client/views/backoffice/js/validate-add-user.js"></script>';
require __DIR__ . '/layout_footer.php';
?>
