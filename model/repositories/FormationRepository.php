<?php
declare(strict_types=1);

namespace Model\Repositories;

require_once __DIR__ . '/../entities/FormationEntity.php';

use Model\Entities\FormationEntity;
use PDO;

final class FormationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM formation ORDER BY id_formation DESC');
        $statement->execute();

        $results = [];
        foreach ($statement->fetchAll() as $row) {
            $results[] = new FormationEntity(
                (int) $row['id_formation'],
                $row['description'],
                (int) $row['duree_heures'],
                $row['certificat'],
                $row['status']
            );
        }

        return $results;
    }

    public function getById(int $id): ?FormationEntity
    {
        $statement = $this->pdo->prepare('SELECT * FROM formation WHERE id_formation = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return new FormationEntity(
            (int) $row['id_formation'],
            $row['description'],
            (int) $row['duree_heures'],
            $row['certificat'],
            $row['status']
        );
    }

    public function add(FormationEntity $formation): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO formation (description, duree_heures, certificat, status) VALUES (:description, :duree_heures, :certificat, :status)'
        );
        $statement->execute([
            'description' => $formation->getDescription(),
            'duree_heures' => $formation->getDureeHeures(),
            'certificat' => $formation->getCertificat(),
            'status' => $formation->getStatus(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, FormationEntity $formation): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE formation SET description = :description, duree_heures = :duree_heures, certificat = :certificat, status = :status WHERE id_formation = :id'
        );
        $statement->execute([
            'id' => $id,
            'description' => $formation->getDescription(),
            'duree_heures' => $formation->getDureeHeures(),
            'certificat' => $formation->getCertificat(),
            'status' => $formation->getStatus(),
        ]);

        return $statement->rowCount() > 0 || $this->getById($id) !== null;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM formation WHERE id_formation = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
