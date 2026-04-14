<?php

class CreneauModel
{
    private $id_creneau;
    private $date_heure;
    private $est_heure_creuse;
    private $capacite_max;

    public function getIdCreneau() { return $this->id_creneau; }
    public function setIdCreneau($id_creneau) { $this->id_creneau = $id_creneau; }

    public function getDateHeure() { return $this->date_heure; }
    public function setDateHeure($date_heure) { $this->date_heure = $date_heure; }

    public function getEstHeureCreuse() { return $this->est_heure_creuse; }
    public function setEstHeureCreuse($est_heure_creuse) { $this->est_heure_creuse = $est_heure_creuse; }

    public function getCapaciteMax() { return $this->capacite_max; }
    public function setCapaciteMax($capacite_max) { $this->capacite_max = $capacite_max; }
}
