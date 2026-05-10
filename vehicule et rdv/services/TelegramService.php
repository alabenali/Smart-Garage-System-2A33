<?php

/**
 * Service de notification Telegram
 * Envoie des messages à l'admin via le Bot API Telegram
 * Non bloquant : ne casse jamais le flux principal
 */
class TelegramService
{
    /** @var string */
    private $botToken;

    /** @var string */
    private $chatId;

    /** @var string */
    private $logFile;

    /** @var array Types d'intervention considérés comme urgents */
    private $urgentInterventions = ['Moteur', 'Transmission', 'Embrayage'];

    /** @var int Seuil de score santé véhicule pour déclencher une alerte urgente */
    private $healthScoreThreshold = 30;

    public function __construct()
    {
        $configPath = __DIR__ . '/../config/telegram.php';
        $config = file_exists($configPath) ? (array) require $configPath : [];

        $this->botToken = (string) ($config['bot_token'] ?? '');
        $this->chatId   = (string) ($config['chat_id'] ?? '');
        $this->logFile  = __DIR__ . '/../logs/telegram.log';
    }

    /**
     * Envoie un message Telegram via Bot API
     * Timeout max 2 secondes, parse_mode HTML
     *
     * @param string $message Message HTML à envoyer
     * @return bool true si envoi réussi, false sinon
     */
    public function sendTelegramMessage(string $message): bool
    {
        if ($this->botToken === '' || $this->chatId === '') {
            $this->logError('Configuration manquante: TELEGRAM_BOT_TOKEN ou TELEGRAM_CHAT_ID absent');
            return false;
        }

        $url = 'https://api.telegram.org/bot' . $this->botToken . '/sendMessage';

        $postData = [
            'chat_id'    => $this->chatId,
            'text'       => $message,
            'parse_mode' => 'HTML',
        ];

        // Essai cURL en priorité
        if (function_exists('curl_init')) {
            return $this->sendViaCurl($url, $postData);
        }

        // Fallback file_get_contents
        return $this->sendViaFileGetContents($url, $postData);
    }

    /**
     * Wrapper sécurisé : envoie le message sans jamais bloquer le système
     * Log l'erreur en cas d'échec
     *
     * @param string $message Message HTML à envoyer
     * @return bool
     */
    public function sendTelegramSafe(string $message): bool
    {
        try {
            $result = $this->sendTelegramMessage($message);

            if (!$result) {
                $this->logError('Envoi échoué (retour false)');
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }

    /**
     * Construit et envoie la notification de nouveau RDV
     *
     * @param array $rdvData Données du rendez-vous (avec jointures véhicule/créneau)
     * @return bool
     */
    public function notifyNewRdv(array $rdvData): bool
    {
        $message = $this->buildRdvMessage($rdvData);
        return $this->sendTelegramSafe($message);
    }

    /**
     * Construit le message Telegram selon le contexte (normal ou urgent)
     *
     * @param array $rdvData
     * @return string Message formaté HTML
     */
    public function buildRdvMessage(array $rdvData): string
    {
        $nom            = trim(($rdvData['prenom_client'] ?? '') . ' ' . ($rdvData['nom_client'] ?? ''));
        $telephone      = $rdvData['telephone_client'] ?? 'N/A';
        $marque         = $rdvData['marque'] ?? 'N/A';
        $modele         = $rdvData['modele'] ?? 'N/A';
        $annee          = $rdvData['annee'] ?? 'N/A';
        $dateRdv        = isset($rdvData['date_heure']) ? date('d/m/Y à H:i', strtotime($rdvData['date_heure'])) : 'N/A';
        $typeInter      = $rdvData['type_intervention'] ?? 'N/A';
        $idVehicle      = isset($rdvData['id_vehicle']) ? (int) $rdvData['id_vehicle'] : 0;

        // Récupérer le score santé véhicule si possible
        $healthScore = null;
        if ($idVehicle > 0) {
            $healthScore = $this->getVehicleHealthScore($idVehicle);
        }

        // Déterminer si c'est urgent
        $isUrgent = $this->isUrgentRdv($typeInter, $healthScore);

        if ($isUrgent) {
            return $this->buildUrgentMessage($nom, $telephone, $marque, $modele, $annee, $dateRdv, $typeInter, $healthScore);
        }

        return $this->buildNormalMessage($nom, $telephone, $marque, $modele, $annee, $dateRdv, $typeInter);
    }

    /**
     * Vérifie si le RDV est urgent
     *
     * @param string $typeIntervention
     * @param int|null $healthScore
     * @return bool
     */
    private function isUrgentRdv(string $typeIntervention, ?int $healthScore): bool
    {
        if (in_array($typeIntervention, $this->urgentInterventions, true)) {
            return true;
        }

        if ($healthScore !== null && $healthScore < $this->healthScoreThreshold) {
            return true;
        }

        return false;
    }

    /**
     * Construit le message normal
     */
    private function buildNormalMessage(
        string $nom,
        string $telephone,
        string $marque,
        string $modele,
        string $annee,
        string $dateRdv,
        string $typeIntervention
    ): string {
        $lines = [];
        $lines[] = "🚗 <b>Nouveau RDV confirmé</b>";
        $lines[] = "";
        $lines[] = "👤 Client: " . $this->escapeHtml($nom ?: 'Non renseigné');
        $lines[] = "📞 Téléphone: " . $this->escapeHtml($telephone);
        $lines[] = "🚘 Véhicule: " . $this->escapeHtml($marque) . " " . $this->escapeHtml($modele) . " (" . $this->escapeHtml((string) $annee) . ")";
        $lines[] = "📅 Date: " . $this->escapeHtml($dateRdv);
        $lines[] = "🔧 Intervention: " . $this->escapeHtml($typeIntervention);

        return implode("\n", $lines);
    }

    /**
     * Construit le message urgent
     */
    private function buildUrgentMessage(
        string $nom,
        string $telephone,
        string $marque,
        string $modele,
        string $annee,
        string $dateRdv,
        string $typeIntervention,
        ?int $healthScore
    ): string {
        $lines = [];
        $lines[] = "⚠️ <b>RDV URGENT</b>";
        $lines[] = "";
        $lines[] = "🚘 Véhicule critique détecté";

        if ($healthScore !== null) {
            $lines[] = "📊 Score santé: " . $healthScore . "/100";
        }

        $lines[] = "";
        $lines[] = "👤 Client: " . $this->escapeHtml($nom ?: 'Non renseigné');
        $lines[] = "📞 Téléphone: " . $this->escapeHtml($telephone);
        $lines[] = "🚘 Véhicule: " . $this->escapeHtml($marque) . " " . $this->escapeHtml($modele) . " (" . $this->escapeHtml((string) $annee) . ")";
        $lines[] = "📅 RDV: " . $this->escapeHtml($dateRdv);
        $lines[] = "🔧 Intervention: " . $this->escapeHtml($typeIntervention);

        return implode("\n", $lines);
    }

    /**
     * Récupère le score santé du véhicule via l'API interne
     * Non bloquant : retourne null si l'API échoue
     *
     * @param int $idVehicle
     * @return int|null Score 0-100 ou null si indisponible
     */
    private function getVehicleHealthScore(int $idVehicle): ?int
    {
        try {
            $apiUrl = $this->getBaseUrl() . '/api/vehicle-health.php?id_vehicle=' . $idVehicle;

            $context = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 2,
                    'ignore_errors' => true,
                ],
            ]);

            $response = @file_get_contents($apiUrl, false, $context);
            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);
            if (is_array($data) && isset($data['score'])) {
                return (int) $data['score'];
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Détecte l'URL de base de l'application
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        return $scheme . '://' . $host . $scriptDir;
    }

    /**
     * Envoi via cURL avec timeout de 2 secondes
     *
     * @param string $url
     * @param array $postData
     * @return bool
     */
    private function sendViaCurl(string $url, array $postData): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            $this->logError('cURL error: ' . ($error ?: 'HTTP ' . $httpCode));
            return false;
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded) && isset($decoded['ok']) && $decoded['ok'] === true) {
            return true;
        }

        $this->logError('Telegram API response: ' . ($response ?: 'empty'));
        return false;
    }

    /**
     * Envoi via file_get_contents avec timeout de 2 secondes
     *
     * @param string $url
     * @param array $postData
     * @return bool
     */
    private function sendViaFileGetContents(string $url, array $postData): bool
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($postData),
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $this->logError('file_get_contents failed');
            return false;
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded) && isset($decoded['ok']) && $decoded['ok'] === true) {
            return true;
        }

        $this->logError('Telegram API response: ' . $response);
        return false;
    }

    /**
     * Échappe les caractères spéciaux pour le HTML Telegram
     *
     * @param string $text
     * @return string
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Log une erreur dans logs/telegram.log
     *
     * @param string $errorMessage
     * @return void
     */
    private function logError(string $errorMessage): void
    {
        try {
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            $logLine = '[' . date('Y-m-d H:i:s') . '] ERROR Telegram failed: ' . $errorMessage . PHP_EOL;
            @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Ne jamais bloquer le système
        }
    }
}
