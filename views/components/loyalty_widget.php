<?php
$widget = $loyaltyWidget ?? [];
$progression = $widget['progression'] ?? [];
$account = $widget['account'] ?? ($progression['account'] ?? []);
$history = $widget['historique'] ?? [];
$currentPalier = $progression['palier'] ?? [];
$nextPalier = $progression['prochain_palier'] ?? null;
$pointsGagnes = max(0, (int) ($widget['points_gagnes'] ?? 0));
$pointsDisponibles = max(0, (int) ($account['points_restants'] ?? ($progression['points_actuels'] ?? 0)));
$palierNom = (string) ($progression['palier_actuel'] ?? ($account['palier_actuel'] ?? 'Bronze'));
$palierColor = (string) ($currentPalier['couleur_hex'] ?? '#E85D04');
$palierIcon = (string) ($currentPalier['icone'] ?? '★');
$nextName = $nextPalier ? (string) $nextPalier['nom'] : 'Platinum';
$nextColor = $nextPalier ? (string) ($nextPalier['couleur_hex'] ?? '#E85D04') : '#E85D04';
$nextAdvantage = $nextPalier ? (string) ($nextPalier['avantage_desc'] ?? '') : 'Tous les avantages sont débloqués';
$progressPct = max(0, min(100, (int) ($progression['progression_pct'] ?? 0)));
$pointsRequired = (int) ($progression['points_requis_prochain'] ?? $pointsDisponibles);
$pointsMissing = max(0, (int) ($progression['points_manquants'] ?? 0));
$rewards = [
    ['id' => 1, 'label' => 'Diagnostic offert', 'points' => 120],
    ['id' => 2, 'label' => 'Vidange offerte', 'points' => 200],
    ['id' => 3, 'label' => 'Pack contrôle sécurité', 'points' => 350],
];
?>

<?php if (!empty($account)): ?>
    <?php if (empty($GLOBALS['LOYALTY_WIDGET_STYLE_PRINTED'])): ?>
        <?php $GLOBALS['LOYALTY_WIDGET_STYLE_PRINTED'] = true; ?>
        <style>
            .loyalty-widget{margin:1.25rem 0;padding:1rem;border:1px solid #f1dfd2;background:#fff;border-radius:8px;box-shadow:0 10px 28px rgba(33,37,41,.08)}
            .loyalty-header{display:flex;align-items:center;gap:.85rem;border-left:5px solid #E85D04;padding:.75rem;background:#fff7f0;border-radius:8px}
            .palier-icon{font-size:2rem;line-height:1}
            .palier-nom{font-weight:800;color:#212529}
            .points-total{font-size:.92rem;color:#6c757d}
            .points-gagnes-badge{margin-left:auto;background:#E85D04;color:#fff;border-radius:999px;padding:.35rem .7rem;font-weight:800;animation:loyaltyFadeIn .7s ease both}
            .progression-label,.progression-hint{font-size:.9rem;color:#495057;margin-top:.8rem}
            .loyalty-widget .progress{background:#f4e6db;border-radius:999px;overflow:hidden}
            .loyalty-widget .progress-bar{transition:width 900ms ease;animation:loyaltyGrow .9s ease both}
            .loyalty-history,.rewards-section{margin-top:1rem;border-top:1px solid #f1dfd2;padding-top:.85rem}
            .history-title{font-weight:800;margin-bottom:.5rem;color:#343a40}
            .history-item,.reward-item{display:flex;align-items:center;gap:.75rem;padding:.45rem 0;border-bottom:1px dashed #f1dfd2}
            .h-date{width:86px;color:#868e96;font-size:.84rem}
            .h-desc{flex:1;color:#343a40}
            .h-points{font-weight:800;white-space:nowrap}
            .h-points.gain{color:#198754}
            .h-points.perte{color:#dc3545}
            .reward-item span:first-child{flex:1}
            .btn-redeem{border:0;background:#E85D04;color:#fff;border-radius:6px;padding:.35rem .65rem;font-weight:700}
            .btn-redeem:disabled{background:#adb5bd;cursor:not-allowed}
            @keyframes loyaltyFadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
            @keyframes loyaltyGrow{from{width:0}to{width:var(--loyalty-width)}}
            @media (max-width:576px){.loyalty-header,.history-item,.reward-item{align-items:flex-start}.loyalty-header{flex-wrap:wrap}.points-gagnes-badge{margin-left:0}.h-date{width:auto}.history-item{flex-wrap:wrap}}
        </style>
    <?php endif; ?>

    <div class="loyalty-widget">
        <div class="loyalty-header" style="border-color: <?php echo htmlspecialchars($palierColor); ?>">
            <span class="palier-icon"><?php echo htmlspecialchars($palierIcon); ?></span>
            <div>
                <div class="palier-nom"><?php echo htmlspecialchars($palierNom); ?></div>
                <div class="points-total"><?php echo number_format($pointsDisponibles, 0, ',', ' '); ?> points disponibles</div>
            </div>
            <?php if ($pointsGagnes > 0): ?>
                <div class="points-gagnes-badge">+<?php echo $pointsGagnes; ?> ce RDV</div>
            <?php endif; ?>
        </div>

        <div class="progression-label">
            Progression vers <?php echo htmlspecialchars($nextName); ?> :
            <?php echo number_format($pointsDisponibles, 0, ',', ' '); ?>/<?php echo number_format($pointsRequired, 0, ',', ' '); ?>
        </div>
        <div class="progress" style="height:8px">
            <div class="progress-bar" style="--loyalty-width: <?php echo $progressPct; ?>%; width: <?php echo $progressPct; ?>%; background: <?php echo htmlspecialchars($nextColor); ?>"></div>
        </div>
        <div class="progression-hint">
            <?php if ($pointsMissing > 0): ?>
                Plus que <?php echo number_format($pointsMissing, 0, ',', ' '); ?> points → <?php echo htmlspecialchars($nextAdvantage); ?>
            <?php else: ?>
                <?php echo htmlspecialchars($nextAdvantage); ?>
            <?php endif; ?>
        </div>

        <div class="loyalty-history">
            <div class="history-title">Historique récent</div>
            <?php if (!empty($history)): ?>
                <?php foreach ($history as $item): ?>
                    <?php
                    $isGain = in_array((string) ($item['type'] ?? ''), ['gain', 'bonus'], true);
                    $signedPoints = ($isGain ? '+' : '-') . (int) ($item['points'] ?? 0) . ' pts';
                    ?>
                    <div class="history-item">
                        <span class="h-date"><?php echo htmlspecialchars(date('d/m/Y', strtotime((string) $item['date_transaction']))); ?></span>
                        <span class="h-desc"><?php echo htmlspecialchars((string) ($item['description'] ?? '-')); ?></span>
                        <span class="h-points <?php echo $isGain ? 'gain' : 'perte'; ?>"><?php echo htmlspecialchars($signedPoints); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="history-item"><span class="h-desc">Aucune transaction pour le moment.</span></div>
            <?php endif; ?>
        </div>

        <div class="rewards-section">
            <?php foreach ($rewards as $reward): ?>
                <?php if ($pointsDisponibles >= $reward['points']): ?>
                    <div class="reward-item">
                        <span><?php echo htmlspecialchars($reward['label']); ?></span>
                        <span><?php echo (int) $reward['points']; ?> pts</span>
                        <button class="btn-redeem" data-reward-id="<?php echo (int) $reward['id']; ?>" data-loyalty-id="<?php echo (int) $account['id']; ?>" data-points="<?php echo (int) $reward['points']; ?>">Utiliser</button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
