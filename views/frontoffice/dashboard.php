<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: /projet_final/controllers/UserController.php?action=showLogin'); exit; }

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
    if (file_exists($sp)) $avatarPath = '/projet_final/' . $profilePic;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace - Smart Garage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/projet_final/views/frontoffice/style.css">
    <style>
    .nav-avatar { width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #00E5FF; }
    .nav-avatar-placeholder { width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;font-size:1rem;color:white;border:2px solid #00E5FF; }

    /* ── Stats ── */
    .stat-card:hover { transform:translateY(-4px);border-color:rgba(0,229,255,0.4); }
    .stat-card i { font-size:1.8rem;color:#00E5FF;margin-bottom:10px; }
    .stat-card h3 { font-size:0.7rem;color:#888;letter-spacing:1px;margin:0 0 8px; }
    .stat-card .value { font-size:2rem;font-weight:700;color:#e0e0e0; }
    .stat-card .sub { font-size:0.72rem;color:#555;margin-top:4px; }

    /* ── Section header ── */
    .section-header { display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin:28px 0 16px; }
    .section-header h2 { margin:0;font-size:1.1rem;color:#e0e0e0; }
    .section-header h2 i { color:#00E5FF;margin-right:8px; }

    /* ── Toolbar ── */
    .toolbar { display:flex;gap:10px;align-items:center;flex-wrap:wrap; }
    .search-wrap { position:relative; }
    .search-wrap i { position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#555;font-size:0.85rem; }
    #searchInput { background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:10px;padding:8px 12px 8px 32px;color:#e0e0e0;font-size:0.83rem;outline:none;width:200px;transition:border 0.2s; }
    #searchInput:focus { border-color:rgba(0,229,255,0.4); }
    #searchInput::placeholder { color:#444; }

    
    
    

    
    @media(max-width:700px){.charts-row{grid-template-columns:1fr;}}
    .chart-card { background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:20px; }
    .chart-card h4 { margin:0 0 16px;color:#aaa;font-size:0.85rem; }
    .bar-row { display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .bar-label { color:#888;font-size:0.75rem;width:120px;flex-shrink:0; }
    .bar-bg { flex:1;background:rgba(255,255,255,0.06);border-radius:6px;height:8px;overflow:hidden; }
    .bar-fill { height:100%;border-radius:6px;background:linear-gradient(90deg,#00E5FF,#0284c7);transition:width 1s ease; }
    .bar-val { color:#e0e0e0;font-size:0.75rem;width:60px;text-align:right;flex-shrink:0; }
    .donut-wrap { display:flex;align-items:center;gap:20px; }
    .donut-legend { display:flex;flex-direction:column;gap:8px; }
    .legend-item { display:flex;align-items:center;gap:8px;font-size:0.75rem;color:#aaa; }
    .legend-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><i class="fas fa-car" style="color:#00E5FF;margin-right:8px;"></i><h2>Smart Garage</h2></div>
    <ul class="nav-links">
        <li><a href="/projet_final/controllers/UserController.php?action=showDashboard" class="active">Mon espace</a></li>
        <li><a href="/projet_final/controllers/UserController.php?action=showProfile">Mon profil</a></li>
    </ul>
    <div style="display:flex;align-items:center;gap:1rem;">
        <?php if ($avatarPath): ?>
            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Profil" class="nav-avatar">
        <?php else: ?>
            <div class="nav-avatar-placeholder"><?= strtoupper(substr($prenom,0,1)) ?></div>
        <?php endif; ?>
        <span><?= $prenom ?></span>
        <button class="dm-toggle-nav" onclick="toggleDarkMode()" title="Mode clair/sombre"><i class="dm-icon fas fa-moon"></i></button>
        <a href="/projet_final/controllers/UserController.php?action=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</nav>

<div class="container">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="welcome-banner">
        <h1>Bonjour, <?= $prenom ?> <span style="color:#00E5FF;"><?= $nom ?></span> 👋</h1>
        <p class="greeting">Bienvenue sur votre espace Smart Garage — Gestion intelligente de vos véhicules</p>
    </div>

<?php require_once __DIR__ . "/darkmode.php"; ?>
<?php require_once __DIR__ . "/chatbot_widget.php"; ?>
</body>
</html>
