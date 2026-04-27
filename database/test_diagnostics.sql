-- ============================================
-- Test Data: Diagnostics en attente
-- ============================================

-- D'abord, assurez-vous que la base est utilisée
USE smart_garage_system;

-- Insérez quelques diagnostics de test avec statut 'en_attente'
INSERT INTO diagnostic (id_vehicule, description_probleme, resultat, gravite, montant_estime, status, date_diagnostic, media_path, media_type) VALUES
(1, 'Bruit moteur anormal', 'En attente de traitement', 'Moyen', 250.50, 'en_attente', CURDATE(), NULL, NULL),
(2, 'Problème de freinage', 'À vérifier', 'Élevé', 450.00, 'en_attente', CURDATE(), NULL, NULL),
(3, 'Usure plaquettes', 'À diagnostiquer', 'Faible', 150.00, 'en_attente', CURDATE(), NULL, NULL),
(4, 'Voyant moteur allumé', 'Diagnostic requis', 'Moyen', 300.00, 'en_attente', CURDATE(), NULL, NULL),
(5, 'Problème de climatisation', 'À analyser', 'Faible', 200.00, 'en_attente', CURDATE(), NULL, NULL);

-- Vérifiez les diagnostics insérés
SELECT * FROM diagnostic WHERE status = 'en_attente';
