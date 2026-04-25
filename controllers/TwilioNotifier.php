<?php

class TwilioNotifier
{
    private $accountSid;
    private $authToken;
    private $fromNumber;
    private $brandName;

    public function __construct()
    {
        $this->accountSid = $this->env('TWILIO_ACCOUNT_SID', '');
        $this->authToken = $this->env('TWILIO_AUTH_TOKEN', '');
        $this->fromNumber = $this->env('TWILIO_FROM_NUMBER', '');
        $this->brandName = $this->env('SMART_GARAGE_BRAND', 'SmartGarage');
    }

    public function sendOrderConfirmation(array $orderData)
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $to = $this->normalizePhoneNumber(
            isset($orderData['telephone']) ? (string) $orderData['telephone'] : ''
        );

        if ($to === null) {
            return false;
        }

        try {
            return $this->sendSms($to, $this->buildOrderMessage($orderData));
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isConfigured()
    {
        return $this->accountSid !== ''
            && $this->authToken !== ''
            && $this->fromNumber !== '';
    }

    private function buildOrderMessage(array $orderData)
    {
        $prenom = trim((string) ($orderData['prenom_client'] ?? ''));
        $nom = trim((string) ($orderData['nom_client'] ?? ''));
        $clientName = trim($prenom . ' ' . $nom);
        $pieceName = trim((string) ($orderData['piece_nom'] ?? 'votre piece'));
        $quantity = max(1, (int) ($orderData['quantite'] ?? 1));
        $amount = number_format((float) ($orderData['montant_total'] ?? 0), 2, ',', ' ');

        return sprintf(
            '%s: Bonjour %s, votre commande de %d x %s a ete confirmee. Montant total: %s DT. Merci pour votre confiance.',
            $this->brandName,
            $clientName !== '' ? $clientName : 'client',
            $quantity,
            $pieceName,
            $amount
        );
    }

    private function normalizePhoneNumber($phone)
    {
        $cleaned = preg_replace('/[^\d\+]/', '', $phone);
        if ($cleaned === null || $cleaned === '') {
            return null;
        }

        if (strpos($cleaned, '+') === 0) {
            return $cleaned;
        }

        if (strpos($cleaned, '00') === 0) {
            return '+' . substr($cleaned, 2);
        }

        if (preg_match('/^\d{8}$/', $cleaned)) {
            return '+216' . $cleaned;
        }

        return '+' . $cleaned;
    }

    private function sendSms($to, $messageBody)
    {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($this->accountSid) . '/Messages.json';
        $postFields = http_build_query([
            'To' => $to,
            'From' => $this->fromNumber,
            'Body' => $messageBody,
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ':' . $this->authToken);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);

            $response = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return $response !== false && $statusCode >= 200 && $statusCode < 300;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Authorization: Basic ' . base64_encode($this->accountSid . ':' . $this->authToken),
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($postFields),
                ]),
                'content' => $postFields,
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCode(isset($http_response_header) ? $http_response_header : []);

        return $response !== false && $statusCode >= 200 && $statusCode < 300;
    }

    private function extractStatusCode(array $headers)
    {
        if (!isset($headers[0])) {
            return 0;
        }

        if (preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function env($key, $default = '')
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}
