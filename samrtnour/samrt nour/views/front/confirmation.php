<?php
$pageTitle = $pageTitle ?? 'Commande confirmée';
$action = $action ?? 'orderConfirmation';
$commande = $commande ?? [];
$lastOrder = $lastOrder ?? null;
?>
<?php require __DIR__ . '/layout_header.php'; ?>

<style>
/* ── Confirmation page ── */
.confirmation-wrap {
    max-width: 700px; margin: 2rem auto; text-align: center;
}

/* ── Checkmark SVG animé ── */
.checkmark-circle {
    width: 100px; height: 100px; margin: 0 auto 1.5rem;
}
.checkmark-circle svg { width: 100px; height: 100px; }
.checkmark-circle .circle {
    stroke: #059669; stroke-width: 3; fill: none;
    stroke-dasharray: 300; stroke-dashoffset: 300;
    animation: drawCircle .6s ease forwards;
}
.checkmark-circle .check {
    stroke: #059669; stroke-width: 4; fill: none;
    stroke-linecap: round; stroke-linejoin: round;
    stroke-dasharray: 50; stroke-dashoffset: 50;
    animation: drawCheck .4s .5s ease forwards;
}
@keyframes drawCircle { to { stroke-dashoffset: 0; } }
@keyframes drawCheck  { to { stroke-dashoffset: 0; } }

.confirmation-title {
    font-size: 1.8rem; font-weight: 800; color: #059669;
    margin-bottom: .5rem;
}
.confirmation-sub {
    font-size: 1rem; color: var(--sg-text-muted, #64748b);
    margin-bottom: .5rem;
}
.confirmation-order-id {
    display: inline-block; padding: 8px 20px;
    background: var(--sg-bg-subtle, #f0fdf4); border: 1px solid #a7f3d0;
    border-radius: 12px; font-size: 1.1rem; font-weight: 700;
    color: #059669; margin: 1rem 0;
}
.confirmation-sms {
    font-size: .88rem; color: var(--sg-text-muted, #64748b);
    margin-bottom: 1.5rem;
}
.confirmation-sms i { color: #059669; }

/* ── Tableau récap ── */
.confirmation-table-wrap {
    background: var(--sg-card-bg, #fff);
    border: 1px solid var(--sg-border, #e2e8f0);
    border-radius: 18px; overflow: hidden; margin: 1.5rem 0;
    text-align: left;
}
.confirmation-table { width: 100%; border-collapse: collapse; }
.confirmation-table th {
    background: var(--sg-bg-subtle, #f8fafc);
    padding: 10px 14px; font-size: .78rem; text-transform: uppercase;
    letter-spacing: .04em; color: var(--sg-text-muted, #64748b);
    border-bottom: 1px solid var(--sg-border, #e2e8f0);
}
.confirmation-table td {
    padding: 10px 14px; font-size: .88rem;
    border-bottom: 1px solid var(--sg-border-light, #f1f5f9);
}
.confirmation-table tr:last-child td { border-bottom: none; }
.confirmation-total-row {
    background: linear-gradient(135deg, #173252, #1e3a5f);
    color: #fff;
}
.confirmation-total-row td { font-weight: 700; font-size: 1rem; }

/* ── Boutons ── */
.confirmation-actions {
    display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
    margin-top: 2rem;
}
.btn-conf {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: 12px 24px; border-radius: 12px; font-size: .9rem; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: all .2s; border: none;
}
.btn-conf-primary {
    background: linear-gradient(135deg, #173252, #c43d2f); color: #fff;
}
.btn-conf-primary:hover { opacity: .9; transform: translateY(-1px); }
.btn-conf-outline {
    background: transparent; border: 1px solid var(--sg-border, #e2e8f0);
    color: var(--sg-text, #334155);
}
.btn-conf-outline:hover { border-color: var(--sg-primary, #173252); color: var(--sg-primary, #173252); }
.btn-conf-success {
    background: #059669; color: #fff;
}
.btn-conf-success:hover { background: #047857; }
</style>

<div class="confirmation-wrap">
    <!-- Animation checkmark -->
    <div class="checkmark-circle">
        <svg viewBox="0 0 100 100">
            <circle class="circle" cx="50" cy="50" r="45"/>
            <polyline class="check" points="30,52 44,66 72,36"/>
        </svg>
    </div>

    <h1 class="confirmation-title">Commande confirmée !</h1>

    <?php if (!empty($commande)): ?>
        <div class="confirmation-order-id">
            Commande #<?php echo (int)$commande['id_commande']; ?>
        </div>

        <p class="confirmation-sms">
            <i class="bi bi-chat-dots-fill"></i>
            Un SMS de confirmation a été envoyé au
            <strong><?php echo htmlspecialchars($commande['telephone'] ?? ($lastOrder['telephone'] ?? '')); ?></strong>
        </p>

        <!-- Tableau récapitulatif -->
        <?php if (!empty($commande['items'])): ?>
        <div class="confirmation-table-wrap">
            <table class="confirmation-table">
                <thead>
                    <tr>
                        <th>Pièce</th>
                        <th>Marque</th>
                        <th style="text-align:center;">Qté</th>
                        <th style="text-align:right;">Prix unit.</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commande['items'] as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['nom'] ?? ''); ?></strong><br>
                            <small style="color:var(--sg-text-muted,#94a3b8);"><?php echo htmlspecialchars($item['reference'] ?? ''); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($item['marque'] ?? ''); ?></td>
                        <td style="text-align:center;"><?php echo (int)$item['quantite']; ?></td>
                        <td style="text-align:right;"><?php echo number_format((float)$item['prix_unitaire'], 2, ',', ' '); ?> DT</td>
                        <td style="text-align:right;font-weight:600;"><?php echo number_format((float)$item['sous_total'], 2, ',', ' '); ?> DT</td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Sous-totaux -->
                    <tr>
                        <td colspan="4" style="text-align:right;color:var(--sg-text-muted,#94a3b8);">Sous-total HT</td>
                        <td style="text-align:right;"><?php echo number_format((float)($commande['montant_ht'] ?? $commande['montant_total'] ?? 0), 2, ',', ' '); ?> DT</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:right;color:var(--sg-text-muted,#94a3b8);">TVA (19%)</td>
                        <td style="text-align:right;"><?php echo number_format((float)($commande['tva'] ?? 0), 2, ',', ' '); ?> DT</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:right;color:var(--sg-text-muted,#94a3b8);">Livraison</td>
                        <td style="text-align:right;"><?php echo (float)($commande['frais_livraison'] ?? 0) > 0 ? number_format((float)$commande['frais_livraison'], 2, ',', ' ') . ' DT' : '<span style="color:#059669;">Gratuit</span>'; ?></td>
                    </tr>
                    <tr class="confirmation-total-row">
                        <td colspan="4" style="text-align:right;">Total TTC</td>
                        <td style="text-align:right;"><?php echo number_format((float)($commande['montant_ttc'] ?? $commande['montant_total'] ?? 0), 2, ',', ' '); ?> DT</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="confirmation-sub">Votre commande a été enregistrée avec succès.</p>
    <?php endif; ?>

    <div class="confirmation-actions">
        <?php if (!empty($commande)): ?>
        <a href="index.php?action=exportCommande&id=<?php echo (int)$commande['id_commande']; ?>" class="btn-conf btn-conf-success">
            <i class="bi bi-file-earmark-pdf"></i> Télécharger le bon PDF
        </a>
        <?php endif; ?>
        <a href="index.php?action=showCatalogue" class="btn-conf btn-conf-primary">
            <i class="bi bi-plus-circle"></i> Nouvelle commande
        </a>
        <a href="index.php?action=orderHistory" class="btn-conf btn-conf-outline">
            <i class="bi bi-clock-history"></i> Mes commandes
        </a>
    </div>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
