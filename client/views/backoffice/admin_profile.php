<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}
if (!isset($admin)) {
    header('Location: /integration/client/controllers/AdminController.php?action=showDashboard');
    exit;
}

$pageTitle = 'Mon Profil';
$currentAction = 'profile';
$fullName = trim((string) ($admin['prenom'] ?? '') . ' ' . (string) ($admin['nom'] ?? ''));
$initial = strtoupper(substr((string) ($admin['prenom'] ?? $admin['nom'] ?? 'A'), 0, 1));
$pic = $admin['profile_picture'] ?? null;
$picUrl = null;
if ($pic && file_exists(__DIR__ . '/../../' . $pic)) {
    $picUrl = '/integration/client/' . $pic;
}

require __DIR__ . '/layout_header.php';
?>

<div class="detail-page-head">
    <a href="/integration/client/controllers/AdminController.php?action=showDashboard" class="btn-sg btn-sg-outline btn-sg-sm">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
    <div>
        <h1 class="page-title">Mon profil</h1>
        <p class="page-subtitle" style="margin-bottom:0;">Parametres du compte administrateur Smart Garage.</p>
    </div>
</div>

<div class="vehicle-detail-layout">
    <section class="vehicle-profile-panel">
        <div class="vehicle-profile-title">
            <?php if ($picUrl): ?>
                <img
                    src="<?php echo htmlspecialchars($picUrl); ?>"
                    id="adminPreviewImg"
                    alt="Photo de profil"
                    style="width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid var(--border-color);"
                >
            <?php else: ?>
                <span
                    id="adminPreviewImg"
                    class="vehicle-profile-icon"
                    style="width:72px;height:72px;font-size:1.9rem;font-weight:800;"
                ><?php echo htmlspecialchars($initial); ?></span>
            <?php endif; ?>
            <div>
                <h2><?php echo htmlspecialchars($fullName !== '' ? $fullName : 'Administrateur'); ?></h2>
                <span class="client-admin-chip"><i class="bi bi-shield-check"></i> Admin</span>
            </div>
        </div>

        <div class="vehicle-fiche-grid">
            <div class="vehicle-fiche-item">
                <span>Email</span>
                <strong><?php echo htmlspecialchars($admin['email'] ?? '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Telephone</span>
                <strong><?php echo htmlspecialchars($admin['telephone'] ?? '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Adresse</span>
                <strong><?php echo htmlspecialchars($admin['adresse'] ?? '-'); ?></strong>
            </div>
            <div class="vehicle-fiche-item">
                <span>Statut</span>
                <strong><span class="status-badge status-termine">Actif</span></strong>
            </div>
        </div>
    </section>

    <section class="vehicle-stats-panel">
        <h2>Securite</h2>
        <div class="vehicle-stats-grid">
            <div class="vehicle-stat-box">
                <span>Role</span>
                <strong>Admin</strong>
            </div>
            <div class="vehicle-stat-box">
                <span>Session</span>
                <strong>Active</strong>
            </div>
        </div>
        <div class="vehicle-last-intervention">
            <span>Compte</span>
            <strong>#<?php echo (int) ($admin['id'] ?? 0); ?></strong>
        </div>
    </section>
</div>

<div class="sg-form-wrap" style="margin-top:1rem;">
    <form
        id="editForm"
        method="POST"
        action="/integration/client/controllers/AdminController.php?action=updateAdminProfile"
        novalidate
        enctype="multipart/form-data"
    >
        <div class="sg-form-grid">
            <div class="sg-form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($admin['nom'] ?? ''); ?>">
                <div class="invalid-feedback" id="nomError"></div>
            </div>
            <div class="sg-form-group">
                <label for="prenom">Prenom</label>
                <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($admin['prenom'] ?? ''); ?>">
                <div class="invalid-feedback" id="prenomError"></div>
            </div>
            <div class="sg-form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                <div class="invalid-feedback" id="emailError"></div>
            </div>
            <div class="sg-form-group">
                <label for="telephone">Telephone</label>
                <input type="text" name="telephone" id="telephone" value="<?php echo htmlspecialchars($admin['telephone'] ?? ''); ?>">
                <div class="invalid-feedback" id="telephoneError"></div>
            </div>
            <div class="sg-form-group full-width">
                <label for="adresse">Adresse</label>
                <input type="text" name="adresse" id="adresse" value="<?php echo htmlspecialchars($admin['adresse'] ?? ''); ?>">
            </div>
            <div class="sg-form-group full-width">
                <label for="adminProfilePic">Photo de profil</label>
                <input type="file" name="profile_picture" id="adminProfilePic" accept="image/jpeg,image/png,image/gif,image/webp">
                <span id="fileLabel" style="color:var(--text-muted);font-size:0.78rem;">JPG, PNG, GIF, WebP - max 2 Mo</span>
            </div>
            <div class="sg-form-actions">
                <button type="submit" class="btn-sg btn-sg-primary">
                    <i class="bi bi-check-lg"></i> Enregistrer
                </button>
                <a href="/integration/client/controllers/AdminController.php?action=showDashboard" class="btn-sg btn-sg-outline">Annuler</a>
            </div>
        </div>
    </form>
</div>

<?php
$extraScripts = <<<'HTML'
<script src="/integration/client/views/backoffice/js/validate-edit-user.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('adminProfilePic');
    const label = document.getElementById('fileLabel');
    if (!input) {
        return;
    }

    input.addEventListener('change', function () {
        if (!this.files || !this.files[0]) {
            return;
        }

        const file = this.files[0];
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Format non autorise. Utilisez JPG, PNG, GIF ou WebP.');
            this.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('La taille ne doit pas depasser 2 Mo.');
            this.value = '';
            return;
        }

        if (label) {
            label.textContent = file.name;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            const current = document.getElementById('adminPreviewImg');
            if (!current) {
                return;
            }

            const img = document.createElement('img');
            img.id = 'adminPreviewImg';
            img.src = event.target.result;
            img.alt = 'Photo de profil';
            img.style = 'width:72px;height:72px;border-radius:12px;object-fit:cover;border:1px solid var(--border-color);';
            current.parentNode.replaceChild(img, current);
        };
        reader.readAsDataURL(file);
    });
});
</script>
HTML;
require __DIR__ . '/layout_footer.php';
?>
