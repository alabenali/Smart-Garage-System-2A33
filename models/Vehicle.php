<?php
// ============================================
// Vehicle Model (OOP + PDO)
// ============================================

require_once __DIR__ . '/../config/Database.php';

class Vehicle {
    private $db;

    // Vehicle properties
    private $id;
    private $marque;
    private $modele;
    private $immatriculation;
    private $couleur;
    private $annee;
    private $kilometrage;
    private $carburant;
    private $date_ajout;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ---- Getters & Setters ----

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getMarque() { return $this->marque; }
    public function setMarque($marque) { $this->marque = $marque; }

    public function getModele() { return $this->modele; }
    public function setModele($modele) { $this->modele = $modele; }

    public function getImmatriculation() { return $this->immatriculation; }
    public function setImmatriculation($immatriculation) { $this->immatriculation = $immatriculation; }

    public function getCouleur() { return $this->couleur; }
    public function setCouleur($couleur) { $this->couleur = $couleur; }

    public function getAnnee() { return $this->annee; }
    public function setAnnee($annee) { $this->annee = $annee; }

    public function getKilometrage() { return $this->kilometrage; }
    public function setKilometrage($kilometrage) { $this->kilometrage = $kilometrage; }

    public function getCarburant() { return $this->carburant; }
    public function setCarburant($carburant) { $this->carburant = $carburant; }

    public function getDateAjout() { return $this->date_ajout; }

    // ---- CRUD Methods ----

    /**
     * Add a new vehicle to the database
     */
    public function add() {
        $sql = "INSERT INTO vehicle (marque, modele, immatriculation, couleur, annee, kilometrage, carburant)
                VALUES (:marque, :modele, :immatriculation, :couleur, :annee, :kilometrage, :carburant)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':marque', $this->marque);
        $stmt->bindParam(':modele', $this->modele);
        $stmt->bindParam(':immatriculation', $this->immatriculation);
        $stmt->bindParam(':couleur', $this->couleur);
        $stmt->bindParam(':annee', $this->annee, PDO::PARAM_INT);
        $stmt->bindParam(':kilometrage', $this->kilometrage, PDO::PARAM_INT);
        $stmt->bindParam(':carburant', $this->carburant);
        return $stmt->execute();
    }

    /**
     * Update an existing vehicle
     */
    public function update() {
        $sql = "UPDATE vehicle
                SET marque = :marque, modele = :modele, immatriculation = :immatriculation,
                    couleur = :couleur, annee = :annee, kilometrage = :kilometrage, carburant = :carburant
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':marque', $this->marque);
        $stmt->bindParam(':modele', $this->modele);
        $stmt->bindParam(':immatriculation', $this->immatriculation);
        $stmt->bindParam(':couleur', $this->couleur);
        $stmt->bindParam(':annee', $this->annee, PDO::PARAM_INT);
        $stmt->bindParam(':kilometrage', $this->kilometrage, PDO::PARAM_INT);
        $stmt->bindParam(':carburant', $this->carburant);
        return $stmt->execute();
    }

    /**
     * Delete a vehicle by ID
     */
    public function delete($id) {
        $sql = "DELETE FROM vehicle WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * List all vehicles
     */
    public function list() {
        $sql = "SELECT * FROM vehicle ORDER BY date_ajout DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Find a single vehicle by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM vehicle WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }
}
