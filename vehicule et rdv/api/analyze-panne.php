<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function lowerUtf8(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }

    return strtolower($value);
}

function normalizePanneText(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = lowerUtf8($text);
    $text = strtr($text, [
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'á' => 'a',
        'ã' => 'a',
        'å' => 'a',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'ö' => 'o',
        'õ' => 'o',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ý' => 'y',
        'ÿ' => 'y',
        'œ' => 'oe',
        'æ' => 'ae',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

    return trim($text);
}

function keywordMatches(string $normalizedText, string $keyword): bool
{
    $normalizedKeyword = normalizePanneText($keyword);
    if ($normalizedKeyword === '') {
        return false;
    }

    $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($normalizedKeyword, '/') . '(?![\p{L}\p{N}])/u';

    return preg_match($pattern, $normalizedText) === 1;
}

function confidenceFromScore(int $score, string $type): string
{
    if ($score >= 3 && $type !== 'Diagnostic général') {
        return 'high';
    }

    if ($score >= 2 && $type !== 'Diagnostic général') {
        return 'medium';
    }

    return 'low';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$rawBody = file_get_contents('php://input') ?: '';
$payload = [];

if (trim($rawBody) !== '') {
    $decoded = json_decode($rawBody, true);
    if (!is_array($decoded)) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }
    $payload = $decoded;
} elseif (!empty($_POST)) {
    $payload = $_POST;
}

$description = isset($payload['description']) ? (string) $payload['description'] : '';
$normalizedDescription = normalizePanneText($description);

$dictionary = [
    'Vidange' => [
        ['term' => 'vidange', 'weight' => 4],
        ['term' => 'huile', 'weight' => 2],
        ['term' => 'huile moteur', 'weight' => 3],
        ['term' => 'filtre huile', 'weight' => 3],
        ['term' => 'filtre à huile', 'weight' => 3],
        ['term' => 'filtre air', 'weight' => 2],
        ['term' => 'filtre à air', 'weight' => 2],
        ['term' => 'filtre carburant', 'weight' => 2],
        ['term' => 'niveau huile', 'weight' => 2],
        ['term' => 'entretien huile', 'weight' => 3],
        ['term' => 'zitoula', 'weight' => 3],
        ['term' => 'zit moteur', 'weight' => 3],
        ['term' => 'زيت', 'weight' => 2],
        ['term' => 'زيت موتور', 'weight' => 3],
        ['term' => 'فيلتر', 'weight' => 2],
    ],
    'Révision' => [
        ['term' => 'révision', 'weight' => 4],
        ['term' => 'revision', 'weight' => 4],
        ['term' => 'entretien', 'weight' => 3],
        ['term' => 'contrôle général', 'weight' => 3],
        ['term' => 'controle general', 'weight' => 3],
        ['term' => 'check up', 'weight' => 3],
        ['term' => 'avant voyage', 'weight' => 2],
        ['term' => 'service', 'weight' => 2],
        ['term' => 'entretien périodique', 'weight' => 3],
        ['term' => 'control', 'weight' => 2],
        ['term' => 'kontrol', 'weight' => 2],
        ['term' => 'كونترول', 'weight' => 2],
        ['term' => 'صيانة', 'weight' => 3],
    ],
    'Changement de pneu' => [
        ['term' => 'changement de pneu', 'weight' => 4],
        ['term' => 'changer pneu', 'weight' => 4],
        ['term' => 'remplacer pneu', 'weight' => 4],
        ['term' => 'pneu crevé', 'weight' => 4],
        ['term' => 'pneu creve', 'weight' => 4],
        ['term' => 'crevaison', 'weight' => 3],
        ['term' => 'roue crevée', 'weight' => 3],
        ['term' => 'roue crevee', 'weight' => 3],
        ['term' => 'pneu éclaté', 'weight' => 3],
        ['term' => 'pneu eclate', 'weight' => 3],
        ['term' => 'عجلة مثقوبة', 'weight' => 4],
        ['term' => 'عجلة مخرومة', 'weight' => 4],
        ['term' => 'بدل عجلة', 'weight' => 4],
    ],
    'Pneumatiques' => [
        ['term' => 'pneu', 'weight' => 2],
        ['term' => 'pneus', 'weight' => 2],
        ['term' => 'pneumatique', 'weight' => 3],
        ['term' => 'pneumatiques', 'weight' => 3],
        ['term' => 'roue', 'weight' => 2],
        ['term' => 'roues', 'weight' => 2],
        ['term' => 'pression pneu', 'weight' => 3],
        ['term' => 'pression pneus', 'weight' => 3],
        ['term' => 'équilibrage', 'weight' => 3],
        ['term' => 'equilibrage', 'weight' => 3],
        ['term' => 'parallélisme', 'weight' => 3],
        ['term' => 'parallelisme', 'weight' => 3],
        ['term' => 'jante', 'weight' => 2],
        ['term' => 'ajla', 'weight' => 3],
        ['term' => 'عجلة', 'weight' => 3],
        ['term' => 'عجلات', 'weight' => 3],
    ],
    'Batterie' => [
        ['term' => 'batterie', 'weight' => 4],
        ['term' => 'batterie faible', 'weight' => 4],
        ['term' => 'batterie déchargée', 'weight' => 4],
        ['term' => 'batterie dechargee', 'weight' => 4],
        ['term' => 'cosses batterie', 'weight' => 3],
        ['term' => 'alternateur', 'weight' => 2],
        ['term' => 'ne démarre pas', 'weight' => 2],
        ['term' => 'ne demarre pas', 'weight' => 2],
        ['term' => 'batri', 'weight' => 4],
        ['term' => 'batteria', 'weight' => 4],
        ['term' => 'باطرية', 'weight' => 4],
        ['term' => 'بطارية', 'weight' => 4],
        ['term' => 'ما تشعلش', 'weight' => 2],
    ],
    'Freinage' => [
        ['term' => 'frein', 'weight' => 3],
        ['term' => 'freins', 'weight' => 3],
        ['term' => 'freinage', 'weight' => 3],
        ['term' => 'plaquette', 'weight' => 3],
        ['term' => 'plaquettes', 'weight' => 3],
        ['term' => 'disque', 'weight' => 2],
        ['term' => 'disques', 'weight' => 2],
        ['term' => 'liquide de frein', 'weight' => 3],
        ['term' => 'pédale', 'weight' => 1],
        ['term' => 'sifflement', 'weight' => 2],
        ['term' => 'siffle', 'weight' => 2],
        ['term' => 'grince', 'weight' => 2],
        ['term' => 'grincement', 'weight' => 2],
        ['term' => 'abs', 'weight' => 2],
        ['term' => 'brimb', 'weight' => 3],
        ['term' => 'brimba', 'weight' => 3],
        ['term' => 'fran', 'weight' => 3],
        ['term' => 'frinat', 'weight' => 3],
        ['term' => 'فران', 'weight' => 3],
        ['term' => 'فرانات', 'weight' => 3],
        ['term' => 'بريمب', 'weight' => 3],
        ['term' => 'صفير', 'weight' => 2],
    ],
    'Moteur' => [
        ['term' => 'moteur', 'weight' => 3],
        ['term' => 'voyant moteur', 'weight' => 4],
        ['term' => 'surchauffe', 'weight' => 3],
        ['term' => 'chauffe', 'weight' => 2],
        ['term' => 'fumée', 'weight' => 2],
        ['term' => 'huile moteur', 'weight' => 3],
        ['term' => 'injecteur', 'weight' => 3],
        ['term' => 'bougie', 'weight' => 2],
        ['term' => 'ralenti', 'weight' => 2],
        ['term' => 'calage', 'weight' => 2],
        ['term' => 'démarrage difficile', 'weight' => 2],
        ['term' => 'moteur tremble', 'weight' => 3],
        ['term' => 'motor', 'weight' => 3],
        ['term' => 'moteur yskhen', 'weight' => 3],
        ['term' => 'ma ych3alch', 'weight' => 2],
        ['term' => 'ماكينة', 'weight' => 3],
        ['term' => 'موتور', 'weight' => 3],
        ['term' => 'دخان', 'weight' => 2],
        ['term' => 'يسخن', 'weight' => 2],
    ],
    'Électronique' => [
        ['term' => 'batterie', 'weight' => 3],
        ['term' => 'alternateur', 'weight' => 3],
        ['term' => 'démarreur', 'weight' => 3],
        ['term' => 'fusible', 'weight' => 2],
        ['term' => 'capteur', 'weight' => 2],
        ['term' => 'voyant', 'weight' => 1],
        ['term' => 'tableau de bord', 'weight' => 2],
        ['term' => 'électrique', 'weight' => 3],
        ['term' => 'court circuit', 'weight' => 3],
        ['term' => 'feux', 'weight' => 2],
        ['term' => 'phare ne marche pas', 'weight' => 2],
        ['term' => 'batri', 'weight' => 3],
        ['term' => 'batteria', 'weight' => 3],
        ['term' => 'démarreur maydourch', 'weight' => 3],
        ['term' => 'باطرية', 'weight' => 3],
        ['term' => 'كهرباء', 'weight' => 3],
        ['term' => 'فيوز', 'weight' => 2],
    ],
    'Climatisation' => [
        ['term' => 'climatisation', 'weight' => 3],
        ['term' => 'clim', 'weight' => 3],
        ['term' => 'climatiseur', 'weight' => 3],
        ['term' => 'compresseur', 'weight' => 3],
        ['term' => 'gaz clim', 'weight' => 3],
        ['term' => 'ne refroidit pas', 'weight' => 3],
        ['term' => 'air chaud', 'weight' => 2],
        ['term' => 'ventilation', 'weight' => 2],
        ['term' => 'froid', 'weight' => 1],
        ['term' => 'klim', 'weight' => 3],
        ['term' => 'klem', 'weight' => 3],
        ['term' => 'klimatizor', 'weight' => 3],
        ['term' => 'كليم', 'weight' => 3],
        ['term' => 'كليماتيزور', 'weight' => 3],
        ['term' => 'بارد', 'weight' => 1],
    ],
    'Carrosserie' => [
        ['term' => 'carrosserie', 'weight' => 3],
        ['term' => 'choc', 'weight' => 3],
        ['term' => 'accident', 'weight' => 3],
        ['term' => 'rayure', 'weight' => 2],
        ['term' => 'bosse', 'weight' => 2],
        ['term' => 'portière', 'weight' => 2],
        ['term' => 'pare chocs', 'weight' => 3],
        ['term' => 'aile', 'weight' => 2],
        ['term' => 'peinture', 'weight' => 2],
        ['term' => 'vitre cassée', 'weight' => 3],
        ['term' => 'phare cassé', 'weight' => 2],
        ['term' => 'karosri', 'weight' => 3],
        ['term' => 'sbigha', 'weight' => 2],
        ['term' => 'كاروسري', 'weight' => 3],
        ['term' => 'صبغة', 'weight' => 2],
        ['term' => 'ضربة', 'weight' => 2],
    ],
    'Transmission' => [
        ['term' => 'boîte de vitesse', 'weight' => 4],
        ['term' => 'boite de vitesse', 'weight' => 4],
        ['term' => 'embrayage', 'weight' => 3],
        ['term' => 'vitesse', 'weight' => 2],
        ['term' => 'vitesses', 'weight' => 2],
        ['term' => 'rapport', 'weight' => 2],
        ['term' => 'cardan', 'weight' => 3],
        ['term' => 'transmission', 'weight' => 3],
        ['term' => 'patine', 'weight' => 2],
        ['term' => 'huile boîte', 'weight' => 3],
        ['term' => 'clutch', 'weight' => 3],
        ['term' => 'vitas', 'weight' => 3],
        ['term' => 'boita', 'weight' => 3],
        ['term' => 'كلتش', 'weight' => 3],
        ['term' => 'فيتاس', 'weight' => 3],
        ['term' => 'بوطة', 'weight' => 3],
    ],
    'Diagnostic général' => [
        ['term' => 'bruit', 'weight' => 1],
        ['term' => 'vibration', 'weight' => 1],
        ['term' => 'odeur', 'weight' => 1],
        ['term' => 'fuite', 'weight' => 1],
        ['term' => 'panne', 'weight' => 1],
        ['term' => 'problème', 'weight' => 1],
        ['term' => 'ne marche pas', 'weight' => 1],
        ['term' => 'ma ykhdemch', 'weight' => 1],
        ['term' => 'مش يخدم', 'weight' => 1],
        ['term' => 'عطل', 'weight' => 1],
    ],
];

$bestType = 'Diagnostic général';
$bestScore = 0;
$bestKeywords = [];

foreach ($dictionary as $type => $keywords) {
    $score = 0;
    $found = [];
    $seenNormalizedTerms = [];

    foreach ($keywords as $keyword) {
        $normalizedKeyword = normalizePanneText($keyword['term']);
        if ($normalizedKeyword === '' || isset($seenNormalizedTerms[$normalizedKeyword])) {
            continue;
        }
        $seenNormalizedTerms[$normalizedKeyword] = true;

        if (keywordMatches($normalizedDescription, $keyword['term'])) {
            $score += (int) $keyword['weight'];
            $found[] = $keyword['term'];
        }
    }

    if ($score > $bestScore) {
        $bestType = $type;
        $bestScore = $score;
        $bestKeywords = $found;
    }
}

if ($bestScore === 0) {
    jsonResponse([
        'type' => 'Diagnostic général',
        'score' => 0,
        'confidence' => 'low',
        'keywords_found' => [],
    ]);
}

jsonResponse([
    'type' => $bestType,
    'score' => $bestScore,
    'confidence' => confidenceFromScore($bestScore, $bestType),
    'keywords_found' => $bestKeywords,
]);
