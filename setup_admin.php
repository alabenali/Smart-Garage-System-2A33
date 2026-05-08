<?php
require __DIR__ . '/client/config.php';

try {
    $db = Database::getConnection();
    
    // Créer/mettre à jour l'admin avec des identifiants clairs
    $email = getenv('SMART_GARAGE_ADMIN_EMAIL') ?: 'admin@smartgarage.local';
    $password = getenv('SMART_GARAGE_ADMIN_PASSWORD') ?: 'change-this-password';
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Vérifier si l'admin existe
    $stmt = $db->prepare("SELECT id FROM user WHERE email = ? AND post = 'admin'");
    $stmt->execute([$email]);
    $existingAdmin = $stmt->fetch();
    
    if($existingAdmin) {
        // Mettre à jour le mot de passe
        $stmt = $db->prepare("UPDATE user SET mot_de_passe = ? WHERE email = ? AND post = 'admin'");
        $stmt->execute([$hashedPassword, $email]);
        echo "✓ Admin existant mis à jour\n";
    } else {
        // Créer un nouvel admin
        $stmt = $db->prepare("
            INSERT INTO user (nom, prenom, email, mot_de_passe, statut, post, created_at)
            VALUES (?, ?, ?, ?, 'actif', 'admin', NOW())
        ");
        $stmt->execute(['Admin', 'Smart Garage', $email, $hashedPassword]);
        echo "✓ Admin créé\n";
    }
    
    echo "\n=== IDENTIFIANTS DE CONNEXION ADMIN ===\n";
    echo "Email    : " . $email . "\n";
    echo "Mot de passe: " . $password . "\n";
    echo "\nURL: http://localhost/integration/client/controllers/AdminController.php?action=showLogin\n";
    
} catch(Exception $e) {
    echo 'Erreur: ' . $e->getMessage();
}
?>
