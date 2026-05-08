<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: /integration/client/controllers/UserController.php?action=showLogin'); exit; }

$db   = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM user WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

$prenom     = htmlspecialchars($_SESSION['user_prenom']);
$nom        = htmlspecialchars($_SESSION['user_nom']);
$profilePic = $_SESSION['user_profile_pic'] ?? $user['profile_picture'] ?? null;
$avatarPath = null;
if ($profilePic) {
    $sp = __DIR__ . '/../../' . $profilePic;
    if (file_exists($sp)) $avatarPath = '/integration/client/' . $profilePic;
}
?>

<?php $pageTitle = 'Mon espace'; $action = 'clientDashboard'; ?>
<?php require __DIR__ . '/layout_header.php'; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="sg-alert sg-alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success']); ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<h1 class="page-title">Bonjour, <?php echo htmlspecialchars($prenom); ?> <span style="color:var(--accent);"><?php echo htmlspecialchars($nom); ?></span></h1>
<p class="page-subtitle">Bienvenue sur votre espace Smart Garage — Gestion intelligente de vos véhicules</p>

<div class="vehicle-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
    <a href="/integration/vehicule%20et%20rdv/index.php?action=addVehicle&next=client" class="vehicle-card" style="text-decoration:none;color:inherit;">
        <div class="vc-header" style="margin-bottom:0.5rem;">
            <div>
                <div class="vc-brand"><i class="bi bi-plus-circle me-1"></i> Ajouter une voiture</div>
                <div class="vc-model">Enregistrer votre véhicule dans Smart Garage.</div>
            </div>
        </div>
        <div class="btn-group-actions" style="margin-top:1rem;">
            <span class="btn-sg btn-sg-primary"><i class="bi bi-arrow-right"></i> Ouvrir</span>
        </div>
    </a>

    <a href="/integration/vehicule%20et%20rdv/index.php?action=frontCalendar" class="vehicle-card" style="text-decoration:none;color:inherit;">
        <div class="vc-header" style="margin-bottom:0.5rem;">
            <div>
                <div class="vc-brand"><i class="bi bi-calendar-check me-1"></i> Prendre un RDV</div>
                <div class="vc-model">Choisir votre véhicule, déclarer la panne et réserver.</div>
            </div>
        </div>
        <div class="btn-group-actions" style="margin-top:1rem;">
            <span class="btn-sg btn-sg-primary"><i class="bi bi-arrow-right"></i> Ouvrir</span>
        </div>
    </a>

    <a href="/integration/vehicule%20et%20rdv/index.php?action=showVehicles" class="vehicle-card" style="text-decoration:none;color:inherit;">
        <div class="vc-header" style="margin-bottom:0.5rem;">
            <div>
                <div class="vc-brand"><i class="bi bi-car-front me-1"></i> Mes véhicules</div>
                <div class="vc-model">Voir les voitures rattachées à votre compte.</div>
            </div>
        </div>
        <div class="btn-group-actions" style="margin-top:1rem;">
            <span class="btn-sg btn-sg-primary"><i class="bi bi-arrow-right"></i> Ouvrir</span>
        </div>
    </a>

    <a href="/integration/client/controllers/UserController.php?action=showMyRendezvous" class="vehicle-card" style="text-decoration:none;color:inherit;">
        <div class="vc-header" style="margin-bottom:0.5rem;">
            <div>
                <div class="vc-brand"><i class="bi bi-calendar2-week me-1"></i> Mes rendez-vous</div>
                <div class="vc-model">Consulter vos RDV passes, futurs et vos points fidelite.</div>
            </div>
        </div>
        <div class="btn-group-actions" style="margin-top:1rem;">
            <span class="btn-sg btn-sg-primary"><i class="bi bi-arrow-right"></i> Ouvrir</span>
        </div>
    </a>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
