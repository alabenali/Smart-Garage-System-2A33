<?php
// models/User.php

require_once __DIR__ . '/../config.php';

// ── Classe User ───────────────────────────────────────────────────────────────
class User {
    // ── Propriétés privées ──────────────────────────────────────────────
    private ?int    $id              = null;
    private string  $nom             = '';
    private string  $prenom          = '';
    private string  $email           = '';
    private ?string $telephone       = null;
    private ?string $adresse         = null;
    private string  $motDePasse      = '';
    private string  $statut          = 'actif';
    private string  $post            = 'client'; // 'admin' ou 'client'
    private ?string $createdAt       = null;
    private ?string $profilePicture  = null;

    public function __construct() {}

    // ── GETTERS ────────────────────────────────────────────────────────
    public function getId(): ?int           { return $this->id; }
    public function getNom(): string        { return $this->nom; }
    public function getPrenom(): string     { return $this->prenom; }
    public function getEmail(): string      { return $this->email; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getAdresse(): ?string   { return $this->adresse; }
    public function getMotDePasse(): string { return $this->motDePasse; }
    public function getStatut(): string     { return $this->statut; }
    public function getPost(): string       { return $this->post; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getProfilePicture(): ?string { return $this->profilePicture; }

    // ── SETTERS ────────────────────────────────────────────────────────
    public function setId(?int $id): void            { $this->id = $id; }
    public function setNom(string $nom): void        { $this->nom = trim($nom); }
    public function setPrenom(string $prenom): void  { $this->prenom = trim($prenom); }
    public function setEmail(string $email): void    { $this->email = trim($email); }
    public function setTelephone(?string $tel): void { $this->telephone = $tel ? trim($tel) : null; }
    public function setAdresse(?string $adr): void   { $this->adresse = $adr ? trim($adr) : null; }
    public function setMotDePasse(string $mdp): void { $this->motDePasse = $mdp; }
    public function setStatut(string $statut): void  { $this->statut = $statut; }
    public function setPost(string $post): void      { $this->post = $post; }
    public function setCreatedAt(?string $d): void   { $this->createdAt = $d; }
    public function setProfilePicture(?string $pic): void { $this->profilePicture = $pic; }
}
