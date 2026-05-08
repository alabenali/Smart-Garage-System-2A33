<?php
require_once __DIR__ . '/../config.php';

class ChatbotController {

    public function chat(): void {
        header('Content-Type: application/json; charset=utf-8');

        $input   = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $history = $input['history'] ?? [];

        if (empty($message)) {
            echo json_encode(['reply' => 'Message vide.']);
            exit;
        }

        $reply = $this->callGroq($message, $history);
        echo json_encode(['reply' => $reply ?: "Désolé, je n'ai pas pu répondre. Réessayez."]);
        exit;
    }

    private function callGroq(string $message, array $history): ?string {
        $system = <<<SYS
Tu es AutoBot, un assistant virtuel intelligent et sympathique du garage "Smart Garage".
Tu réponds dans la langue du client (français, anglais, arabe, etc.).

Tu es un assistant universel qui répond à TOUTES les questions sans exception :
- Voitures, mécanique, entretien, prix, pannes, conseils auto 🚗
- Sciences, mathématiques, histoire, géographie
- Informatique, programmation, technologie
- Cuisine, recettes, santé, sport
- Culture générale, actualités, divertissement
- Traduction, rédaction, résumés
- Maths et calculs
- Et absolument n'importe quelle autre question !

Règles :
- Réponds toujours de façon utile, claire et amicale
- Utilise des emojis avec modération pour être sympa
- Sois concis (3-4 phrases) sauf si la question nécessite plus de détails
- Si tu ne sais pas quelque chose, dis-le honnêtement
- Tu peux faire des blagues légères si le contexte s'y prête
- Mets en valeur les services du garage Smart Garage quand c'est pertinent
SYS;

        // Construire l'historique
        $messages = [['role' => 'system', 'content' => $system]];
        foreach (array_slice($history, -6) as $h) {
            if (!empty($h['role']) && !empty($h['content'])) {
                $messages[] = ['role' => $h['role'], 'content' => $h['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = [
            'model'       => 'llama-3.3-70b-versatile',
            'messages'    => $messages,
            'max_tokens'  => 300,
            'temperature' => 0.7,
        ];

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GROQ_API_KEY,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $code !== 200) return null;
        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }
}

$ctrl   = new ChatbotController();
$action = $_GET['action'] ?? 'chat';
if ($action === 'chat') $ctrl->chat();
