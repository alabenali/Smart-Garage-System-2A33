<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/lib/db.php';
require_once __DIR__ . '/../model/Mecanicien.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function readRequestData(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    parse_str($raw, $formData);
    return is_array($formData) ? $formData : [];
}

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function validateMecanicien(array $data): array
{
    $errors = [];
    $namePattern = '/^[A-Za-zÀ-ÿ\s]+$/u';

    $nom = trim((string)($data['nom'] ?? ''));
    $prenom = trim((string)($data['prenom'] ?? ''));
    $telephone = trim((string)($data['telephone'] ?? ''));
    $specialite = trim((string)($data['specialite'] ?? ''));

    if ($nom === '' || !preg_match($namePattern, $nom)) {
        $errors['nom'] = 'Nom invalide';
    }

    if ($prenom === '' || !preg_match($namePattern, $prenom)) {
        $errors['prenom'] = 'Prenom invalide';
    }

    if (!preg_match('/^[0-9]{8}$/', $telephone)) {
        $errors['telephone'] = 'Telephone invalide';
    }

    if ($specialite === '') {
        $errors['specialite'] = 'Specialite obligatoire';
    }

    return $errors;
}

function cleanMecanicienData(array $data): array
{
    return [
        'nom' => trim((string)($data['nom'] ?? '')),
        'prenom' => trim((string)($data['prenom'] ?? '')),
        'telephone' => trim((string)($data['telephone'] ?? '')),
        'specialite' => trim((string)($data['specialite'] ?? '')),
    ];
}

try {
    $pdo = DB::getConnection();
    $model = new Mecanicien($pdo);

    $action = (string)($_GET['action'] ?? '');
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($method === 'GET' && $action === 'getAll') {
        respond(200, ['ok' => true, 'data' => $model->getAll()]);
    }

    if ($method === 'POST' && $action === 'add') {
        $data = readRequestData();
        $errors = validateMecanicien($data);

        if (!empty($errors)) {
            respond(400, ['ok' => false, 'errors' => $errors]);
        }

        $payload = cleanMecanicienData($data);
        $id = $model->add($payload);

        respond(201, [
            'ok' => true,
            'id' => $id,
            'data' => ['id_mecanicien' => $id] + $payload,
        ]);
    }

    if ($method === 'POST' && $action === 'update') {
        $data = readRequestData();
        $id = (int)($data['id_mecanicien'] ?? 0);

        if ($id <= 0) {
            respond(400, ['ok' => false, 'errors' => ['id_mecanicien' => 'id_mecanicien invalide']]);
        }

        $errors = validateMecanicien($data);
        if (!empty($errors)) {
            respond(400, ['ok' => false, 'errors' => $errors]);
        }

        $payload = cleanMecanicienData($data);
        if (!$model->update($id, $payload)) {
            respond(404, ['ok' => false, 'error' => 'Mecanicien introuvable.']);
        }

        respond(200, [
            'ok' => true,
            'updated' => true,
            'data' => ['id_mecanicien' => $id] + $payload,
        ]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $data = readRequestData();
        $id = (int)($data['id_mecanicien'] ?? 0);

        if ($id <= 0) {
            respond(400, ['ok' => false, 'errors' => ['id_mecanicien' => 'id_mecanicien invalide']]);
        }

        if (!$model->delete($id)) {
            respond(404, ['ok' => false, 'error' => 'Mecanicien introuvable.']);
        }

        respond(200, ['ok' => true, 'deleted' => true]);
    }

    respond(404, ['ok' => false, 'error' => 'Action inconnue']);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => 'Erreur serveur.', 'detail' => $e->getMessage()]);
}
