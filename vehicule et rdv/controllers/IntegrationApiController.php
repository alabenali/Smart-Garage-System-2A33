<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../services/RdvService.php';

class IntegrationApiController
{
    private PDO $db;
    private RdvService $rdvService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->rdvService = new RdvService($this->db);
    }

    public function clientVehicles(): void
    {
        $idClient = isset($_GET['id_client']) ? (int) $_GET['id_client'] : 0;
        if ($idClient <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'id_client requis'], 422);
            return;
        }

        try {
            $data = $this->rdvService->getClientWithVehicles($idClient);
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lecture client/vehicules'], 500);
            return;
        }

        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Client introuvable'], 404);
            return;
        }

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function clientRendezvous(): void
    {
        $idClient = isset($_GET['id_client']) ? (int) $_GET['id_client'] : 0;
        if ($idClient <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'id_client requis'], 422);
            return;
        }

        try {
            $data = $this->rdvService->getClientWithRendezvous($idClient);
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lecture client/RDV'], 500);
            return;
        }

        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Client introuvable'], 404);
            return;
        }

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function vehicleRendezvous(): void
    {
        $idVehicle = 0;
        if (isset($_GET['id_vehicule'])) {
            $idVehicle = (int) $_GET['id_vehicule'];
        } elseif (isset($_GET['id_vehicle'])) {
            $idVehicle = (int) $_GET['id_vehicle'];
        }

        if ($idVehicle <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'id_vehicule requis'], 422);
            return;
        }

        try {
            $data = $this->rdvService->getVehicleWithRendezvous($idVehicle);
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Erreur lecture vehicule/RDV'], 500);
            return;
        }

        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Vehicule introuvable'], 404);
            return;
        }

        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    public function vehicleDiagnostics(): void
    {
        $id = isset($_GET['id_vehicle']) ? (int)$_GET['id_vehicle'] : 0;
        if ($id <= 0) { $this->jsonResponse(['success'=>false,'message'=>'id_vehicle requis'],422); return; }
        $stmt = $this->db->prepare('SELECT * FROM diagnostic WHERE id_vehicle = :id ORDER BY date_diagnostic DESC');
        $stmt->execute([':id' => $id]);
        $this->jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
    }

    public function rdvDiagnostic(): void
    {
        $id = isset($_GET['id_rdv']) ? (int)$_GET['id_rdv'] : 0;
        if ($id <= 0) { $this->jsonResponse(['success'=>false,'message'=>'id_rdv requis'],422); return; }
        $stmt = $this->db->prepare('SELECT * FROM diagnostic WHERE id_rdv = :id ORDER BY date_diagnostic DESC LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $this->jsonResponse(['success'=>true,'data'=>$row ?: null]);
    }

    public function clientDiagnostics(): void
    {
        $id = isset($_GET['id_client']) ? (int)$_GET['id_client'] : 0;
        if ($id <= 0) { $this->jsonResponse(['success'=>false,'message'=>'id_client requis'],422); return; }
        $stmt = $this->db->prepare('SELECT * FROM diagnostic WHERE id_client = :id ORDER BY date_diagnostic DESC');
        $stmt->execute([':id' => $id]);
        $this->jsonResponse(['success'=>true,'data'=>$stmt->fetchAll()]);
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
