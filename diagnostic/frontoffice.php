<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /integration/client/controllers/UserController.php?action=showLogin');
    exit;
}

$query = $_GET;
if (empty($query['action'])) {
    $query['action'] = 'client_dashboard';
}
header('Location: index.php?' . http_build_query($query));
exit;
