<?php
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel = new User();
$users = $userModel->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Clients - Smart Garage Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<aside class="sidebar">
    <div class="logo"><h2><i class="fas fa-car" style="color:#00E5FF;"></i> Smart Garage</h2></div>
    <nav><ul>
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="users_list.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="add_user.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h1><i class="fas fa-users" style="color:#00E5FF;"></i> Gestion des Clients</h1>
        <a href="add_user.php?action=showAddUser" class="btn-add"><i class="fas fa-user-plus"></i> Ajouter un client</a>
    </div>

    <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher par nom ou email..." oninput="filterTable()">
    </div>

    <table id="usersTable">
        <thead>
            <tr>
                <th>#</th><th>Nom complet</th><th>Email</th><th>Téléphone</th>
                <th>Adresse</th><th>Statut</th><th>Inscrit le</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8"><div class="empty"><i class="fas fa-user-slash" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>Aucun client enregistré</div></td></tr>
            <?php else: foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['telephone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($u['adresse'] ?? '-') ?></td>
                    <td><span class="status-<?= $u['statut'] ?>"><?= ucfirst($u['statut']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <a href="edit_user.php?action=showEditUser&id=<?= $u['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Modifier</a>
                        <a href="../../controllers/AdminController.php?action=deleteUser&id=<?= $u['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce client ?')"><i class="fas fa-trash"></i> Suppr.</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</main>
<script>
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
    });
}
</script>
</body>
</html>
