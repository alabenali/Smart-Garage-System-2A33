<?php
// models/User.php

<<<<<<< HEAD
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
=======
require_once __DIR__ . '/Database.php';

class User {
    // ── Propriétés privées ──────────────────────────────────────────────
    private ?int    $id         = null;
    private string  $nom        = '';
    private string  $prenom     = '';
    private string  $email      = '';
    private ?string $telephone  = null;
    private ?string $adresse    = null;
    private string  $motDePasse = '';
    private string  $statut     = 'actif';
    private string  $post       = 'client'; // 'admin' ou 'client'
    private ?string $createdAt  = null;

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e

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
<<<<<<< HEAD
    public function getProfilePicture(): ?string { return $this->profilePicture; }
=======
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e

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
<<<<<<< HEAD
    public function setProfilePicture(?string $pic): void { $this->profilePicture = $pic; }
=======

    // ── HYDRATATION depuis tableau PDO ────────────────────────────────
    public function hydrate(array $row): void {
        $this->setId((int) $row['id']);
        $this->setNom($row['nom']);
        $this->setPrenom($row['prenom']);
        $this->setEmail($row['email']);
        $this->setTelephone($row['telephone'] ?? null);
        $this->setAdresse($row['adresse'] ?? null);
        $this->setMotDePasse($row['mot_de_passe']);
        $this->setStatut($row['statut'] ?? 'actif');
        $this->setPost($row['post'] ?? 'client');
        $this->setCreatedAt($row['created_at'] ?? null);
    }

    // ── Convertir en tableau (pratique pour les vues) ─────────────────
    public function toArray(): array {
        return [
            'id'           => $this->getId(),
            'nom'          => $this->getNom(),
            'prenom'       => $this->getPrenom(),
            'email'        => $this->getEmail(),
            'telephone'    => $this->getTelephone(),
            'adresse'      => $this->getAdresse(),
            'mot_de_passe' => $this->getMotDePasse(),
            'statut'       => $this->getStatut(),
            'post'         => $this->getPost(),
            'created_at'   => $this->getCreatedAt(),
        ];
    }

    // ── CRUD ───────────────────────────────────────────────────────────

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM user WHERE post = 'client' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function create(array $data): bool {
        $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO user (nom, prenom, email, telephone, adresse, mot_de_passe, statut, post)
            VALUES (:nom, :prenom, :email, :telephone, :adresse, :mot_de_passe, :statut, :post)
        ");
        return $stmt->execute([
            ':nom'          => $data['nom'],
            ':prenom'       => $data['prenom'],
            ':email'        => $data['email'],
            ':telephone'    => $data['telephone'] ?? null,
            ':adresse'      => $data['adresse'] ?? null,
            ':mot_de_passe' => $hashedPassword,
            ':statut'       => $data['statut'] ?? 'actif',
            ':post'         => $data['post'] ?? 'client',
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE user
            SET nom = :nom, prenom = :prenom, email = :email,
                telephone = :telephone, adresse = :adresse, statut = :statut
            WHERE id = :id
        ");
        return $stmt->execute([
            ':nom'       => $data['nom'],
            ':prenom'    => $data['prenom'],
            ':email'     => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':adresse'   => $data['adresse'] ?? null,
            ':statut'    => $data['statut'],
            ':id'        => $id,
        ]);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE user SET mot_de_passe = :mdp WHERE id = :id");
        return $stmt->execute([':mdp' => $hashed, ':id' => $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM user WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countAll(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client'")->fetchColumn();
    }

    public function countActive(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM user WHERE post = 'client' AND statut = 'actif'")->fetchColumn();
    }

    public function verifyPassword(string $email, string $password): array|false {
        $row = $this->getByEmail($email);
        if ($row && password_verify($password, $row['mot_de_passe'])) {
            return $row;
        }
        return false;
    }

    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM user WHERE email = :email AND id != :id");
        $stmt->execute([':email' => $email, ':id' => $excludeId]);
        return $stmt->fetch() !== false;
    }
>>>>>>> c44cda46c49945f97d6970f58880ae0b98fe562e
}
