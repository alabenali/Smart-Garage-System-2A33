<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: /projet_final/controllers/AdminController.php?action=showLogin'); exit; }
if (!isset($users)) { $users = []; }
$total   = count($users);
$actifs  = count(array_filter($users, fn($u) => $u['statut'] === 'actif'));
$bloques = $total - $actifs;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion Clients - Smart Garage Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
<style>
/* ── Stats Cards ── */
.stats-bar { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
.stat-card { flex:1; min-width:130px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:12px; }
.stat-card .sc-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.stat-card .sc-val  { font-size:1.6rem; font-weight:700; color:#e0e0e0; line-height:1; }
.stat-card .sc-lbl  { font-size:0.72rem; color:#888; margin-top:2px; }
.sc-total  .sc-icon { background:rgba(0,229,255,0.15); color:#00E5FF; }
.sc-actif  .sc-icon { background:rgba(0,230,118,0.15); color:#00e676; }
.sc-bloque .sc-icon { background:rgba(255,82,82,0.15);  color:#ff5252; }

/* ── Toolbar ── */
.toolbar { display:flex; gap:10px; margin-bottom:16px; flex-wrap:wrap; align-items:center; }
.search-box { display:flex; align-items:center; gap:8px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:8px 14px; flex:1; min-width:200px; }
.search-box input { background:none; border:none; outline:none; color:#e0e0e0; font-size:0.88rem; width:100%; }
.search-box input::placeholder { color:#555; }
.search-box i { color:#555; }

.sort-select { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:8px 14px; color:#ccc; font-size:0.85rem; outline:none; cursor:pointer; }
.sort-select option { background:#1a1a2e; }

.filter-btns { display:flex; gap:7px; }
.flt-btn { padding:7px 14px; border-radius:9px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.04); color:#aaa; font-size:0.8rem; cursor:pointer; transition:all 0.2s; }
.flt-btn:hover,.flt-btn.active { background:rgba(0,229,255,0.15); color:#00E5FF; border-color:rgba(0,229,255,0.3); }
.flt-btn.fa  { color:#00e676; border-color:rgba(0,230,118,0.2); background:rgba(0,230,118,0.08); }
.flt-btn.fb  { color:#ff5252; border-color:rgba(255,82,82,0.2);  background:rgba(255,82,82,0.08); }

.export-btn { padding:8px 16px; border-radius:10px; border:1px solid rgba(167,139,250,0.3); background:rgba(167,139,250,0.1); color:#a78bfa; font-size:0.82rem; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
.export-btn:hover { background:rgba(167,139,250,0.2); }

/* ── Table ── */
.table-wrap { overflow-x:auto; border-radius:14px; border:1px solid rgba(255,255,255,0.08); }
#usersTable { width:100%; border-collapse:collapse; }
#usersTable thead tr { background:rgba(0,229,255,0.08); }
#usersTable th { padding:12px 14px; text-align:left; color:#00E5FF; font-size:0.82rem; font-weight:600; white-space:nowrap; cursor:pointer; user-select:none; }
#usersTable th:hover { color:#fff; }
#usersTable th .sort-arrow { color:#555; margin-left:4px; font-size:0.7rem; }
#usersTable th.sorted-asc  .sort-arrow:before { content:'▲'; color:#00E5FF; }
#usersTable th.sorted-desc .sort-arrow:before { content:'▼'; color:#00E5FF; }
#usersTable th:not(.sorted-asc):not(.sorted-desc) .sort-arrow:before { content:'⇅'; }
#usersTable tbody tr { border-top:1px solid rgba(255,255,255,0.05); transition:background 0.15s; }
#usersTable tbody tr:hover { background:rgba(255,255,255,0.03); }
#usersTable td { padding:11px 14px; color:#ccc; font-size:0.85rem; }
#usersTable td strong { color:#e0e0e0; }
.status-actif  { background:rgba(0,230,118,0.15); color:#00e676; padding:3px 10px; border-radius:20px; font-size:0.75rem; white-space:nowrap; }
.status-bloque { background:rgba(255,82,82,0.15);  color:#ff5252; padding:3px 10px; border-radius:20px; font-size:0.75rem; white-space:nowrap; }
.btn-edit   { background:rgba(0,229,255,0.1); color:#00E5FF; border:1px solid rgba(0,229,255,0.2); padding:5px 10px; border-radius:7px; font-size:0.78rem; text-decoration:none; transition:all 0.2s; display:inline-block; }
.btn-edit:hover { background:rgba(0,229,255,0.2); }
.btn-delete { background:rgba(255,82,82,0.1); color:#ff5252; border:1px solid rgba(255,82,82,0.2); padding:5px 10px; border-radius:7px; font-size:0.78rem; text-decoration:none; transition:all 0.2s; display:inline-block; margin-left:4px; }
.btn-delete:hover { background:rgba(255,82,82,0.2); }
.no-results { text-align:center; padding:40px; color:#555; }

/* ── Result count ── */
.result-count { font-size:0.78rem; color:#666; margin-bottom:8px; }
.result-count span { color:#00E5FF; }
</style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage Admin</h2></div>
    <div style="text-align:center;padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:10px;">
        <div style="width:55px;height:55px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:white;margin:0 auto 6px;border:3px solid #00E5FF;">
            <?= strtoupper(substr($_SESSION['admin_nom']??'A',0,1)) ?>
        </div>
        <div style="color:#ccc;font-size:0.85rem;"><?= htmlspecialchars($_SESSION['admin_nom']??'Admin') ?></div>
    </div>
    <nav><ul>
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AIController.php?action=showAssistant" style="color:#a78bfa;"><i class="fas fa-robot"></i> AI Helper</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h1><i class="fas fa-users" style="color:#00E5FF;"></i> Gestion des Clients</h1>
        <a href="/projet_final/controllers/AdminController.php?action=showAddUser" class="btn-add"><i class="fas fa-user-plus"></i> Ajouter</a>
    </div>

    <!-- ── STATS ── -->
    <div class="stats-bar">
        <div class="stat-card sc-total">
            <div class="sc-icon"><i class="fas fa-users"></i></div>
            <div><div class="sc-val"><?= $total ?></div><div class="sc-lbl">Total clients</div></div>
        </div>
        <div class="stat-card sc-actif">
            <div class="sc-icon"><i class="fas fa-user-check"></i></div>
            <div><div class="sc-val"><?= $actifs ?></div><div class="sc-lbl">Actifs</div></div>
        </div>
        <div class="stat-card sc-bloque">
            <div class="sc-icon"><i class="fas fa-user-lock"></i></div>
            <div><div class="sc-val"><?= $bloques ?></div><div class="sc-lbl">Bloqués</div></div>
        </div>
        <div class="stat-card" style="border-color:rgba(167,139,250,0.2);">
            <div class="sc-icon" style="background:rgba(167,139,250,0.15);color:#a78bfa;"><i class="fas fa-percent"></i></div>
            <div>
                <div class="sc-val" style="color:#a78bfa;"><?= $total > 0 ? round($actifs/$total*100) : 0 ?>%</div>
                <div class="sc-lbl">Taux d'activité</div>
            </div>
        </div>
    </div>

    <!-- ── TOOLBAR ── -->
    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Rechercher par nom, email, téléphone..." oninput="applyFilters()">
        </div>
        <select class="sort-select" id="sortSelect" onchange="applyFilters()">
            <option value="">🔃 Trier par...</option>
            <option value="nom-asc">Nom A→Z</option>
            <option value="nom-desc">Nom Z→A</option>
            <option value="email-asc">Email A→Z</option>
            <option value="date-desc">Plus récents</option>
            <option value="date-asc">Plus anciens</option>
        </select>
        <div class="filter-btns">
            <button class="flt-btn active" onclick="setFilter('all',this)"><i class="fas fa-list"></i> Tous</button>
            <button class="flt-btn fa"     onclick="setFilter('actif',this)"><i class="fas fa-circle" style="font-size:0.6rem;"></i> Actifs</button>
            <button class="flt-btn fb"     onclick="setFilter('bloque',this)"><i class="fas fa-ban"></i> Bloqués</button>
        </div>
        <button class="export-btn" onclick="exportCSV()"><i class="fas fa-download"></i> Export CSV</button>
    </div>

    <div class="result-count">Affichage de <span id="resCount"><?= $total ?></span> client(s)</div>

    <!-- ── TABLE ── -->
    <div class="table-wrap">
        <table id="usersTable">
            <thead>
                <tr>
                    <th onclick="sortCol(0)"><span>#</span><span class="sort-arrow"></span></th>
                    <th onclick="sortCol(1)"><span>Nom complet</span><span class="sort-arrow"></span></th>
                    <th onclick="sortCol(2)"><span>Email</span><span class="sort-arrow"></span></th>
                    <th><span>Téléphone</span></th>
                    <th><span>Adresse</span></th>
                    <th onclick="sortCol(5)"><span>Statut</span><span class="sort-arrow"></span></th>
                    <th onclick="sortCol(6)"><span>Inscrit le</span><span class="sort-arrow"></span></th>
                    <th><span>Actions</span></th>
                </tr>
            </thead>
            <tbody id="tableBody">
            <?php if (empty($users)): ?>
                <tr><td colspan="8"><div class="no-results"><i class="fas fa-user-slash" style="font-size:2.5rem;display:block;margin-bottom:10px;"></i>Aucun client</div></td></tr>
            <?php else: foreach ($users as $u): ?>
                <tr data-statut="<?= $u['statut'] ?>">
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['telephone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($u['adresse'] ?? '-') ?></td>
                    <td><span class="status-<?= $u['statut'] ?>"><?= ucfirst($u['statut']) ?></span></td>
                    <td><?= isset($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '-' ?></td>
                    <td>
                        <a href="/projet_final/controllers/AdminController.php?action=showEditUser&id=<?= $u['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Modifier</a>
                        <a href="/projet_final/controllers/AdminController.php?action=deleteUser&id=<?= $u['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce client ?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
var currentFilter = 'all';
var sortState = {};

function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.flt-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function applyFilters() {
    var search = document.getElementById('searchInput').value.toLowerCase();
    var rows   = document.querySelectorAll('#tableBody tr[data-statut]');
    var count  = 0;
    rows.forEach(function(row) {
        var statut  = row.getAttribute('data-statut');
        var text    = row.textContent.toLowerCase();
        var matchF  = currentFilter === 'all' || statut === currentFilter;
        var matchS  = !search || text.includes(search);
        var show    = matchF && matchS;
        row.style.display = show ? '' : 'none';
        if (show) count++;
    });
    document.getElementById('resCount').textContent = count;
}

function sortCol(colIdx) {
    var th   = document.querySelectorAll('#usersTable thead th')[colIdx];
    var asc  = !th.classList.contains('sorted-asc');
    document.querySelectorAll('#usersTable thead th').forEach(function(t) {
        t.classList.remove('sorted-asc','sorted-desc');
    });
    th.classList.add(asc ? 'sorted-asc' : 'sorted-desc');

    var tbody = document.getElementById('tableBody');
    var rows  = Array.from(tbody.querySelectorAll('tr[data-statut]'));
    rows.sort(function(a, b) {
        var va = a.cells[colIdx] ? a.cells[colIdx].textContent.trim() : '';
        var vb = b.cells[colIdx] ? b.cells[colIdx].textContent.trim() : '';
        // Numeric for col 0
        if (colIdx === 0) return asc ? parseInt(va)-parseInt(vb) : parseInt(vb)-parseInt(va);
        // Date for col 6
        if (colIdx === 6) {
            var da = va.split('/').reverse().join('-');
            var db = vb.split('/').reverse().join('-');
            return asc ? da.localeCompare(db) : db.localeCompare(da);
        }
        return asc ? va.localeCompare(vb,'fr') : vb.localeCompare(va,'fr');
    });
    rows.forEach(function(r) { tbody.appendChild(r); });
}

function exportCSV() {
    var rows = Array.from(document.querySelectorAll('#tableBody tr[data-statut]')).filter(function(r){ return r.style.display !== 'none'; });
    var csv  = 'ID,Nom,Email,Téléphone,Adresse,Statut,Inscrit le\n';
    rows.forEach(function(r) {
        var cells = Array.from(r.cells).slice(0,7).map(function(c){ return '"' + c.textContent.trim().replace(/"/g,'""') + '"'; });
        csv += cells.join(',') + '\n';
    });
    var a = document.createElement('a');
    a.href = 'data:text/csv;charset=utf-8,\uFEFF' + encodeURIComponent(csv);
    a.download = 'clients_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}
</script>
<?php require_once __DIR__ . "/darkmode_back.php"; ?>
</body>
</html>
