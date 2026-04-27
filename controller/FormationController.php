<?php
declare(strict_types=1);

namespace Controller;

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/entities/FormationEntity.php';
require_once __DIR__ . '/../model/repositories/FormationRepository.php';

use Config\Database;
use InvalidArgumentException;
use Model\Entities\FormationEntity;
use Model\Repositories\FormationRepository;
use Throwable;

final class FormationController
{
    private FormationRepository $formationRepository;

    public function __construct()
    {
        $this->formationRepository = new FormationRepository(Database::getConnection());
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
                $this->respond(200, ['ok' => true, 'data' => $this->serializeFormations($this->formationRepository->getAll())]);
            }

            if ($method === 'POST' && $action === 'add') {
                $payload = $this->readJsonInput();
                $entity = new FormationEntity(
                    null,
                    (string) ($payload['description'] ?? ''),
                    (int) ($payload['duree_heures'] ?? 0),
                    (string) ($payload['certificat'] ?? ''),
                    (string) ($payload['status'] ?? 'planifiee')
                );

                $id = $this->formationRepository->add($entity);
                $this->respond(201, ['ok' => true, 'id' => $id]);
            }

            if ($method === 'POST' && $action === 'update') {
                $payload = $this->readJsonInput();
                $id = (int) ($payload['id_formation'] ?? 0);
                $entity = new FormationEntity(
                    $id,
                    (string) ($payload['description'] ?? ''),
                    (int) ($payload['duree_heures'] ?? 0),
                    (string) ($payload['certificat'] ?? ''),
                    (string) ($payload['status'] ?? 'planifiee')
                );

                $updated = $this->formationRepository->update($id, $entity);
                if (!$updated) {
                    $this->respond(404, ['ok' => false, 'error' => 'Formation non trouvée']);
                }

                $this->respond(200, ['ok' => true, 'updated' => true]);
            }

            if ($method === 'POST' && $action === 'delete') {
                $payload = $this->readJsonInput();
                $deleted = $this->formationRepository->delete((int) ($payload['id_formation'] ?? 0));

                if (!$deleted) {
                    $this->respond(404, ['ok' => false, 'error' => 'Formation non trouvée']);
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

    private function serializeFormations(array $formations): array
    {
        $data = [];
        foreach ($formations as $formation) {
            $data[] = [
                'id_formation' => $formation->getIdFormation(),
                'description' => $formation->getDescription(),
                'duree_heures' => $formation->getDureeHeures(),
                'certificat' => $formation->getCertificat(),
                'status' => $formation->getStatus(),
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

(new FormationController())->handleRequest();
