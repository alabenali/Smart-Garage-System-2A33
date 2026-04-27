<?php
// ============================================
// Intervention Model (Entity only)
// ============================================
// Représente une intervention liée à un diagnostic
// Statuts: planifiée → en_cours → terminée

class Intervention {
    private $id_intervention;
    private $id_diagnostic;
    private $id_type;
    private $description_travail;
    private $statut;
    private $cout_initial;
    private $cout_final;
    private $statut_devis;
    private $devis_pdf_path;
    private $date_envoi_devis;
    private $date_reponse_devis;
    private $date_debut;
    private $date_fin;

    public function __construct() {
        // Entity only: constructeur pour cohérence avec le projet
    }

    // ---- Getters & Setters ----
    public function getIdIntervention() { 
        return $this->id_intervention; 
    }
    public function setIdIntervention($id) { 
        $this->id_intervention = $id; 
    }

    public function getIdDiagnostic() { 
        return $this->id_diagnostic; 
    }
    public function setIdDiagnostic($id) { 
        $this->id_diagnostic = $id; 
    }

    public function getIdType() { 
        return $this->id_type; 
    }
    public function setIdType($id) { 
        $this->id_type = $id; 
    }

    public function getDescriptionTravail() { 
        return $this->description_travail; 
    }
    public function setDescriptionTravail($desc) { 
        $this->description_travail = $desc; 
    }

    public function getStatut() { 
        return $this->statut; 
    }
    public function setStatut($statut) { 
        $this->statut = $statut; 
    }

    public function getCoutInitial() { 
        return $this->cout_initial; 
    }
    public function setCoutInitial($cout) { 
        $this->cout_initial = (float)$cout; 
    }

    public function getCoutFinal() { 
        return $this->cout_final; 
    }
    public function setCoutFinal($cout) { 
        $this->cout_final = $cout !== null ? (float)$cout : null; 
    }

    public function getStatutDevis() {
        return $this->statut_devis;
    }
    public function setStatutDevis($statut) {
        $this->statut_devis = $statut;
    }

    public function getDevisPdfPath() {
        return $this->devis_pdf_path;
    }
    public function setDevisPdfPath($path) {
        $this->devis_pdf_path = $path;
    }

    public function getDateEnvoiDevis() {
        return $this->date_envoi_devis;
    }
    public function setDateEnvoiDevis($date) {
        $this->date_envoi_devis = $date;
    }

    public function getDateReponseDevis() {
        return $this->date_reponse_devis;
    }
    public function setDateReponseDevis($date) {
        $this->date_reponse_devis = $date;
    }

    public function getDateDebut() { 
        return $this->date_debut; 
    }
    public function setDateDebut($date) { 
        $this->date_debut = $date; 
    }

    public function getDateFin() { 
        return $this->date_fin; 
    }
    public function setDateFin($date) { 
        $this->date_fin = $date; 
    }
}
