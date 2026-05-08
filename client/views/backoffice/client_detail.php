<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$pageTitle = 'Details Client';
$currentAction = 'clientDetail';
$fullName = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
require __DIR__ . '/layout_header.php';
?>

<div class="detail-page-head">
    <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline btn-sg-sm"><i class="bi bi-arrow-left"></i> Retour</a>
    <div>
        <h1 class="page-title">Fiche client</h1>
        <p class="page-subtitle" style="margin-bottom:0;"><?php echo htmlspecialchars($fullName); ?> - #<?php echo (int) $user['id']; ?></p>
    </div>
    <div class="detail-head-actions">
        <a class="btn-sg btn-sg-success" href="/integration/client/controllers/AdminController.php?action=showEditUser&id=<?php echo (int) $user['id']; ?>"><i class="bi bi-pencil-square"></i> Modifier</a>
        <a class="btn-sg btn-sg-primary" href="/integration/vehicule%20et%20rdv/index.php?action=backCalendar&id_client=<?php echo (int) $user['id']; ?>"><i class="bi bi-calendar-plus"></i> Nouveau RDV</a>
    </div>
</div>

<div class="vehicle-detail-layout">
    <section class="vehicle-profile-panel">
        <div class="vehicle-profile-title">
            <span class="vehicle-profile-icon"><i class="bi bi-person"></i></span>
            <div>
                <h2><?php echo htmlspecialchars($fullName !== '' ? $fullName : 'Client'); ?></h2>
                <span class="status-badge <?php echo ($user['statut'] ?? '') === 'actif' ? 'status-termine' : 'status-annule'; ?>"><?php echo htmlspecialchars(ucfirst((string) ($user['statut'] ?? '-'))); ?></span>
            </div>
        </div>
        <div class="vehicle-fiche-grid">
            <div class="vehicle-fiche-item"><span>Email</span><strong><?php echo htmlspecialchars($user['email'] ?? '-'); ?></strong></div>
            <div class="vehicle-fiche-item"><span>Telephone</span><strong><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></strong></div>
            <div class="vehicle-fiche-item"><span>Adresse</span><strong><?php echo htmlspecialchars($user['adresse'] ?? '-'); ?></strong></div>
            <div class="vehicle-fiche-item"><span>Inscription</span><strong><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string) $user['created_at'])) : '-'; ?></strong></div>
        </div>
    </section>
    <section class="vehicle-stats-panel">
        <h2>Relations</h2>
        <div class="vehicle-stats-grid">
            <div class="vehicle-stat-box"><span>Vehicules</span><strong><?php echo count($vehicles); ?></strong></div>
            <div class="vehicle-stat-box"><span>Rendez-vous</span><strong><?php echo count($rdvs); ?></strong></div>
            <div class="vehicle-stat-box"><span>Urgence moyenne</span><strong><?php echo number_format((float) $avgUrgence, 1, ',', ' '); ?>/10</strong></div>
            <div class="vehicle-stat-box"><span>Actifs</span><strong><?php echo count(array_filter($rdvs, static fn($r) => in_array($r['statut'] ?? '', ['En attente', 'Confirme', 'Confirmé', 'En cours'], true))); ?></strong></div>
        </div>
    </section>
</div>

<div class="sg-table-wrap" style="margin-top:1rem;">
    <div class="table-header">
        <h3><i class="bi bi-car-front me-2"></i>Vehicules du client</h3>
        <a class="btn-sg btn-sg-outline btn-sg-sm" href="/integration/vehicule%20et%20rdv/index.php?action=addVehicleBack&id_client=<?php echo (int) $user['id']; ?>"><i class="bi bi-plus-lg"></i> Ajouter vehicule</a>
    </div>
    <table class="sg-table">
        <thead><tr><th>Vehicule</th><th>Immatriculation</th><th>Annee</th><th>Kilometrage</th><th>Carburant</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars(($vehicle['marque'] ?? '') . ' ' . ($vehicle['modele'] ?? '')); ?></strong></td>
                    <td><?php echo htmlspecialchars($vehicle['immatriculation'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($vehicle['annee'] ?? '-')); ?></td>
                    <td><?php echo number_format((int) ($vehicle['kilometrage'] ?? 0), 0, ',', ' '); ?> km</td>
                    <td><span class="badge-fuel <?php echo strtolower((string) ($vehicle['carburant'] ?? '')); ?>"><?php echo htmlspecialchars($vehicle['carburant'] ?? '-'); ?></span></td>
                    <td><a class="btn-sg btn-sg-outline btn-sg-sm" href="/integration/vehicule%20et%20rdv/index.php?action=vehicleDetail&id=<?php echo (int) $vehicle['id']; ?>"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($vehicles)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun vehicule rattache.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="sg-table-wrap" style="margin-top:1rem;">
    <div class="table-header">
        <h3><i class="bi bi-calendar-check me-2"></i>Rendez-vous du client</h3>
        <a class="btn-sg btn-sg-outline btn-sg-sm" href="/integration/vehicule%20et%20rdv/index.php?action=backRdvList&client_id=<?php echo (int) $user['id']; ?>"><i class="bi bi-list-ul"></i> Voir liste RDV</a>
    </div>
    <table class="sg-table">
        <thead><tr><th>Date</th><th>Vehicule</th><th>Intervention</th><th>Urgence</th><th>Statut</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($rdvs as $rdv): ?>
                <?php $score = (int) ($rdv['urgence_score'] ?? 0); ?>
                <tr>
                    <td><?php echo !empty($rdv['date_heure']) ? date('d/m/Y H:i', strtotime((string) $rdv['date_heure'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars(trim(($rdv['marque'] ?? '') . ' ' . ($rdv['modele'] ?? '') . ' ' . ($rdv['immatriculation'] ?? '')) ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($rdv['type_intervention'] ?? '-'); ?></td>
                    <td><span class="urgence-badge <?php echo $score >= 7 ? 'urgence-high' : ($score >= 4 ? 'urgence-medium' : 'urgence-low'); ?>"><?php echo $score; ?>/10</span></td>
                    <td><span class="status-badge"><?php echo htmlspecialchars($rdv['statut'] ?? '-'); ?></span></td>
                    <td><a class="btn-sg btn-sg-success btn-sg-sm" href="/integration/vehicule%20et%20rdv/index.php?action=backEditRdv&id=<?php echo (int) $rdv['id_rdv']; ?>"><i class="bi bi-pencil-square"></i></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rdvs)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun rendez-vous lie.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
