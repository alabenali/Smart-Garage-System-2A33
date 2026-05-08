<?php
require __DIR__ . '/client/config.php';

try {
    $db = Database::getConnection();
    
    echo "=== Tables in garage1 database ===\n";
    $tables = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'garage1'")->fetchAll();
    foreach($tables as $t) echo "  - " . $t['TABLE_NAME'] . "\n";
    
    echo "\n=== User table structure ===\n";
    $columns = $db->query("DESCRIBE user")->fetchAll();
    foreach($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== All users with post='admin' ===\n";
    $admins = $db->query("SELECT id, email, post, statut FROM user WHERE post = 'admin'")->fetchAll();
    if(empty($admins)) {
        echo "No admin users found!\n";
    } else {
        foreach($admins as $admin) {
            echo "ID: " . $admin['id'] . " | Email: " . $admin['email'] . " | Post: " . $admin['post'] . " | Status: " . $admin['statut'] . "\n";
        }
    }
    
    echo "\n=== Total users in database ===\n";
    $count = $db->query("SELECT COUNT(*) as cnt FROM user")->fetch()['cnt'];
    echo "Total: " . $count . "\n";
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
