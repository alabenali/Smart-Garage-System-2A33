<?php

class RendezvousModel
{
    private $id_rdv;
    private $id_creneau;
    private $nom_client;
    private $prenom_client;
    private $telephone_client;
    private $email_client;
    private $id_vehicle;
    private $type_intervention;
    private $description_panne;
    private $remise_eco_appliquee;
    private $urgence_score;
    private $urgence_details;
    private $statut;
    private $notes;
    private $date_creation;
    private $date_modification;

    public function getIdRdv() { return $this->id_rdv; }
    public function setIdRdv($id_rdv) { $this->id_rdv = $id_rdv; }

    public function getIdCreneau() { return $this->id_creneau; }
    public function setIdCreneau($id_creneau) { $this->id_creneau = $id_creneau; }

    public function getNomClient() { return $this->nom_client; }
    public function setNomClient($nom_client) { $this->nom_client = $nom_client; }

    public function getPrenomClient() { return $this->prenom_client; }
    public function setPrenomClient($prenom_client) { $this->prenom_client = $prenom_client; }

    public function getTelephoneClient() { return $this->telephone_client; }
    public function setTelephoneClient($telephone_client) { $this->telephone_client = $telephone_client; }

    public function getEmailClient() { return $this->email_client; }
    public function setEmailClient($email_client) { $this->email_client = $email_client; }

    public function getIdVehicle() { return $this->id_vehicle; }
    public function setIdVehicle($id_vehicle) { $this->id_vehicle = $id_vehicle; }

    public function getTypeIntervention() { return $this->type_intervention; }
    public function setTypeIntervention($type_intervention) { $this->type_intervention = $type_intervention; }

    public function getDescriptionPanne() { return $this->description_panne; }
    public function setDescriptionPanne($description_panne) { $this->description_panne = $description_panne; }

    public function getRemiseEcoAppliquee() { return $this->remise_eco_appliquee; }
    public function setRemiseEcoAppliquee($remise_eco_appliquee) { $this->remise_eco_appliquee = $remise_eco_appliquee; }

    public function getUrgenceScore() { return $this->urgence_score; }
    public function setUrgenceScore($urgence_score) { $this->urgence_score = $urgence_score; }

    public function getUrgenceDetails() { return $this->urgence_details; }
    public function setUrgenceDetails($urgence_details) { $this->urgence_details = $urgence_details; }

    public function getStatut() { return $this->statut; }
    public function setStatut($statut) { $this->statut = $statut; }

    public function getNotes() { return $this->notes; }
    public function setNotes($notes) { $this->notes = $notes; }

    public function getDateCreation() { return $this->date_creation; }
    public function setDateCreation($date_creation) { $this->date_creation = $date_creation; }

    public function getDateModification() { return $this->date_modification; }
    public function setDateModification($date_modification) { $this->date_modification = $date_modification; }
}
