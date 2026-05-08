<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$pageTitle = 'Dashboard Admin';
$currentAction = 'dashboard';
require __DIR__ . '/layout_header.php';
?>

<div class="client-topline">
    <div>
        <h1 class="page-title">Dashboard administration</h1>
        <p class="page-subtitle">Pilotage Client, Vehicule et Rendez-vous depuis un espace unifie.</p>
    </div>
    <span class="client-admin-chip"><i class="bi bi-person-shield"></i><?php echo htmlspecialchars($_SESSION['admin_nom'] ?? 'Admin'); ?></span>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people-fill"></i></div><div class="stat-value"><?php echo (int) ($totalUsers ?? 0); ?></div><div class="stat-label">Clients</div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-person-check"></i></div><div class="stat-value"><?php echo (int) ($activeUsers ?? 0); ?></div><div class="stat-label">Clients actifs</div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="bi bi-car-front-fill"></i></div><div class="stat-value"><?php echo (int) ($totalVehicles ?? 0); ?></div><div class="stat-label">Vehicules</div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-calendar-check"></i></div><div class="stat-value"><?php echo (int) ($totalRdv ?? 0); ?></div><div class="stat-label">Rendez-vous</div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-car-front"></i></div><div class="stat-value"><?php echo number_format((float) ($avgVehiclesPerClient ?? 0), 1, ',', ' '); ?></div><div class="stat-label">Vehicules / client</div></div>
    <div class="stat-card"><div class="stat-icon teal"><i class="bi bi-card-checklist"></i></div><div class="stat-value"><?php echo number_format((float) ($avgRdvPerClient ?? 0), 1, ',', ' '); ?></div><div class="stat-label">RDV / client</div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-activity"></i></div><div class="stat-value"><?php echo number_format((float) ($avgUrgence ?? 0), 1, ',', ' '); ?>/10</div><div class="stat-label">Urgence moyenne</div></div>
    <div class="stat-card"><div class="stat-icon cyan"><i class="bi bi-person-plus"></i></div><div class="stat-value"><?php echo (int) ($newThisMonth ?? 0); ?></div><div class="stat-label">Nouveaux ce mois</div></div>
</div>

<div class="client-mini-grid">
    <a href="/integration/client/controllers/AdminController.php?action=listUsers" class="btn-sg btn-sg-outline"><i class="bi bi-people"></i> Gerer clients</a>
    <a href="/integration/vehicule%20et%20rdv/index.php?action=manageVehicles" class="btn-sg btn-sg-outline"><i class="bi bi-car-front"></i> Gerer vehicules</a>
    <a href="/integration/vehicule%20et%20rdv/index.php?action=backCalendar" class="btn-sg btn-sg-outline"><i class="bi bi-calendar-plus"></i> Creer RDV</a>
    <a href="/integration/vehicule%20et%20rdv/index.php?action=backRdvList" class="btn-sg btn-sg-outline"><i class="bi bi-file-earmark-pdf"></i> Exports RDV</a>
</div>

<div style="display:grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap:1.25rem; margin-top:1.5rem;">
    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-trophy me-2"></i>Clients les plus actifs</h3>
        </div>
        <table class="sg-table">
            <thead><tr><th>Client</th><th>Email</th><th>RDV</th><th></th></tr></thead>
            <tbody>
                <?php foreach (($topActiveClients ?? []) as $client): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($client['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                        <td><span class="client-admin-chip"><i class="bi bi-calendar-check"></i><?php echo (int) $client['rdv_total']; ?></span></td>
                        <td><a class="btn-sg btn-sg-outline btn-sg-sm" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $client['id']; ?>"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($topActiveClients)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucune activite RDV.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="sg-table-wrap">
        <div class="table-header">
            <h3><i class="bi bi-exclamation-triangle me-2"></i>Vehicules les plus problematiques</h3>
        </div>
        <table class="sg-table">
            <thead><tr><th>Vehicule</th><th>Immat.</th><th>RDV</th><th>Urgence</th></tr></thead>
            <tbody>
                <?php foreach (($problematicVehicles ?? []) as $vehicle): ?>
                    <?php $avg = round((float) ($vehicle['avg_urgence'] ?? 0), 1); ?>
                    <tr>
                        <td><a class="client-link" href="/integration/vehicule%20et%20rdv/index.php?action=vehicleDetail&id=<?php echo (int) $vehicle['id']; ?>"><?php echo htmlspecialchars(trim(($vehicle['marque'] ?? '') . ' ' . ($vehicle['modele'] ?? ''))); ?></a></td>
                        <td><?php echo htmlspecialchars($vehicle['immatriculation'] ?? '-'); ?></td>
                        <td><?php echo (int) ($vehicle['rdv_total'] ?? 0); ?></td>
                        <td><span class="urgence-badge <?php echo $avg >= 7 ? 'urgence-high' : ($avg >= 4 ? 'urgence-medium' : 'urgence-low'); ?>"><?php echo number_format($avg, 1, ',', ' '); ?>/10</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($problematicVehicles)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:2rem;">Aucun historique vehicule.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
