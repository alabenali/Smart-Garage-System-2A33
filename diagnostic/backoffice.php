<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!(isset($_SESSION['admin_id']) || (($_SESSION['role'] ?? '') === 'admin'))) {
    header('Location: /integration/client/controllers/AdminController.php?action=showLogin');
    exit;
}

$query = $_GET;
if (empty($query['action'])) {
    $query['action'] = 'diagnostics';
}
header('Location: index.php?' . http_build_query($query));
exit;
