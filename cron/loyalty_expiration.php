<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/loyalty_rules.php';
require_once __DIR__ . '/../services/LoyaltyService.php';

function loyaltyCronLog(string $message): void
{
    @file_put_contents(__DIR__ . '/../logs/loyalty.log', '[' . date('Y-m-d H:i:s') . '] CRON ' . $message . PHP_EOL, FILE_APPEND);
}

function sendExpirationWarning(array $account, string $expirationDate): bool
{
    $email = trim((string) ($account['client_email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $nom = trim((string) ($account['client_nom'] ?? ''));
    $prenom = trim((string) ($account['client_prenom'] ?? ''));
    $civilite = $nom !== '' ? 'M. ' . $nom : trim($prenom . ' ' . $nom);
    if ($civilite === '') {
        $civilite = 'Cher client';
    }

    $points = max(0, (int) ($account['points_restants'] ?? 0));
    $dateLabel = date('d/m/Y', strtotime($expirationDate));
    $subject = 'Vos points Smart Garage expirent dans 30 jours';
    $body = $civilite . ', vos ' . $points . ' points expirent le ' . $dateLabel . ".\n"
        . 'Prenez un RDV avant cette date pour les conserver.';

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: Smart Garage <no-reply@smart-garage.local>',
    ];

    return @mail($email, $subject, $body, implode("\r\n", $headers));
}

try {
    $pdo = Database::getInstance()->getConnection();
    $loyalty = new LoyaltyService($pdo);
    $months = max(1, (int) LOYALTY_RULES['expiration_mois']);

    $warningSql = 'SELECT *, DATE_ADD(COALESCE(derniere_activite, date_inscription), INTERVAL ' . $months . ' MONTH) AS date_expiration
                   FROM loyalty_account
                   WHERE points_restants > 0
                     AND DATEDIFF(DATE_ADD(COALESCE(derniere_activite, date_inscription), INTERVAL ' . $months . ' MONTH), CURDATE()) BETWEEN 1 AND 30';
    $warnings = $pdo->query($warningSql)->fetchAll(PDO::FETCH_ASSOC);

    $sent = 0;
    foreach ($warnings as $account) {
        if (sendExpirationWarning($account, (string) $account['date_expiration'])) {
            $sent++;
        }
    }

    $expired = $loyalty->expirePointsInactifs();
    loyaltyCronLog('warnings=' . $sent . ' expired_accounts=' . $expired);

    echo 'Loyalty expiration done. Warnings: ' . $sent . '. Expired accounts: ' . $expired . PHP_EOL;
} catch (Throwable $e) {
    loyaltyCronLog('error=' . $e->getMessage());
    echo 'Loyalty expiration failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
