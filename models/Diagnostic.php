<?php
// ============================================
// Diagnostic Model (Entity only)
// ============================================

class Diagnostic {
    private $id_diagnostic;
    private $id_vehicule;
    private $description_probleme;
    private $resultat;
    private $gravite;
    private $montant_estime;
    private $status;
    private $date_diagnostic;

    public function __construct() {
        // Entity only: constructor kept for consistency with the project style.
    }

    // ---- Getters & Setters ----
    public function getIdDiagnostic() { return $this->id_diagnostic; }
    public function setIdDiagnostic($id) { $this->id_diagnostic = $id; }

    public function getIdVehicule() { return $this->id_vehicule; }
    public function setIdVehicule($id) { $this->id_vehicule = $id; }

    public function getDescriptionProbleme() { return $this->description_probleme; }
    public function setDescriptionProbleme($desc) { $this->description_probleme = $desc; }

    public function getResultat() { return $this->resultat; }
    public function setResultat($res) { $this->resultat = $res; }

    public function getGravite() { return $this->gravite; }
    public function setGravite($grav) { $this->gravite = $grav; }

    public function getMontantEstime() { return $this->montant_estime; }
    public function setMontantEstime($montant) { $this->montant_estime = $montant; }

    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }

    public function getDateDiagnostic() { return $this->date_diagnostic; }
    public function setDateDiagnostic($date) { $this->date_diagnostic = $date; }

}
