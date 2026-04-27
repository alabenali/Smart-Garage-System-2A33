<?php
declare(strict_types=1);

namespace Controller;

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/entities/MecanicienEntity.php';
require_once __DIR__ . '/../model/entities/MecanicienFormationEntity.php';
require_once __DIR__ . '/../model/repositories/MecanicienRepository.php';
require_once __DIR__ . '/../model/repositories/MecanicienFormationRepository.php';

use Config\Database;
use InvalidArgumentException;
use Model\Entities\MecanicienEntity;
use Model\Entities\MecanicienFormationEntity;
use Model\Repositories\MecanicienFormationRepository;
use Model\Repositories\MecanicienRepository;
use Throwable;

final class MecanicienController
{
    private MecanicienRepository $mecanicienRepository;
    private MecanicienFormationRepository $relationRepository;

    public function __construct()
    {
        $pdo = Database::getConnection();
        $this->mecanicienRepository = new MecanicienRepository($pdo);
        $this->relationRepository = new MecanicienFormationRepository($pdo);
    }

    public function handleRequest(): void
    {
        $this->sendHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $action = $_GET['action'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            if ($method === 'GET' && $action === 'getAll') {
                $this->respond(200, ['ok' => true, 'data' => $this->serializeMecaniciens($this->mecanicienRepository->getAll())]);
            }

            if ($method === 'GET' && $action === 'getAllWithFormations') {
                $this->respond(200, ['ok' => true, 'data' => $this->mecanicienRepository->getAllWithFormations()]);
            }

            if ($method === 'GET' && $action === 'getRelations') {
                $this->respond(200, ['ok' => true, 'data' => $this->relationRepository->getRelations()]);
            }

            if ($method === 'POST' && $action === 'add') {
                $payload = $this->readJsonInput();
                $entity = new MecanicienEntity(
                    null,
                    (string) ($payload['nom'] ?? ''),
                    (string) ($payload['prenom'] ?? ''),
                    (string) ($payload['telephone'] ?? ''),
                    (string) ($payload['specialite'] ?? '')
                );

                $id = $this->mecanicienRepository->add($entity);
                $this->respond(201, ['ok' => true, 'id' => $id]);
            }

            if ($method === 'POST' && $action === 'update') {
                $payload = $this->readJsonInput();
                $id = (int) ($payload['id_mecanicien'] ?? 0);
                $entity = new MecanicienEntity(
                    $id,
                    (string) ($payload['nom'] ?? ''),
                    (string) ($payload['prenom'] ?? ''),
                    (string) ($payload['telephone'] ?? ''),
                    (string) ($payload['specialite'] ?? '')
                );

                $updated = $this->mecanicienRepository->update($id, $entity);
                if (!$updated) {
                    $this->respond(404, ['ok' => false, 'error' => 'Mécanicien non trouvé']);
                }

                $this->respond(200, ['ok' => true, 'updated' => true]);
            }

            if ($method === 'POST' && $action === 'delete') {
                $payload = $this->readJsonInput();
                $id = (int) ($payload['id_mecanicien'] ?? 0);
                $deleted = $this->mecanicienRepository->delete($id);

                if (!$deleted) {
                    $this->respond(404, ['ok' => false, 'error' => 'Mécanicien non trouvé']);
                }

                $this->respond(200, ['ok' => true, 'deleted' => true]);
            }

            if ($method === 'POST' && $action === 'assignFormation') {
                $payload = $this->readJsonInput();
                $entity = new MecanicienFormationEntity(
                    (int) ($payload['id_mecanicien'] ?? 0),
                    (int) ($payload['id_formation'] ?? 0),
                    isset($payload['date_inscription']) ? (string) $payload['date_inscription'] : null,
                    isset($payload['date_obtention']) ? (string) $payload['date_obtention'] : null,
                    $payload['note_obtenue'] ?? null
                );

                $this->relationRepository->assignFormationToMecanicien($entity);
                $this->respond(200, ['ok' => true, 'assigned' => true]);
            }

            if ($method === 'POST' && $action === 'deleteRelation') {
                $payload = $this->readJsonInput();
                $deleted = $this->relationRepository->deleteRelation(
                    (int) ($payload['id_mecanicien'] ?? 0),
                    (int) ($payload['id_formation'] ?? 0)
                );

                if (!$deleted) {
                    $this->respond(404, ['ok' => false, 'error' => 'Relation non trouvée']);
                }

                $this->respond(200, ['ok' => true, 'deleted' => true]);
            }

            $this->respond(404, ['ok' => false, 'error' => 'Action inconnue']);
        } catch (InvalidArgumentException $exception) {
            $this->respond(400, ['ok' => false, 'error' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            $this->respond(500, ['ok' => false, 'error' => 'Erreur serveur: ' . $exception->getMessage()]);
        }
    }

    private function readJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw === false ? '' : $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function serializeMecaniciens(array $mecaniciens): array
    {
        $data = [];
        foreach ($mecaniciens as $mecanicien) {
            $data[] = [
                'id_mecanicien' => $mecanicien->getIdMecanicien(),
                'nom' => $mecanicien->getNom(),
                'prenom' => $mecanicien->getPrenom(),
                'telephone' => $mecanicien->getTelephone(),
                'specialite' => $mecanicien->getSpecialite(),
            ];
        }

        return $data;
    }

    private function sendHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }

    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

(new MecanicienController())->handleRequest();
