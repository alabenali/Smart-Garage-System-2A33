<?php
declare(strict_types=1);

namespace Model\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

final class MecanicienFormationEntity
{
    private int $id_mecanicien;
    private int $id_formation;
    private ?string $date_inscription = null;
    private ?string $date_obtention = null;
    private ?float $note_obtenue = null;

    public function __construct(int $idMecanicien, int $idFormation, ?string $dateInscription = null, ?string $dateObtention = null, $noteObtenue = null)
    {
        $this->setIdMecanicien($idMecanicien);
        $this->setIdFormation($idFormation);
        $this->setDateInscription($dateInscription);
        $this->setDateObtention($dateObtention);
        $this->setNoteObtenue($noteObtenue);
    }

    public function getIdMecanicien(): int
    {
        return $this->id_mecanicien;
    }

    public function getIdFormation(): int
    {
        return $this->id_formation;
    }

    public function getDateInscription(): ?string
    {
        return $this->date_inscription;
    }

    public function getDateObtention(): ?string
    {
        return $this->date_obtention;
    }

    public function getNoteObtenue(): ?float
    {
        return $this->note_obtenue;
    }

    public function setIdMecanicien(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID mécanicien invalide');
        }

        $this->id_mecanicien = $id;
    }

    public function setIdFormation(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID formation invalide');
        }

        $this->id_formation = $id;
    }

    public function setDateInscription(?string $date): void
    {
        $this->date_inscription = $this->normalizeDate($date, 'Date inscription invalide');
    }

    public function setDateObtention(?string $date): void
    {
        $this->date_obtention = $this->normalizeDate($date, 'Date obtention invalide');
    }

    public function setNoteObtenue($note): void
    {
        if ($note === null || $note === '') {
            $this->note_obtenue = null;
            return;
        }

        if (!is_numeric($note)) {
            throw new InvalidArgumentException('La note doit être numérique');
        }

        $this->note_obtenue = (float) $note;
    }

    private function normalizeDate(?string $date, string $message): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim($date);

        if ($date === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException($message);
        }

        return $date;
    }
}
