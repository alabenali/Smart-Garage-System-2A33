<!-- ── DARK / LIGHT MODE — Backoffice ── -->
<style>
/* Light mode for backoffice */
body.light-mode-back { background:#f1f5f9 !important; }
body.light-mode-back .sidebar { background:linear-gradient(180deg,#1e293b,#0f172a) !important; }
body.light-mode-back .main { background:#f1f5f9 !important; }
body.light-mode-back .top-bar h1 { color:#1e293b !important; }
body.light-mode-back .kpi-card, body.light-mode-back .chart-box,
body.light-mode-back .table-wrap, body.light-mode-back .stat-card,
body.light-mode-back .stats-bar .stat-card { background:#fff !important; border-color:#e2e8f0 !important; color:#374151 !important; }
body.light-mode-back .kpi-val, body.light-mode-back .sc-val { color:inherit !important; }
body.light-mode-back #usersTable th { background:rgba(2,132,199,0.08) !important; color:#0284c7 !important; }
body.light-mode-back #usersTable td { color:#374151 !important; }
body.light-mode-back #usersTable tbody tr:hover { background:#f8fafc !important; }
body.light-mode-back .search-box { background:#fff !important; border-color:#e2e8f0 !important; }
body.light-mode-back .search-box input { color:#374151 !important; }
body.light-mode-back .sort-select { background:#fff !important; border-color:#e2e8f0 !important; color:#374151 !important; }
body.light-mode-back .flt-btn { background:#f8fafc !important; border-color:#e2e8f0 !important; color:#64748b !important; }
body.light-mode-back .result-count { color:#94a3b8 !important; }
body.light-mode-back .chart-box h3 { color:#64748b !important; }
body.light-mode-back .kpi-lbl, body.light-mode-back .sc-lbl { color:#94a3b8 !important; }
body.light-mode-back .progress-label { color:#64748b !important; }
body.light-mode-back .progress-track { background:rgba(0,0,0,0.08) !important; }
body.light-mode-back .alert-success { background:#f0fdf4 !important; color:#16a34a !important; }
body.light-mode-back .admin-badge { background:rgba(0,229,255,0.1) !important; color:#0284c7 !important; }

/* Bouton dans la sidebar en bas */
#dmBackBtn {
    display:flex; align-items:center; gap:10px;
    width:calc(100% - 32px); margin:8px 16px 4px;
    padding:10px 14px; border-radius:10px;
    border:1px solid rgba(255,255,255,0.08);
    background:rgba(255,255,255,0.04);
    color:#aaa; cursor:pointer; font-size:0.85rem;
    transition:all 0.2s; text-align:left;
}
#dmBackBtn:hover { background:rgba(0,229,255,0.08); color:#00E5FF; border-color:rgba(0,229,255,0.2); }
body.light-mode-back #dmBackBtn { background:rgba(0,0,0,0.04); border-color:#e2e8f0; color:#374151; }
</style>

<script>
// Insert dark mode button at bottom of sidebar
document.addEventListener('DOMContentLoaded', function() {
    var sidebar = document.querySelector('.sidebar nav ul');
    if (sidebar) {
        var li = document.createElement('li');
        li.style.cssText = 'margin-top:auto;padding-top:8px;border-top:1px solid rgba(255,255,255,0.08);';
        li.innerHTML = '<button id="dmBackBtn" onclick="toggleDarkBack()" title="Mode clair/sombre">' +
            '<i class="fas fa-moon" id="dmBackIcon"></i>' +
            '<span id="dmBackLabel">Mode clair</span>' +
            '</button>';
        sidebar.appendChild(li);
        // Apply saved theme to icon
        if (localStorage.getItem('sg_back_theme') === 'light') {
            var ic = document.getElementById('dmBackIcon');
            var lb = document.getElementById('dmBackLabel');
            if (ic) ic.className = 'fas fa-sun';
            if (lb) lb.textContent = 'Mode sombre';
        }
    }
});
</script>

<script>
(function() {
    if (localStorage.getItem('sg_back_theme') === 'light') {
        document.body.classList.add('light-mode-back');
        var ic = document.getElementById('dmBackIcon');
        if (ic) { ic.className = 'fas fa-sun'; }
    }
})();
function toggleDarkBack() {
    var isLight = document.body.classList.toggle('light-mode-back');
    var ic = document.getElementById('dmBackIcon');
    var lb = document.getElementById('dmBackLabel');
    if (ic) ic.className = 'fas ' + (isLight ? 'fa-sun' : 'fa-moon');
    if (lb) lb.textContent = isLight ? 'Mode sombre' : 'Mode clair';
    localStorage.setItem('sg_back_theme', isLight ? 'light' : 'dark');
}
</script>
