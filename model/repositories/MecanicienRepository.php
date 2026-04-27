<?php
declare(strict_types=1);

namespace Model\Repositories;

require_once __DIR__ . '/../entities/MecanicienEntity.php';

use Model\Entities\MecanicienEntity;
use PDO;

final class MecanicienRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM mecanicien ORDER BY id_mecanicien DESC');
        $statement->execute();

        $results = [];
        foreach ($statement->fetchAll() as $row) {
            $results[] = new MecanicienEntity(
                (int) $row['id_mecanicien'],
                $row['nom'],
                $row['prenom'],
                $row['telephone'],
                $row['specialite']
            );
        }

        return $results;
    }

    public function getById(int $id): ?MecanicienEntity
    {
        $statement = $this->pdo->prepare('SELECT * FROM mecanicien WHERE id_mecanicien = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return new MecanicienEntity(
            (int) $row['id_mecanicien'],
            $row['nom'],
            $row['prenom'],
            $row['telephone'],
            $row['specialite']
        );
    }

    public function add(MecanicienEntity $mecanicien): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO mecanicien (nom, prenom, telephone, specialite) VALUES (:nom, :prenom, :telephone, :specialite)'
        );
        $statement->execute([
            'nom' => $mecanicien->getNom(),
            'prenom' => $mecanicien->getPrenom(),
            'telephone' => $mecanicien->getTelephone(),
            'specialite' => $mecanicien->getSpecialite(),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, MecanicienEntity $mecanicien): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE mecanicien SET nom = :nom, prenom = :prenom, telephone = :telephone, specialite = :specialite WHERE id_mecanicien = :id'
        );
        $statement->execute([
            'id' => $id,
            'nom' => $mecanicien->getNom(),
            'prenom' => $mecanicien->getPrenom(),
            'telephone' => $mecanicien->getTelephone(),
            'specialite' => $mecanicien->getSpecialite(),
        ]);

        return $statement->rowCount() > 0 || $this->getById($id) !== null;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM mecanicien WHERE id_mecanicien = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function getAllWithFormations(): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                m.id_mecanicien,
                m.nom,
                m.prenom,
                m.telephone,
                m.specialite,
                f.id_formation,
                f.description,
                f.duree_heures,
                f.certificat,
                f.status,
                mf.date_inscription,
                mf.date_obtention,
                mf.note_obtenue
            FROM mecanicien m
            LEFT JOIN mecanicien_formation mf ON m.id_mecanicien = mf.id_mecanicien
            LEFT JOIN formation f ON mf.id_formation = f.id_formation
            ORDER BY m.id_mecanicien ASC, f.id_formation ASC"
        );
        $statement->execute();

        $results = [];
        foreach ($statement->fetchAll() as $row) {
            $id = (int) $row['id_mecanicien'];

            if (!isset($results[$id])) {
                $results[$id] = [
                    'id_mecanicien' => $id,
                    'nom' => $row['nom'],
                    'prenom' => $row['prenom'],
                    'telephone' => $row['telephone'],
                    'specialite' => $row['specialite'],
                    'formations' => [],
                ];
            }

            if ($row['id_formation'] !== null) {
                $results[$id]['formations'][] = [
                    'id_formation' => (int) $row['id_formation'],
                    'description' => $row['description'],
                    'duree_heures' => (int) $row['duree_heures'],
                    'certificat' => $row['certificat'],
                    'status' => $row['status'],
                    'date_inscription' => $row['date_inscription'],
                    'date_obtention' => $row['date_obtention'],
                    'note_obtenue' => $row['note_obtenue'] !== null ? (float) $row['note_obtenue'] : null,
                ];
            }
        }

        return array_values($results);
    }
}
