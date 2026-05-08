<?php
// ============================================
// Cron : Envoi automatique des alertes garantie par SMS Twilio
// ============================================
// Crontab recommandé :
// 0 9 * * * php /chemin/vers/cron/send_garantie_alerts.php >> /chemin/vers/logs/cron.log 2>&1
// ============================================

define('CRON_MODE', true);

// Charger la config et le modèle
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Garantie.php';

// ── Charger les variables d'environnement ──
$envPath = __DIR__ . '/../.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv($k . '=' . $v);
        }
    }
}

// ── Config Twilio ──
$twilioSid   = getenv('TWILIO_ACCOUNT_SID') ?: '';
$twilioToken = getenv('TWILIO_AUTH_TOKEN') ?: '';
$twilioFrom  = getenv('TWILIO_FROM_NUMBER') ?: '';
$garagePhone = getenv('SMART_GARAGE_PHONE') ?: '71 234 567';
$brandName   = getenv('SMART_GARAGE_BRAND') ?: 'SmartGarage';

// ── Construire le message SMS selon le type d'alerte ──
function buildSmsMessage(array $garantie, string $garagePhone, string $brandName): string
{
    $nom    = trim($garantie['nom_complet'] ?? $garantie['nom_client'] ?? 'Client');
    $marque = $garantie['marque_piece'] ?? '';
    $piece  = $garantie['nom_piece'] ?? 'votre pièce';
    $jours  = (int) ($garantie['jours_restants'] ?? 0);
    $dateExp = !empty($garantie['date_expiration'])
        ? date('d/m/Y', strtotime($garantie['date_expiration']))
        : 'prochainement';

    switch ($garantie['type_alerte'] ?? '') {
        case 'ALERTE_30J':
            return sprintf(
                '%s: Bonjour %s, votre garantie %s %s expire dans %d jours (le %s). Pour tout controle, contactez Smart Garage au %s.',
                $brandName, $nom, $marque, $piece, $jours, $dateExp, $garagePhone
            );

        case 'ALERTE_7J':
            return sprintf(
                '%s: Rappel urgent - Votre garantie %s %s expire dans %d jours. Appelez Smart Garage pour un controle preventif au %s.',
                $brandName, $marque, $piece, $jours, $garagePhone
            );

        case 'EXPIREE':
            return sprintf(
                '%s: Votre garantie %s %s a expire le %s. Contactez Smart Garage au %s pour planifier un entretien ou renouveler votre protection.',
                $brandName, $marque, $piece, $dateExp, $garagePhone
            );

        default:
            return sprintf('%s: Alerte garantie %s %s. Contactez Smart Garage au %s.', $brandName, $marque, $piece, $garagePhone);
    }
}

// ── Envoyer un SMS via l'API Twilio ──
function sendTwilioSms(string $to, string $body, string $sid, string $token, string $from): bool
{
    if ($sid === '' || $token === '' || $from === '') {
        return false;
    }

    // Normaliser le numéro (format tunisien)
    $cleaned = preg_replace('/[^\d+]/', '', $to);
    if (strpos($cleaned, '0') === 0) {
        $cleaned = '+216' . substr($cleaned, 1);
    } elseif (strpos($cleaned, '+') !== 0) {
        if (preg_match('/^\d{8}$/', $cleaned)) {
            $cleaned = '+216' . $cleaned;
        } else {
            $cleaned = '+' . $cleaned;
        }
    }

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';
    $postFields = http_build_query([
        'To'   => $cleaned,
        'From' => $from,
        'Body' => $body,
    ]);

    if (!function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response   = curl_exec($ch);
    $httpCode   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return $response !== false && $httpCode === 201;
}

// ══════════════════════════════════════════
// BOUCLE PRINCIPALE
// ══════════════════════════════════════════

$model = new Garantie();

$startTime = microtime(true);
$envoyees  = 0;
$echecs    = 0;
$details   = [];

echo "[" . date('Y-m-d H:i:s') . "] Démarrage envoi alertes garantie...\n";

// 1. Récupérer les alertes à envoyer
$alertes = $model->getAlertesToSend();
echo "  → " . count($alertes) . " alerte(s) à traiter.\n";

// 2. Envoyer chaque alerte
foreach ($alertes as $alerte) {
    $telephone = $alerte['telephone'] ?? '';
    $type      = $alerte['type_alerte'] ?? 'INCONNU';
    $nomPiece  = ($alerte['marque_piece'] ?? '') . ' ' . ($alerte['nom_piece'] ?? '');
    $nomClient = $alerte['nom_complet'] ?? 'Client';

    $message = buildSmsMessage($alerte, $garagePhone, $brandName);

    $logLine = sprintf(
        '  [%s] #%d %s → %s (%s)',
        $type,
        $alerte['id_garantie'] ?? 0,
        trim($nomPiece),
        $nomClient,
        $telephone
    );

    if ($telephone === '') {
        $logLine .= ' ❌ Pas de téléphone';
        $echecs++;
        $details[] = $logLine;
        echo $logLine . "\n";
        continue;
    }

    $sent = sendTwilioSms($telephone, $message, $twilioSid, $twilioToken, $twilioFrom);

    if ($sent) {
        $model->markAlertSent((int) $alerte['id_garantie'], $type);
        $logLine .= ' ✅ Envoyé';
        $envoyees++;
    } else {
        $logLine .= ' ❌ Échec envoi';
        $echecs++;
    }

    $details[] = $logLine;
    echo $logLine . "\n";
}

// 3. Expirer les vieilles garanties
$expired = $model->expireOldGaranties();
echo "  → " . $expired . " garantie(s) marquée(s) expirée(s).\n";

// 4. Résumé
$elapsed = round(microtime(true) - $startTime, 2);
$summary = sprintf(
    "[%s] Terminé en %ss — Envoyées: %d | Échecs: %d | Expirées: %d | Total alertes: %d",
    date('Y-m-d H:i:s'),
    $elapsed,
    $envoyees,
    $echecs,
    $expired,
    count($alertes)
);
echo $summary . "\n";

// 5. Écrire dans le fichier de log
$logDir  = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/garanties_' . date('Y-m') . '.log';
$logContent = $summary . "\n";
foreach ($details as $line) {
    $logContent .= $line . "\n";
}
$logContent .= str_repeat('-', 60) . "\n";

file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

echo "  → Log écrit dans: " . basename($logFile) . "\n";
