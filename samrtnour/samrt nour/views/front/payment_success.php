<?php
$pageTitle = 'Paiement confirme';
$action = 'orderPiece';
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<div class="payment-feedback-card">
    <div class="payment-feedback-icon success">
        <i class="bi bi-check2-circle"></i>
    </div>
    <h1 class="page-title" style="margin-bottom:0.4rem;">Paiement confirme</h1>

    <?php if (!empty($error)): ?>
        <div class="sg-alert sg-alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars((string) $error); ?></div>
    <?php else: ?>
        <p class="page-subtitle" style="margin-bottom:1.1rem;"><?php echo htmlspecialchars((string) $success); ?></p>
        <?php if (!empty($commande)): ?>
            <div class="payment-summary-box">
                <div><strong>Commande :</strong> #<?php echo (int) $commande['id_commande']; ?></div>
                <div><strong>Piece :</strong> <?php echo htmlspecialchars((string) $commande['piece_nom']); ?></div>
                <div><strong>Total :</strong> <?php echo number_format((float) $commande['montant_total'], 2, ',', ' '); ?> DT</div>
                <div><strong>Paiement :</strong> <?php echo htmlspecialchars((string) ($commande['payment_method'] ?? 'Konnect')); ?></div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="hero-actions" style="justify-content:center; margin-top:1rem;">
        <a href="index.php?action=orderHistory" class="btn-sg btn-sg-primary">
            <i class="bi bi-clock-history"></i> Voir l'historique
        </a>
        <a href="index.php?action=showCatalogue" class="btn-sg btn-sg-outline">Retour au catalogue</a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
