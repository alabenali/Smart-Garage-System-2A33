<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}
if (!isset($user)) {
    header('Location: /integration/client/controllers/AdminController.php?action=listUsers');
    exit;
}

$pageTitle = 'Modifier Client';
$currentAction = 'editClient';
$fullName = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
$pic = $user['profile_picture'] ?? null;
$picUrl = null;
if ($pic && file_exists(__DIR__ . '/../../' . $pic)) {
    $picUrl = '/integration/client/' . $pic;
}
require __DIR__ . '/layout_header.php';
?>

<div class="detail-page-head">
    <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title">Modifier le client</h1>
        <p class="page-subtitle" style="margin-bottom:0;"><?php echo htmlspecialchars($fullName); ?> - #<?php echo (int) $user['id']; ?></p>
    </div>
    <div class="detail-head-actions">
        <a class="btn-sg btn-sg-outline" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $user['id']; ?>"><i class="bi bi-eye"></i> Details</a>
    </div>
</div>

<div class="sg-form-wrap">
    <form id="editForm" method="POST" action="/integration/client/controllers/AdminController.php?action=editUser" novalidate enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;">
            <?php if ($picUrl): ?>
                <img src="<?php echo htmlspecialchars($picUrl); ?>" id="previewImgAdmin" alt="Photo" style="width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid var(--border-color);">
            <?php else: ?>
                <div id="previewImgAdmin" style="width:72px;height:72px;border-radius:12px;background:var(--info-bg);display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--accent-secondary);font-weight:800;">
                    <?php echo htmlspecialchars(strtoupper(substr((string) ($user['prenom'] ?? 'C'), 0, 1))); ?>
                </div>
            <?php endif; ?>
            <div>
                <strong><?php echo htmlspecialchars($fullName); ?></strong>
                <div style="color:var(--text-muted);font-size:0.85rem;"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
            </div>
        </div>
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>">
                <div class="invalid-feedback" id="nomError"></div>
            </div>
            <div class="sg-form-group">
                <label for="prenom">Prenom</label>
                <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>">
                <div class="invalid-feedback" id="prenomError"></div>
            </div>
            <div class="sg-form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                <div class="invalid-feedback" id="emailError"></div>
            </div>
            <div class="sg-form-group">
                <label for="telephone">Telephone</label>
                <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                <div class="invalid-feedback" id="telephoneError"></div>
            </div>
            <div class="sg-form-group full-width">
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" id="adresse" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
            </div>
            <div class="sg-form-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="actif" <?php echo ($user['statut'] ?? '') === 'actif' ? 'selected' : ''; ?>>Actif</option>
                    <option value="inactif" <?php echo ($user['statut'] ?? '') === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>
            <div class="sg-form-group">
                <label for="profile_picture_edit">Photo de profil</label>
                <input type="file" name="profile_picture" id="profile_picture_edit" accept="image/jpeg,image/png,image/gif,image/webp">
                <span style="color:var(--text-muted);font-size:0.78rem;">JPG, PNG, GIF, WebP - max 2 Mo</span>
            </div>
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline">Annuler</a>
                <a href="/integration/client/controllers/AdminController.php?action=deleteUser&id=<?php echo (int) $user['id']; ?>" onclick="return confirm('Supprimer ce client ?')" class="btn-sg btn-sg-danger" style="margin-left:auto;"><i class="bi bi-trash3"></i> Supprimer</a>
            </div>
        </div>
    </form>
</div>

<?php
$extraScripts = <<<'HTML'
<script src="/integration/client/views/backoffice/js/validate-edit-user.js"></script>
<script>
document.getElementById('profile_picture_edit').addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    const file = this.files[0];
    if (file.size > 2 * 1024 * 1024) {
        alert('Max 2 Mo.');
        this.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function (event) {
        const current = document.getElementById('previewImgAdmin');
        const img = document.createElement('img');
        img.id = 'previewImgAdmin';
        img.src = event.target.result;
        img.alt = 'Photo';
        img.style = 'width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid var(--border-color);';
        current.parentNode.replaceChild(img, current);
    };
    reader.readAsDataURL(file);
});
</script>
HTML;
require __DIR__ . '/layout_footer.php';
?>
