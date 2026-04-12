<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';

final class Mecanicien
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array{id_mecanicien:int, nom:string, prenom:string, telephone:string, specialite:string}>
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id_mecanicien, nom, prenom, telephone, specialite
             FROM mecanicien
             ORDER BY id_mecanicien DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * @param array{nom:string, prenom:string, telephone:string, specialite:string} $data
     */
    public function add(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO mecanicien (nom, prenom, telephone, specialite)
             VALUES (:nom, :prenom, :telephone, :specialite)'
        );

        $stmt->execute([
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':telephone' => $data['telephone'],
            ':specialite' => $data['specialite'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @param array{nom:string, prenom:string, telephone:string, specialite:string} $data
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE mecanicien
             SET nom = :nom,
                 prenom = :prenom,
                 telephone = :telephone,
                 specialite = :specialite
             WHERE id_mecanicien = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':telephone' => $data['telephone'],
            ':specialite' => $data['specialite'],
        ]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM mecanicien WHERE id_mecanicien = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
