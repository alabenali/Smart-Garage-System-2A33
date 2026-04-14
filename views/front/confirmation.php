<?php
$pageTitle = 'Confirmation rendez-vous';
$action = 'frontCalendar';
$extraCss = ['views/css/calendrier.css'];
require __DIR__ . '/layout_header.php';
?>

<div class="confirm-page">
    <div class="confirm-icon"><i class="bi bi-patch-check-fill"></i></div>
    <h1>Votre rendez-vous est enregistré</h1>
    <p>Merci. Votre demande a bien été prise en compte.</p>

    <div class="confirm-card">
        <div><strong>Numéro RDV:</strong> #<?php echo (int) $rdv['id_rdv']; ?></div>
        <div><strong>Date:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($rdv['date_heure']))); ?></div>
        <div><strong>Heure:</strong> <?php echo htmlspecialchars(date('H:i', strtotime($rdv['date_heure']))); ?></div>
        <div><strong>Client:</strong> <?php echo htmlspecialchars($rdv['prenom_client'] . ' ' . $rdv['nom_client']); ?></div>
        <div><strong>Téléphone:</strong> <?php echo htmlspecialchars($rdv['telephone_client']); ?></div>
        <div><strong>Véhicule:</strong> <?php echo htmlspecialchars(($rdv['immatriculation'] ?? '-') . ' - ' . ($rdv['marque'] ?? '-') . ' ' . ($rdv['modele'] ?? '')); ?></div>
        <div><strong>Intervention:</strong> <?php echo htmlspecialchars($rdv['type_intervention']); ?></div>
        <div><strong>Statut:</strong> <?php echo htmlspecialchars($rdv['statut']); ?></div>
        <div><strong>Remise éco:</strong> <?php echo (float) $rdv['remise_eco_appliquee']; ?>%</div>
    </div>

    <a href="index.php?action=frontCalendar" class="btn-sg btn-sg-primary">Prendre un autre rendez-vous</a>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
