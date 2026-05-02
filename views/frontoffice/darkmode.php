<!-- ── DARK / LIGHT MODE TOGGLE ── -->
<style>
body { transition:background 0.35s,color 0.35s; }
body.light-mode { background:#f1f5f9 !important; }
body.light-mode .auth-body { background:#f1f5f9 !important; }
body.light-mode .auth-card { background:#fff !important; border-color:#e2e8f0 !important; box-shadow:0 8px 32px rgba(0,0,0,0.08) !important; }
body.light-mode .auth-logo h2 { color:#1e293b !important; }
body.light-mode .auth-logo p  { color:#64748b !important; }
body.light-mode label { color:#374151 !important; }
body.light-mode .input-wrap { background:#f8fafc !important; border-color:#cbd5e1 !important; }
body.light-mode .input-wrap input { color:#1e293b !important; background:transparent !important; }
body.light-mode .input-wrap i:not(.pw-eye i):not(.fa-eye):not(.fa-eye-slash) { color:#94a3b8 !important; }
body.light-mode .links a { color:#0284c7 !important; }
body.light-mode .links p { color:#64748b !important; }
body.light-mode .btn-primary { background:linear-gradient(135deg,#0ea5e9,#0284c7) !important; }
body.light-mode .navbar { background:rgba(255,255,255,0.97) !important; border-color:#e2e8f0 !important; backdrop-filter:blur(12px); }
body.light-mode .navbar h2, body.light-mode .nav-links a { color:#1e293b !important; }
body.light-mode .container { color:#1e293b !important; }
body.light-mode .info-card, body.light-mode .table-wrap, body.light-mode .chart-card { background:#fff !important; border-color:#e2e8f0 !important; color:#374151 !important; }
body.light-mode .hist-table th { color:#0284c7 !important; background:rgba(2,132,199,0.05) !important; }
body.light-mode .hist-table td, body.light-mode .hist-table td strong { color:#374151 !important; }
body.light-mode .welcome-banner h1 { color:#1e293b !important; }
body.light-mode .welcome-banner p  { color:#64748b !important; }
body.light-mode .logout-btn { color:#dc2626 !important; border-color:#fca5a5 !important; }

/* Bouton fixe en bas à droite — au-dessus du chatbot */
#dmBtn {
    position:fixed;
    bottom:24px;
    left:24px;
    z-index:9997;
    width:42px; height:42px;
    border-radius:50%;
    border:2px solid rgba(255,255,255,0.15);
    background:rgba(15,23,42,0.85);
    color:#ccc;
    cursor:pointer;
    font-size:1rem;
    display:flex; align-items:center; justify-content:center;
    transition:all 0.25s;
    backdrop-filter:blur(8px);
    box-shadow:0 2px 12px rgba(0,0,0,0.3);
}
#dmBtn:hover { background:rgba(30,41,59,0.95); color:#00E5FF; transform:scale(1.1); }
body.light-mode #dmBtn { background:rgba(255,255,255,0.92); border-color:#e2e8f0; color:#374151; box-shadow:0 2px 12px rgba(0,0,0,0.12); }
body.light-mode #dmBtn:hover { background:#f1f5f9; color:#0284c7; }
</style>

<button id="dmBtn" onclick="toggleDarkMode()" title="Mode clair / sombre">
    <i class="dm-icon fas fa-moon" id="dmMainIcon"></i>
</button>

<script>
(function() {
    if (localStorage.getItem('sg_theme') === 'light') {
        document.body.classList.add('light-mode');
        var ic = document.getElementById('dmMainIcon');
        if (ic) ic.className = 'dm-icon fas fa-sun';
    }
})();
function toggleDarkMode() {
    var isLight = document.body.classList.toggle('light-mode');
    document.querySelectorAll('.dm-icon').forEach(function(el) {
        el.className = 'dm-icon fas ' + (isLight ? 'fa-sun' : 'fa-moon');
    });
    localStorage.setItem('sg_theme', isLight ? 'light' : 'dark');
}
</script>
