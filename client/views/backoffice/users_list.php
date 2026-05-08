<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$users = $users ?? [];
$total = count($users);
$active = count(array_filter($users, static fn($u) => ($u['statut'] ?? '') === 'actif'));
$inactive = max(0, $total - $active);
$vehicleTotal = array_sum(array_map(static fn($u) => (int) ($u['vehicles_count'] ?? 0), $users));
$rdvTotal = array_sum(array_map(static fn($u) => (int) ($u['rdv_count'] ?? 0), $users));
$pageTitle = 'Gestion Clients';
$currentAction = 'clients';
require __DIR__ . '/layout_header.php';
?>

<div class="client-topline">
    <div>
        <h1 class="page-title">Gestion des clients</h1>
        <p class="page-subtitle">Vue admin reliee aux vehicules, rendez-vous et scores d'urgence.</p>
    </div>
    <div class="btn-group-actions">
        <a href="/integration/client/controllers/AdminController.php?action=showAddUser" class="btn-sg btn-sg-primary">
            <i class="bi bi-person-plus"></i> Ajouter
        </a>
        <button type="button" class="btn-sg btn-sg-outline" onclick="exportClientsCsv()">
            <i class="bi bi-download"></i> Export CSV
        </button>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people-fill"></i></div><div class="stat-value"><?php echo $total; ?></div><div class="stat-label">Clients total</div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-person-check"></i></div><div class="stat-value"><?php echo $active; ?></div><div class="stat-label">Actifs</div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="bi bi-car-front"></i></div><div class="stat-value"><?php echo $vehicleTotal; ?></div><div class="stat-label">Vehicules rattaches</div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-calendar-check"></i></div><div class="stat-value"><?php echo $rdvTotal; ?></div><div class="stat-label">Rendez-vous lies</div></div>
</div>

<div class="sg-form-wrap" style="margin-bottom:1.25rem;">
    <div class="sg-form-grid" style="grid-template-columns:minmax(0,1fr) 180px 180px auto;">
        <div class="sg-form-group">
            <label for="clientSearch">Recherche</label>
            <input id="clientSearch" type="search" placeholder="Nom, email, telephone, adresse..." oninput="filterClients()">
        </div>
        <div class="sg-form-group">
            <label for="statusFilter">Statut</label>
            <select id="statusFilter" onchange="filterClients()">
                <option value="">Tous</option>
                <option value="actif">Actifs</option>
                <option value="inactif">Inactifs</option>
            </select>
        </div>
        <div class="sg-form-group">
            <label for="relationFilter">Relation</label>
            <select id="relationFilter" onchange="filterClients()">
                <option value="">Tous</option>
                <option value="vehicles">Avec vehicule</option>
                <option value="rdv">Avec RDV</option>
                <option value="orphan">Sans relation</option>
            </select>
        </div>
        <div class="sg-form-actions" style="align-self:end;margin:0;">
            <button type="button" class="btn-sg btn-sg-outline" onclick="resetClientFilters()"><i class="bi bi-arrow-counterclockwise"></i></button>
        </div>
    </div>
</div>

<div class="sg-table-wrap">
    <div class="table-header">
        <h3><i class="bi bi-list-ul me-2"></i>Liste clients</h3>
        <span style="color:var(--text-muted);font-size:0.85rem;"><span id="clientCount"><?php echo $total; ?></span> resultat(s)</span>
    </div>
    <table class="sg-table" id="clientsTable">
        <thead>
            <tr>
                <th>Client</th>
                <th>Contact</th>
                <th>Statut</th>
                <th>Vehicules</th>
                <th>RDV</th>
                <th>Urgence moy.</th>
                <th>Inscription</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="8"><div class="empty-state client-empty"><div class="empty-icon"><i class="bi bi-person-x"></i></div><h3>Aucun client</h3><p>Aucun client n'est encore enregistre.</p></div></td></tr>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <?php
                $status = (string) ($user['statut'] ?? 'inactif');
                $statusClass = $status === 'actif' ? 'status-termine' : 'status-annule';
                $fullName = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
                $vehiclesCount = (int) ($user['vehicles_count'] ?? 0);
                $rdvCount = (int) ($user['rdv_count'] ?? 0);
                $avgUrgence = (float) ($user['avg_urgence'] ?? 0);
                ?>
                <tr data-client-row data-status="<?php echo htmlspecialchars($status); ?>" data-vehicles="<?php echo $vehiclesCount; ?>" data-rdv="<?php echo $rdvCount; ?>">
                    <td>
                        <a class="client-link" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $user['id']; ?>">
                            <?php echo htmlspecialchars($fullName !== '' ? $fullName : 'Client #' . (int) $user['id']); ?>
                        </a>
                        <div style="color:var(--text-muted);font-size:0.78rem;">#<?php echo (int) $user['id']; ?></div>
                    </td>
                    <td>
                        <div><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
                        <div style="color:var(--text-muted);font-size:0.78rem;"><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></div>
                    </td>
                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span></td>
                    <td><span class="client-admin-chip"><i class="bi bi-car-front"></i><?php echo $vehiclesCount; ?></span></td>
                    <td><span class="client-admin-chip"><i class="bi bi-calendar-check"></i><?php echo $rdvCount; ?></span></td>
                    <td><span class="urgence-badge <?php echo $avgUrgence >= 7 ? 'urgence-high' : ($avgUrgence >= 4 ? 'urgence-medium' : 'urgence-low'); ?>"><?php echo number_format($avgUrgence, 1, ',', ' '); ?>/10</span></td>
                    <td><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string) $user['created_at'])) : '-'; ?></td>
                    <td>
                        <div class="btn-group-actions">
                            <a class="btn-sg btn-sg-outline btn-sg-sm" href="/integration/client/controllers/AdminController.php?action=showClientDetail&id=<?php echo (int) $user['id']; ?>" title="Details"><i class="bi bi-eye"></i></a>
                            <a class="btn-sg btn-sg-success btn-sg-sm" href="/integration/client/controllers/AdminController.php?action=showEditUser&id=<?php echo (int) $user['id']; ?>" title="Modifier"><i class="bi bi-pencil-square"></i></a>
                            <a class="btn-sg btn-sg-danger btn-sg-sm" href="/integration/client/controllers/AdminController.php?action=deleteUser&id=<?php echo (int) $user['id']; ?>" onclick="return confirm('Supprimer ce client ?')" title="Supprimer"><i class="bi bi-trash3"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$extraScripts = <<<'HTML'
<script>
function filterClients() {
    const search = (document.getElementById('clientSearch').value || '').toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const relation = document.getElementById('relationFilter').value;
    let count = 0;

    document.querySelectorAll('[data-client-row]').forEach(function (row) {
        const rowStatus = row.getAttribute('data-status') || '';
        const vehicles = parseInt(row.getAttribute('data-vehicles') || '0', 10);
        const rdv = parseInt(row.getAttribute('data-rdv') || '0', 10);
        const relationOk = relation === ''
            || (relation === 'vehicles' && vehicles > 0)
            || (relation === 'rdv' && rdv > 0)
            || (relation === 'orphan' && vehicles === 0 && rdv === 0);
        const visible = (!status || rowStatus === status) && relationOk && (!search || row.textContent.toLowerCase().includes(search));
        row.style.display = visible ? '' : 'none';
        if (visible) count++;
    });

    document.getElementById('clientCount').textContent = count;
}

function resetClientFilters() {
    document.getElementById('clientSearch').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('relationFilter').value = '';
    filterClients();
}

function exportClientsCsv() {
    const rows = Array.from(document.querySelectorAll('[data-client-row]')).filter(function (row) {
        return row.style.display !== 'none';
    });
    let csv = 'Client,Contact,Statut,Vehicules,RDV,Urgence moyenne,Inscription\n';
    rows.forEach(function (row) {
        const cells = Array.from(row.cells).slice(0, 7).map(function (cell) {
            return '"' + cell.textContent.trim().replace(/\s+/g, ' ').replace(/"/g, '""') + '"';
        });
        csv += cells.join(',') + '\n';
    });
    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csv);
    link.download = 'clients_smart_garage_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
}
</script>
HTML;
require __DIR__ . '/layout_footer.php';
?>
