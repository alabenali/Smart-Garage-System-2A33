<?php
// ============================================
// Modèle de Véhicule (classe de données)
// ============================================

class Vehicle {

    // Propriétés du véhicule
    private $id;
    private $id_client;
    private $marque;
    private $modele;
    private $immatriculation;
    private $couleur;
    private $annee;
    private $kilometrage;
    private $carburant;
    private $date_ajout;

    // ---- Getters & Setters ----

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdVehicule() { return $this->id; }
    public function setIdVehicule($id_vehicule) { $this->id = $id_vehicule; }

    public function getIdClient() { return $this->id_client; }
    public function setIdClient($id_client) { $this->id_client = $id_client; }

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

}
