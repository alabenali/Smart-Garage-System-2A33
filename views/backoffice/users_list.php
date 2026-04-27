<?php
<<<<<<< HEAD
// views/backoffice/users_list.php

require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) {
    header('Location: /projet_final/controllers/AdminController.php?action=showLogin');
    exit;
}
if (!isset($users)) { $users = []; }
=======
require_once __DIR__ . '/../../config.php';
if (!isset($_SESSION['admin_id'])) { header('Location: admin_login.php'); exit; }
require_once __DIR__ . '/../../models/User.php';
$userModel = new User();
$users = $userModel->getAll();
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Clients - Smart Garage Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="/projet_final/views/backoffice/style.css">
    <style>
        /* Styles pour les contrôles de tri */
        .sort-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .sort-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(26, 26, 26, 0.5);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            border: 1px solid rgba(0, 229, 255, 0.2);
        }
        
        .sort-group label {
            color: #00E5FF;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .sort-group select {
            background: rgba(0, 229, 255, 0.1);
            border: 1px solid rgba(0, 229, 255, 0.3);
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .sort-group select:hover {
            background: rgba(0, 229, 255, 0.2);
            border-color: #00E5FF;
        }
        
        .sort-group select:focus {
            outline: none;
            border-color: #00E5FF;
            box-shadow: 0 0 5px rgba(0, 229, 255, 0.3);
        }
        
        .sort-group select option {
            background: #0D1F3A;
            color: #fff;
        }
        
        .reset-sort {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ccc;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        
        .reset-sort:hover {
            background: rgba(0, 229, 255, 0.15);
            border-color: #00E5FF;
            color: #00E5FF;
        }

        /* CORRECTION : Aligner les boutons d'action horizontalement */
        td:last-child {
            white-space: nowrap;
        }

        td:last-child .btn-edit,
        td:last-child .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 8px;
        }

        td:last-child .btn-delete {
            margin-right: 0;
        }
    </style>
=======
    <link rel="stylesheet" href="style.css">
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
</head>
<body>
<aside class="sidebar">
    <div class="logo"><h2><i class="fas fa-car" style="color:#00E5FF;"></i> Smart Garage</h2></div>
    <nav><ul>
<<<<<<< HEAD
        <li><a href="/projet_final/controllers/AdminController.php?action=showDashboard"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showStatistics"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=showAdminProfile"><i class="fas fa-user-cog"></i> Mon profil</a></li>
        <li><a href="/projet_final/controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
=======
        <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
        <li><a href="users_list.php?action=listUsers" class="active"><i class="fas fa-users"></i> Gestion Clients</a></li>
        <li><a href="add_user.php?action=showAddUser"><i class="fas fa-user-plus"></i> Ajouter un client</a></li>
        <li><a href="../../controllers/AdminController.php?action=logout" style="color:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
    </ul></nav>
</aside>

<main class="main">
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="top-bar">
        <h1><i class="fas fa-users" style="color:#00E5FF;"></i> Gestion des Clients</h1>
<<<<<<< HEAD
        <a href="/projet_final/controllers/AdminController.php?action=showAddUser" class="btn-add"><i class="fas fa-user-plus"></i> Ajouter un client</a>
=======
        <a href="add_user.php?action=showAddUser" class="btn-add"><i class="fas fa-user-plus"></i> Ajouter un client</a>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
    </div>

    <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Rechercher par nom ou email..." oninput="filterTable()">
    </div>

<<<<<<< HEAD
    <div class="sort-controls">
        <div class="sort-group">
            <label><i class="fas fa-sort-amount-down-alt"></i> Trier par :</label>
            <select id="sortSelect" onchange="sortTable()">
                <option value="id-desc">ID (Plus récent)</option>
                <option value="id-asc">ID (Plus ancien)</option>
                <option value="nom-asc">Nom (A → Z)</option>
                <option value="nom-desc">Nom (Z → A)</option>
                <option value="email-asc">Email (A → Z)</option>
                <option value="date-desc">Date d'inscription (Plus récent)</option>
                <option value="date-asc">Date d'inscription (Plus ancien)</option>
            </select>
        </div>
        <button class="reset-sort" onclick="resetSort()">
            <i class="fas fa-undo-alt"></i> Réinitialiser le tri
        </button>
    </div>

=======
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
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
<<<<<<< HEAD
                        <a href="/projet_final/controllers/AdminController.php?action=showEditUser&id=<?= $u['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Modifier</a>
                        <a href="/projet_final/controllers/AdminController.php?action=deleteUser&id=<?= $u['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce client ?')"><i class="fas fa-trash"></i> Suppr.</a>
=======
                        <a href="edit_user.php?action=showEditUser&id=<?= $u['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i> Modifier</a>
                        <a href="../../controllers/AdminController.php?action=deleteUser&id=<?= $u['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce client ?')"><i class="fas fa-trash"></i> Suppr.</a>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</main>
<<<<<<< HEAD

<script>
let currentSortValue = 'id-desc';




//// tri et recherche

=======
<script>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
    });
}
<<<<<<< HEAD

function sortTable() {
    currentSortValue = document.getElementById('sortSelect').value;
    const [field, direction] = currentSortValue.split('-');
    const tbody = document.querySelector('#usersTable tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aVal, bVal;
        switch(field) {
            case 'id':
                aVal = parseInt(a.cells[0].textContent);
                bVal = parseInt(b.cells[0].textContent);
                break;
            case 'nom':
                aVal = a.cells[1].textContent.toLowerCase();
                bVal = b.cells[1].textContent.toLowerCase();
                break;
            case 'email':
                aVal = a.cells[2].textContent.toLowerCase();
                bVal = b.cells[2].textContent.toLowerCase();
                break;
            case 'date':
                aVal = new Date(a.cells[6].textContent.split('/').reverse().join('-'));
                bVal = new Date(b.cells[6].textContent.split('/').reverse().join('-'));
                break;
            default:
                return 0;
        }
        if (aVal < bVal) return direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return direction === 'asc' ? 1 : -1;
        return 0;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    filterTable();
}

function resetSort() {
    document.getElementById('sortSelect').value = 'id-desc';
    sortTable();
}

document.addEventListener('DOMContentLoaded', function() {
    sortTable();
});
</script>
</body>
</html>
=======
</script>
</body>
</html>
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
