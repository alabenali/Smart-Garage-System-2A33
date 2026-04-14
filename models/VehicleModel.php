<?php

require_once __DIR__ . '/../config/Database.php';

class VehicleModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function normalizePlate(string $plate): string
    {
        $plate = strtoupper(trim($plate));
        $plate = preg_replace('/\s+/', ' ', $plate);
        return $plate;
    }

    public function findByImmatriculation(string $immatriculation): ?array
    {
        $normalized = $this->normalizePlate($immatriculation);

        $sql = 'SELECT * FROM vehicle WHERE UPPER(REPLACE(immatriculation, "  ", " ")) = :immatriculation LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':immatriculation' => $normalized]);

        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vehicle ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO vehicle (marque, modele, immatriculation, couleur, annee, kilometrage, carburant, date_ajout)
                VALUES (:marque, :modele, :immatriculation, :couleur, :annee, :kilometrage, :carburant, NOW())';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':marque' => $data['marque'],
            ':modele' => $data['modele'],
            ':immatriculation' => $this->normalizePlate($data['immatriculation']),
            ':couleur' => $data['couleur'] ?? 'N/A',
            ':annee' => (int) $data['annee'],
            ':kilometrage' => (int) $data['kilometrage'],
            ':carburant' => $data['carburant'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findOrCreate(array $data): int
    {
        $existing = $this->findByImmatriculation($data['immatriculation']);
        if ($existing) {
            return (int) $existing['id'];
        }

        return $this->create($data);
    }
}
