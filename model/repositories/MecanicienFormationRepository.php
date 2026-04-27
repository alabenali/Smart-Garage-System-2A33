<?php
declare(strict_types=1);

namespace Model\Repositories;

require_once __DIR__ . '/../entities/MecanicienFormationEntity.php';

use Model\Entities\MecanicienFormationEntity;
use PDO;

final class MecanicienFormationRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function assignFormationToMecanicien(MecanicienFormationEntity $relation): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO mecanicien_formation (id_mecanicien, id_formation, date_inscription, date_obtention, note_obtenue)
             VALUES (:id_mecanicien, :id_formation, :date_inscription, :date_obtention, :note_obtenue)
             ON DUPLICATE KEY UPDATE
             date_inscription = VALUES(date_inscription),
             date_obtention = VALUES(date_obtention),
             note_obtenue = VALUES(note_obtenue)'
        );

        return $statement->execute([
            'id_mecanicien' => $relation->getIdMecanicien(),
            'id_formation' => $relation->getIdFormation(),
            'date_inscription' => $relation->getDateInscription(),
            'date_obtention' => $relation->getDateObtention(),
            'note_obtenue' => $relation->getNoteObtenue(),
        ]);
    }

    public function deleteRelation(int $idMecanicien, int $idFormation): bool
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM mecanicien_formation WHERE id_mecanicien = :id_mecanicien AND id_formation = :id_formation'
        );
        $statement->execute([
            'id_mecanicien' => $idMecanicien,
            'id_formation' => $idFormation,
        ]);

        return $statement->rowCount() > 0;
    }

    public function getRelations(): array
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
            FROM mecanicien_formation mf
            INNER JOIN mecanicien m ON mf.id_mecanicien = m.id_mecanicien
            INNER JOIN formation f ON mf.id_formation = f.id_formation
            ORDER BY m.id_mecanicien ASC, f.id_formation ASC"
        );
        $statement->execute();

        $results = [];
        foreach ($statement->fetchAll() as $row) {
            $results[] = [
                'mecanicien' => [
                    'id_mecanicien' => (int) $row['id_mecanicien'],
                    'nom' => $row['nom'],
                    'prenom' => $row['prenom'],
                    'telephone' => $row['telephone'],
                    'specialite' => $row['specialite'],
                ],
                'formation' => [
                    'id_formation' => (int) $row['id_formation'],
                    'description' => $row['description'],
                    'duree_heures' => (int) $row['duree_heures'],
                    'certificat' => $row['certificat'],
                    'status' => $row['status'],
                ],
                'pivot' => [
                    'date_inscription' => $row['date_inscription'],
                    'date_obtention' => $row['date_obtention'],
                    'note_obtenue' => $row['note_obtenue'] !== null ? (float) $row['note_obtenue'] : null,
                ],
            ];
        }

        return $results;
    }
}
