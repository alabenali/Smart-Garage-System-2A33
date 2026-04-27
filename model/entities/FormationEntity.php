<?php
declare(strict_types=1);

namespace Model\Entities;

use InvalidArgumentException;

final class FormationEntity
{
    private ?int $id_formation = null;
    private string $description;
    private int $duree_heures;
    private string $certificat;
    private string $status;

    public function __construct(?int $id, string $description, int $duree_heures, string $certificat, string $status = 'planifiee')
    {
        if ($id !== null) {
            $this->setIdFormation($id);
        }

        $this->setDescription($description);
        $this->setDureeHeures($duree_heures);
        $this->setCertificat($certificat);
        $this->setStatus($status);
    }

    public function getIdFormation(): ?int
    {
        return $this->id_formation;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDureeHeures(): int
    {
        return $this->duree_heures;
    }

    public function getCertificat(): string
    {
        return $this->certificat;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setIdFormation(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID invalide');
        }

        $this->id_formation = $id;
    }

    public function setDescription(string $description): void
    {
        $description = trim($description);

        if (mb_strlen($description) < 10 || mb_strlen($description) > 500) {
            throw new InvalidArgumentException('La description doit contenir entre 10 et 500 caractères');
        }

        $this->description = $description;
    }

    public function setDureeHeures(int $duree): void
    {
        if ($duree <= 0 || $duree > 1000) {
            throw new InvalidArgumentException('La durée doit être positive (max 1000 heures)');
        }

        $this->duree_heures = $duree;
    }

    public function setCertificat(string $certificat): void
    {
        $certificat = trim($certificat);

        if (!preg_match('/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/', $certificat)) {
            throw new InvalidArgumentException('Le certificat doit être un email valide (exemple: nom@domaine.com)');
        }

        $this->certificat = $certificat;
    }

    public function setStatus(string $status): void
    {
        $allowed = ['planifiee', 'en_cours', 'terminee', 'annulee'];

        if (!in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Statut invalide');
        }

        $this->status = $status;
    }
}
