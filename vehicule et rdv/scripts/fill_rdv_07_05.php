<?php
/**
 * Script pour remplir les rendez-vous du 07/05/2026
 * Crée des rendez-vous de test pour tous les créneaux disponibles
 */

require_once __DIR__ . '/../config/Database.php';

// Paramètres
$targetDate = '2026-05-07';
$maxRdvPerSlot = 3; // Capacité maximale par créneau

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Créer les créneaux du jour s'ils n'existent pas
    echo "Création des créneaux pour le 07/05/2026...\n";
    for ($hour = 8; $hour <= 17; $hour++) {
        $dateTime = sprintf('%s %02d:00:00', $targetDate, $hour);
        $isOffPeak = ($hour >= 13 && $hour < 15) ? 1 : 0;
        
        // Vérifier si le créneau existe
        $checkStmt = $db->prepare('SELECT id_creneau FROM creneau_atelier WHERE date_heure = :date_heure');
        $checkStmt->execute([':date_heure' => $dateTime]);
        
        if (!$checkStmt->fetch()) {
            $insertStmt = $db->prepare(
                'INSERT INTO creneau_atelier (date_heure, est_heure_creuse, capacite_max) 
                 VALUES (:date_heure, :est_heure_creuse, :capacite_max)'
            );
            $insertStmt->execute([
                ':date_heure' => $dateTime,
                ':est_heure_creuse' => $isOffPeak,
                ':capacite_max' => $maxRdvPerSlot,
            ]);
            echo "  ✓ Créneau créé: $dateTime (heure " . ($isOffPeak ? 'creuse' : 'normale') . ")\n";
        }
    }
    
    // 2. Récupérer tous les créneaux du jour
    echo "\nRécupération des créneaux...\n";
    $slotStmt = $db->prepare(
        'SELECT id_creneau, date_heure, est_heure_creuse FROM creneau_atelier 
         WHERE DATE(date_heure) = :date 
         ORDER BY date_heure ASC'
    );
    $slotStmt->execute([':date' => $targetDate]);
    $slots = $slotStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Créneaux trouvés: " . count($slots) . "\n";
    
    // 3. Créer les rendez-vous
    echo "\nCréation des rendez-vous...\n";
    $rdvCount = 0;
    $typesIntervention = [
        'Vidange',
        'Révision',
        'Changement de pneu',
        'Batterie',
        'Freinage',
        'Climatisation',
        'Diagnostic général'
    ];
    
    foreach ($slots as $slot) {
        $slotId = (int)$slot['id_creneau'];
        $slotTime = $slot['date_heure'];
        $isOffPeak = (int)$slot['est_heure_creuse'];
        $remise = $isOffPeak ? 15.00 : 0.00;
        
        for ($i = 1; $i <= $maxRdvPerSlot; $i++) {
            $typeIdx = ($rdvCount % count($typesIntervention));
            $typeIntervention = $typesIntervention[$typeIdx];
            
            $insertRdvStmt = $db->prepare(
                'INSERT INTO rendezvous_digital 
                 (id_creneau, nom_client, prenom_client, telephone_client, email_client, 
                  id_vehicle, type_intervention, description_panne, circonstances_panne, 
                  temoins_panne, panne_data_json, photos_json, urgence_score, urgence_details,
                  remise_eco_appliquee, statut, notes, date_creation, date_modification)
                 VALUES 
                 (:id_creneau, :nom_client, :prenom_client, :telephone_client, :email_client,
                  :id_vehicle, :type_intervention, :description_panne, :circonstances_panne,
                  :temoins_panne, :panne_data_json, :photos_json, :urgence_score, :urgence_details,
                  :remise_eco_appliquee, :statut, :notes, :date_creation, :date_modification)'
            );
            
            $insertRdvStmt->execute([
                ':id_creneau' => $slotId,
                ':nom_client' => 'Client_' . ($rdvCount + 1),
                ':prenom_client' => 'Test_' . ($i),
                ':telephone_client' => '2167' . str_pad($rdvCount + 1, 6, '0', STR_PAD_LEFT),
                ':email_client' => 'test' . ($rdvCount + 1) . '@example.com',
                ':id_vehicle' => null,
                ':type_intervention' => $typeIntervention,
                ':description_panne' => 'Panne de test - ' . $typeIntervention,
                ':circonstances_panne' => 'En roulant',
                ':temoins_panne' => json_encode(['Bruit'], JSON_UNESCAPED_UNICODE),
                ':panne_data_json' => json_encode([], JSON_UNESCAPED_UNICODE),
                ':photos_json' => json_encode([], JSON_UNESCAPED_UNICODE),
                ':urgence_score' => rand(1, 5),
                ':urgence_details' => json_encode(['test' => true], JSON_UNESCAPED_UNICODE),
                ':remise_eco_appliquee' => $remise,
                ':statut' => 'En attente',
                ':notes' => 'Rendez-vous de test créé automatiquement',
                ':date_creation' => date('Y-m-d H:i:s'),
                ':date_modification' => date('Y-m-d H:i:s'),
            ]);
            
            $rdvCount++;
        }
        
        echo "  ✓ $maxRdvPerSlot RDV créés pour le créneau $slotTime\n";
    }
    
    echo "\n✓ Opération terminée avec succès!\n";
    echo "Total RDV créés: $rdvCount\n";
    echo "Total créneaux: " . count($slots) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
