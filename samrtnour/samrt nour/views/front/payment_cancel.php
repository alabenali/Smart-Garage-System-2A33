<?php
$pageTitle = 'Paiement annule';
$action = 'orderPiece';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="payment-feedback-card">
    <div class="payment-feedback-icon cancel">
        <i class="bi bi-x-circle"></i>
    </div>
    <h1 class="page-title" style="margin-bottom:0.4rem;">Paiement annule</h1>
    <p class="page-subtitle" style="margin-bottom:1.2rem;">Le paiement Konnect a ete annule. Aucune commande payee n'a ete creee.</p>
    <div class="hero-actions" style="justify-content:center;">
        <a href="index.php?action=orderPiece" class="btn-sg btn-sg-primary">
            <i class="bi bi-arrow-repeat"></i> Reessayer
        </a>
        <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Retour au catalogue</a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
