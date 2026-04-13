<?php
// ============================================
// Diagnostic Model (OOP + PDO)
// ============================================

require_once __DIR__ . '/../config/Database.php';

class Diagnostic {
    private $db;
    private $diagnosticColumns = null;
    private $diagnosticColumnTypes = null;

    // Diagnostic properties
    private $id_diagnostic;
    private $id_vehicule;
    private $description_probleme;
    private $resultat;
    private $gravite;
    private $montant_estime;
    private $status;
    private $date_diagnostic;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    private function getDiagnosticColumns() {
        if ($this->diagnosticColumns !== null) {
            return $this->diagnosticColumns;
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM diagnostic");
        $columns = [];
        $columnTypes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = $row['Field'];
            $columnTypes[$row['Field']] = $row['Type'] ?? '';
        }

        $this->diagnosticColumns = $columns;
        $this->diagnosticColumnTypes = $columnTypes;
        return $this->diagnosticColumns;
    }

    private function resolveColumn(array $candidates, $fallback) {
        $columns = $this->getDiagnosticColumns();
        foreach ($candidates as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }
        return $fallback;
    }

    private function getColumnType($column) {
        $this->getDiagnosticColumns();
        return $this->diagnosticColumnTypes[$column] ?? '';
    }

    private function normalizeToken($value) {
        $value = mb_strtolower((string)$value, 'UTF-8');
        $map = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            ' ' => '', '_' => '', '-' => ''
        ];
        return strtr($value, $map);
    }

    private function normalizeStatusValue($status) {
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');
        $type = $this->getColumnType($statusColumn);
        $status = trim((string)$status);

        if (preg_match('/^enum\((.*)\)$/i', $type, $matches)) {
            $choices = str_getcsv($matches[1], ',', "'", '\\');
            if (in_array($status, $choices, true)) {
                return $status;
            }

            $wanted = $this->normalizeToken($status);
            foreach ($choices as $choice) {
                if ($wanted === $this->normalizeToken($choice)) {
                    return $choice;
                }
            }
        }

        return $status;
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

    // ---- CRUD Methods ----

    public function add() {
        $descriptionColumn = $this->resolveColumn(['description_probleme', 'description'], 'description_probleme');
        $resultColumn = $this->resolveColumn(['resultat', 'result'], 'resultat');
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');
        $statusValue = $this->normalizeStatusValue($this->status);

        $sql = "INSERT INTO diagnostic (id_vehicule, $descriptionColumn, $resultColumn, gravite, montant_estime, $statusColumn, date_diagnostic)
            VALUES (:id_vehicule, :description_probleme, :resultat, :gravite, :montant_estime, :status, :date_diagnostic)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_vehicule', $this->id_vehicule);
        $stmt->bindParam(':description_probleme', $this->description_probleme);
        $stmt->bindParam(':resultat', $this->resultat);
        $stmt->bindParam(':gravite', $this->gravite);
        $stmt->bindParam(':montant_estime', $this->montant_estime);
        $stmt->bindParam(':status', $statusValue);
        $stmt->bindParam(':date_diagnostic', $this->date_diagnostic);
        return $stmt->execute();
    }

    public function update() {
        $descriptionColumn = $this->resolveColumn(['description_probleme', 'description'], 'description_probleme');
        $resultColumn = $this->resolveColumn(['resultat', 'result'], 'resultat');
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');
        $statusValue = $this->normalizeStatusValue($this->status);

        $sql = "UPDATE diagnostic
                SET id_vehicule = :id_vehicule, $descriptionColumn = :description_probleme,
                    $resultColumn = :resultat, gravite = :gravite, montant_estime = :montant_estime,
                    $statusColumn = :status, date_diagnostic = :date_diagnostic
                WHERE id_diagnostic = :id_diagnostic";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_diagnostic', $this->id_diagnostic);
        $stmt->bindParam(':id_vehicule', $this->id_vehicule);
        $stmt->bindParam(':description_probleme', $this->description_probleme);
        $stmt->bindParam(':resultat', $this->resultat);
        $stmt->bindParam(':gravite', $this->gravite);
        $stmt->bindParam(':montant_estime', $this->montant_estime);
        $stmt->bindParam(':status', $statusValue);
        $stmt->bindParam(':date_diagnostic', $this->date_diagnostic);
        return $stmt->execute();
    }

    public function delete($id) {
        $sql = "DELETE FROM diagnostic WHERE id_diagnostic = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function listAll() {
        $descriptionColumn = $this->resolveColumn(['description_probleme', 'description'], 'description_probleme');
        $resultColumn = $this->resolveColumn(['resultat', 'result'], 'resultat');
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');

        $sql = "SELECT d.*, v.marque, v.modele, v.immatriculation
            , d.$descriptionColumn AS description_probleme
            , d.$resultColumn AS resultat
            , d.$statusColumn AS status
                FROM diagnostic d
                JOIN vehicle v ON d.id_vehicule = v.id
                ORDER BY d.date_diagnostic DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $descriptionColumn = $this->resolveColumn(['description_probleme', 'description'], 'description_probleme');
        $resultColumn = $this->resolveColumn(['resultat', 'result'], 'resultat');
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');

        $sql = "SELECT d.*, v.marque, v.modele, v.immatriculation
            , d.$descriptionColumn AS description_probleme
            , d.$resultColumn AS resultat
            , d.$statusColumn AS status
                FROM diagnostic d
                JOIN vehicle v ON d.id_vehicule = v.id
                WHERE d.id_diagnostic = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listByVehicle($id_vehicule) {
        $descriptionColumn = $this->resolveColumn(['description_probleme', 'description'], 'description_probleme');
        $resultColumn = $this->resolveColumn(['resultat', 'result'], 'resultat');
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');

        $sql = "SELECT d.*, v.marque, v.modele
            , d.$descriptionColumn AS description_probleme
            , d.$resultColumn AS resultat
            , d.$statusColumn AS status
                FROM diagnostic d
                JOIN vehicle v ON d.id_vehicule = v.id
                WHERE d.id_vehicule = :id_vehicule
                ORDER BY d.date_diagnostic DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_vehicule', $id_vehicule);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $statusColumn = $this->resolveColumn(['status', 'statut'], 'status');
        $statusValue = $this->normalizeStatusValue($status);

        $sql = "UPDATE diagnostic SET $statusColumn = :status WHERE id_diagnostic = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $statusValue);
        return $stmt->execute();
    }

    // Statistics for dashboard
    public function getStats() {
        $stats = [];
        
        $sql = "SELECT COUNT(*) as total FROM diagnostic";
        $stats['total'] = $this->db->query($sql)->fetchColumn();

        $sql = "SELECT COUNT(*) FROM diagnostic WHERE gravite = 'Élevé'";
        $stats['urgent'] = $this->db->query($sql)->fetchColumn();

        $sql = "SELECT COUNT(*) FROM diagnostic WHERE status = 'en attente'";
        $stats['waiting'] = $this->db->query($sql)->fetchColumn();

        $sql = "SELECT COUNT(*) FROM diagnostic WHERE status = 'terminé'";
        $stats['completed'] = $this->db->query($sql)->fetchColumn();

        return $stats;
    }

    public function getGraviteStats() {
        $sql = "SELECT gravite, COUNT(*) as count FROM diagnostic GROUP BY gravite";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
