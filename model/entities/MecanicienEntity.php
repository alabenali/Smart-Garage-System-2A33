<?php
declare(strict_types=1);

namespace Model\Entities;

use InvalidArgumentException;

final class MecanicienEntity
{
    private const SPECIALITE_ELECTRICITE = "\u{00C9}lectricit\u{00E9}";

    private ?int $id_mecanicien = null;
    private string $nom;
    private string $prenom;
    private string $telephone;
    private string $specialite;

    public function __construct(?int $id, string $nom, string $prenom, string $telephone, string $specialite)
    {
        if ($id !== null) {
            $this->setIdMecanicien($id);
        }

        $this->setNom($nom);
        $this->setPrenom($prenom);
        $this->setTelephone($telephone);
        $this->setSpecialite($specialite);
    }

    public function getIdMecanicien(): ?int
    {
        return $this->id_mecanicien;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function getSpecialite(): string
    {
        return $this->specialite;
    }

    public function setIdMecanicien(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID invalide');
        }

        $this->id_mecanicien = $id;
    }

    public function setNom(string $nom): void
    {
        $nom = trim($nom);

        if (!preg_match("/^[A-Za-zÀ-ÿ\\s\\-']+$/u", $nom) || mb_strlen($nom) < 2 || mb_strlen($nom) > 50) {
            throw new InvalidArgumentException('Le nom doit contenir uniquement des lettres (min 2, max 50 caractères)');
        }

        $this->nom = $nom;
    }

    public function setPrenom(string $prenom): void
    {
        $prenom = trim($prenom);

        if (!preg_match("/^[A-Za-zÀ-ÿ\\s\\-']+$/u", $prenom) || mb_strlen($prenom) < 2 || mb_strlen($prenom) > 50) {
            throw new InvalidArgumentException('Le prénom doit contenir uniquement des lettres (min 2, max 50 caractères)');
        }

        $this->prenom = $prenom;
    }

    public function setTelephone(string $telephone): void
    {
        $telephone = trim($telephone);

        if (!preg_match('/^[0-9]{8}$/', $telephone)) {
            throw new InvalidArgumentException('Le téléphone doit contenir exactement 8 chiffres');
        }

        $this->telephone = $telephone;
    }

    public function setSpecialite(string $specialite): void
    {
        $allowed = [
            'Moteur' => 'Moteur',
            self::SPECIALITE_ELECTRICITE => self::SPECIALITE_ELECTRICITE,
            'Carrosserie' => 'Carrosserie',
            'Electricite' => self::SPECIALITE_ELECTRICITE,
        ];

        if (!array_key_exists($specialite, $allowed)) {
            throw new InvalidArgumentException('Spécialité invalide. Choisir parmi: Moteur, Électricité, Carrosserie');
        }

        $this->specialite = $allowed[$specialite];
    }
}
