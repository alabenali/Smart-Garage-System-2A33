<?php
/**
 * Script d'import des pièces automobiles
 * À exécuter via : php database/import_pieces.php
 */

require_once __DIR__ . '/../config/Database.php';

try {
    $conn = Database::getInstance()->getConnection();
    
    // Lire le fichier SQL
    $sqlFile = __DIR__ . '/import_pieces.sql';
    if (!file_exists($sqlFile)) {
        die("Fichier SQL non trouvé: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        die("Impossible de lire le fichier SQL\n");
    }
    
    // Traiter le fichier SQL ligne par ligne
    $lines = explode("\n", $sql);
    $statement = '';
    $count = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignorer les lignes vides et les commentaires
        if (empty($line) || str_starts_with($line, '--')) {
            continue;
        }
        
        $statement .= ' ' . $line;
        
        // Si la ligne finit par un point-virgule, c'est la fin du statement
        if (str_ends_with($line, ';')) {
            $statement = trim($statement);
            
            if (!empty($statement)) {
                try {
                    $conn->exec($statement);
                    $count++;
                    echo "✓ Instruction $count exécutée\n";
                } catch (PDOException $e) {
                    echo "✗ Erreur instruction $count: " . $e->getMessage() . "\n";
                }
            }
            
            $statement = '';
        }
    }
    
    echo "\n✓ Import terminé! $count instructions exécutées\n";
    
    // Compter les pièces insérées
    $result = $conn->query('SELECT COUNT(*) as total FROM pieces');
    $row = $result->fetch();
    echo "\nTotal des pièces dans la base de données: " . $row['total'] . "\n";
    
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage() . "\n");
}
?>
