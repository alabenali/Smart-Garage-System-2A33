<?php

declare(strict_types=1);

class RdvService
{
    private PDO $garageDb;
    private ?PDO $clientDb = null;
    private array $clientConfig;

    public function __construct(PDO $garageDb, ?array $clientConfig = null)
    {
        $this->garageDb = $garageDb;
        $this->clientConfig = $clientConfig ?? (array) require __DIR__ . '/../config/client_database.php';
    }

    public function validateClientVehicleRelation(?int $idClient, ?int $idVehicle, bool $strict): array
    {
        if (!$strict && $idClient === null && $idVehicle === null) {
            return [
                'valid' => true,
                'errors' => [],
                'client' => null,
                'vehicle' => null,
            ];
        }

        $errors = [];
        $client = null;
        $vehicle = null;

        if ($idClient === null || $idClient <= 0) {
            $errors[] = 'id_client requis.';
        } else {
            $client = $this->findClientById($idClient);
            if (!$client) {
                $errors[] = 'Client introuvable.';
            }
        }

        if ($idVehicle === null || $idVehicle <= 0) {
            $errors[] = 'id_vehicule requis.';
        } else {
            $vehicle = $this->findVehicleById($idVehicle);
            if (!$vehicle) {
                $errors[] = 'Vehicule introuvable.';
            }
        }

        if ($client && $vehicle) {
            $vehicleClientId = isset($vehicle['id_client']) ? (int) $vehicle['id_client'] : 0;
            if ($vehicleClientId <= 0) {
                $errors[] = 'Ce vehicule n est pas encore rattache a un client.';
            } elseif ($vehicleClientId !== (int) $client['id']) {
                $errors[] = 'Ce vehicule n appartient pas au client indique.';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'client' => $client,
            'vehicle' => $vehicle,
        ];
    }

    public function findClientById(int $idClient): ?array
    {
        if ($idClient <= 0) {
            return null;
        }

        $table = $this->getClientTable();
        $stmt = $this->getClientDb()->prepare("SELECT id, nom, prenom, email, telephone, adresse, statut, post, created_at FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $idClient]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getClientWithVehicles(int $idClient): ?array
    {
        $client = $this->findClientById($idClient);
        if (!$client) {
            return null;
        }

        $stmt = $this->garageDb->prepare('SELECT * FROM vehicle WHERE id_client = :id_client ORDER BY date_ajout DESC, id DESC');
        $stmt->execute([':id_client' => $idClient]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $client['vehicles'] = array_map([$this, 'formatVehicle'], $vehicles);
        return $this->formatClient($client);
    }

    public function getClientWithRendezvous(int $idClient): ?array
    {
        $client = $this->findClientById($idClient);
        if (!$client) {
            return null;
        }

        $sql = "SELECT
                    r.*, c.date_heure, v.immatriculation, v.marque, v.modele, v.kilometrage, v.carburant
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE r.id_client = :id_client
                ORDER BY c.date_heure DESC, r.id_rdv DESC";

        $stmt = $this->garageDb->prepare($sql);
        $stmt->execute([':id_client' => $idClient]);
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $client['rendez_vous'] = array_map([$this, 'formatRendezvous'], $rdvs);
        return $this->formatClient($client);
    }

    public function getVehicleWithRendezvous(int $idVehicle): ?array
    {
        $vehicle = $this->findVehicleById($idVehicle);
        if (!$vehicle) {
            return null;
        }

        $sql = "SELECT r.*, c.date_heure
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                WHERE r.id_vehicle = :id_vehicle
                ORDER BY c.date_heure DESC, r.id_rdv DESC";

        $stmt = $this->garageDb->prepare($sql);
        $stmt->execute([':id_vehicle' => $idVehicle]);
        $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vehicle = $this->formatVehicle($vehicle);
        $vehicle['rendez_vous'] = array_map([$this, 'formatRendezvous'], $rdvs);

        return $vehicle;
    }

    public function buildScoringContext(array $rdv): array
    {
        $context = [
            'client_history_count' => 0,
            'client_no_show_count' => 0,
            'vehicle_failure_frequency' => 0,
            'vehicle_health_score' => 100,
        ];

        if (!empty($rdv['id_client'])) {
            $stmt = $this->garageDb->prepare("SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN statut = 'Annule' OR statut = 'AnnulÃ©' THEN 1 ELSE 0 END) AS canceled
                FROM rendezvous_digital
                WHERE id_client = :id_client");
            $stmt->execute([':id_client' => (int) $rdv['id_client']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $context['client_history_count'] = (int) ($row['total'] ?? 0);
            $context['client_no_show_count'] = (int) ($row['canceled'] ?? 0);
        }

        if (!empty($rdv['id_vehicle'])) {
            $stmt = $this->garageDb->prepare("SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN urgence_score >= 7 THEN 1 ELSE 0 END) AS urgent_total
                FROM rendezvous_digital
                WHERE id_vehicle = :id_vehicle");
            $stmt->execute([':id_vehicle' => (int) $rdv['id_vehicle']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $historyTotal = (int) ($row['total'] ?? 0);
            $urgentTotal = (int) ($row['urgent_total'] ?? 0);
            $context['vehicle_failure_frequency'] = $historyTotal;

            $vehicle = $this->findVehicleById((int) $rdv['id_vehicle']);
            if ($vehicle) {
                $km = (int) ($vehicle['kilometrage'] ?? 0);
                $age = max(0, (int) date('Y') - (int) ($vehicle['annee'] ?? date('Y')));
                $score = 100 - min(35, (int) floor($km / 10000)) - min(25, $age * 2) - min(20, $urgentTotal * 4);
                $context['vehicle_health_score'] = max(0, min(100, $score));
            }
        }

        return $context;
    }

    public function findVehicleById(int $idVehicle): ?array
    {
        if ($idVehicle <= 0) {
            return null;
        }

        $stmt = $this->garageDb->prepare('SELECT * FROM vehicle WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $idVehicle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function formatClient(array $client): array
    {
        return [
            'id_client' => (int) ($client['id'] ?? 0),
            'nom' => $client['nom'] ?? '',
            'prenom' => $client['prenom'] ?? '',
            'email' => $client['email'] ?? '',
            'telephone' => $client['telephone'] ?? null,
            'adresse' => $client['adresse'] ?? null,
            'statut' => $client['statut'] ?? null,
            'post' => $client['post'] ?? null,
            'created_at' => $client['created_at'] ?? null,
            'vehicles' => $client['vehicles'] ?? null,
            'rendez_vous' => $client['rendez_vous'] ?? null,
        ];
    }

    public function formatVehicle(array $vehicle): array
    {
        return [
            'id_vehicule' => (int) ($vehicle['id'] ?? 0),
            'id' => (int) ($vehicle['id'] ?? 0),
            'id_client' => isset($vehicle['id_client']) ? (int) $vehicle['id_client'] : null,
            'marque' => $vehicle['marque'] ?? '',
            'modele' => $vehicle['modele'] ?? '',
            'immatriculation' => $vehicle['immatriculation'] ?? '',
            'couleur' => $vehicle['couleur'] ?? '',
            'annee' => isset($vehicle['annee']) ? (int) $vehicle['annee'] : null,
            'kilometrage' => isset($vehicle['kilometrage']) ? (int) $vehicle['kilometrage'] : null,
            'carburant' => $vehicle['carburant'] ?? '',
            'date_ajout' => $vehicle['date_ajout'] ?? null,
        ];
    }

    public function formatRendezvous(array $rdv): array
    {
        return [
            'id_rdv' => (int) ($rdv['id_rdv'] ?? 0),
            'id_client' => isset($rdv['id_client']) ? (int) $rdv['id_client'] : null,
            'id_vehicule' => isset($rdv['id_vehicle']) ? (int) $rdv['id_vehicle'] : null,
            'id_vehicle' => isset($rdv['id_vehicle']) ? (int) $rdv['id_vehicle'] : null,
            'id_creneau' => isset($rdv['id_creneau']) ? (int) $rdv['id_creneau'] : null,
            'date_heure' => $rdv['date_heure'] ?? null,
            'type_intervention' => $rdv['type_intervention'] ?? '',
            'description_panne' => $rdv['description_panne'] ?? '',
            'statut' => $rdv['statut'] ?? '',
            'urgence_score' => isset($rdv['urgence_score']) ? (int) $rdv['urgence_score'] : 0,
        ];
    }

    private function getClientDb(): PDO
    {
        if ($this->clientDb instanceof PDO) {
            return $this->clientDb;
        }

        $host = (string) ($this->clientConfig['host'] ?? 'localhost');
        $dbname = (string) ($this->clientConfig['dbname'] ?? 'garage1');
        $charset = (string) ($this->clientConfig['charset'] ?? 'utf8mb4');
        $username = (string) ($this->clientConfig['username'] ?? 'root');
        $password = (string) ($this->clientConfig['password'] ?? '');
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        $this->clientDb = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->clientDb;
    }

    private function getClientTable(): string
    {
        $table = (string) ($this->clientConfig['table'] ?? 'user');
        return preg_match('/^[A-Za-z0-9_]+$/', $table) ? $table : 'user';
    }
}
