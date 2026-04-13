<?php
// ============================================
// Diagnostic Controller
// ============================================

require_once __DIR__ . '/../models/Diagnostic.php';
require_once __DIR__ . '/../models/Vehicle.php';

class DiagnosticController {

    private $diagnosticModel;
    private $vehicleModel;

    public function __construct() {
        $this->diagnosticModel = new Diagnostic();
        $this->vehicleModel = new Vehicle();
    }

    /**
     * List all diagnostics (Back Office)
     */
    public function list() {
        return $this->diagnosticModel->listAll();
    }

    /**
     * Get statistics for dashboard
     */
    public function stats() {
        return $this->diagnosticModel->getStats();
    }

    /**
     * Handle Add/Edit/Delete actions
     */
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'add':
                        $this->add();
                        break;
                    case 'edit':
                        $this->edit();
                        break;
                    case 'delete':
                        $this->delete();
                        break;
                    case 'update_status':
                        $this->updateStatus();
                        break;
                }
            }
        }
    }

    private function add() {
        $id_vehicule = $_POST['id_vehicule'] ?? '';
        $description = $_POST['description_probleme'] ?? '';
        $resultat = $_POST['resultat'] ?? '';
        $gravite = $_POST['gravite'] ?? 'Faible';
        $montant = $_POST['montant_estime'] ?? 0;
        $status = $_POST['status'] ?? 'en attente';
        $date = $_POST['date_diagnostic'] ?? date('Y-m-d');

        if (!empty($id_vehicule) && !empty($description)) {
            $this->diagnosticModel->setIdVehicule($id_vehicule);
            $this->diagnosticModel->setDescriptionProbleme($description);
            $this->diagnosticModel->setResultat($resultat);
            $this->diagnosticModel->setGravite($gravite);
            $this->diagnosticModel->setMontantEstime($montant);
            $this->diagnosticModel->setStatus($status);
            $this->diagnosticModel->setDateDiagnostic($date);

            if ($this->diagnosticModel->add()) {
                header("Location: index.php?action=diagnostics&success=1");
                exit();
            }
        }
        header("Location: index.php?action=diagnostics&error=1");
        exit();
    }

    private function edit() {
        $id = $_POST['id_diagnostic'] ?? '';
        if (!empty($id)) {
            $this->diagnosticModel->setIdDiagnostic($id);
            $this->diagnosticModel->setIdVehicule($_POST['id_vehicule']);
            $this->diagnosticModel->setDescriptionProbleme($_POST['description_probleme']);
            $this->diagnosticModel->setResultat($_POST['resultat']);
            $this->diagnosticModel->setGravite($_POST['gravite']);
            $this->diagnosticModel->setMontantEstime($_POST['montant_estime']);
            $this->diagnosticModel->setStatus($_POST['status']);
            $this->diagnosticModel->setDateDiagnostic($_POST['date_diagnostic']);

            if ($this->diagnosticModel->update()) {
                header("Location: index.php?action=diagnostics&updated=1");
                exit();
            }
        }
        header("Location: index.php?action=diagnostics&error=1");
        exit();
    }

    private function delete() {
        $id = $_POST['id_diagnostic'] ?? '';
        if (!empty($id)) {
            if ($this->diagnosticModel->delete($id)) {
                header("Location: index.php?action=diagnostics&deleted=1");
                exit();
            }
        }
        header("Location: index.php?action=diagnostics&error=1");
        exit();
    }

    private function updateStatus() {
        $id = $_POST['id_diagnostic'] ?? '';
        $status = $_POST['status'] ?? '';
        if (!empty($id) && !empty($status)) {
            if ($this->diagnosticModel->updateStatus($id, $status)) {
                header("Location: index.php?action=mes_diagnostics&updated=1");
                exit();
            }
        }
        header("Location: index.php?action=mes_diagnostics&error=1");
        exit();
    }

    /**
     * Get all vehicles for dropdown
     */
    public function getVehicles() {
        // Assuming Vehicle model has a method to get all vehicles
        // For simplicity, let's add a listAll method to Vehicle class or use it here
        $sql = "SELECT id, marque, modele, immatriculation FROM vehicle";
        $db = Database::getInstance()->getConnection();
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateDiagnosticPdf() {
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            header("Location: index.php?action=diagnostics&error=1");
            exit();
        }

        $diag = $this->diagnosticModel->getById($id);
        if (empty($diag)) {
            header("Location: index.php?action=diagnostics&error=1");
            exit();
        }

        $lines = [
            'Devis Diagnostic - Smart Garage',
            '',
            'Reference: #' . ($diag['id_diagnostic'] ?? ''),
            'Date: ' . (isset($diag['date_diagnostic']) ? date('d/m/Y', strtotime($diag['date_diagnostic'])) : date('d/m/Y')),
            '',
            'Vehicule: ' . trim(($diag['marque'] ?? '') . ' ' . ($diag['modele'] ?? '')),
            'Immatriculation: ' . ($diag['immatriculation'] ?? '-'),
            'Gravite: ' . ($diag['gravite'] ?? '-'),
            '',
            'Description du probleme:',
            (string)($diag['description_probleme'] ?? 'Non specifie'),
            '',
            'Resultat / Reparation proposee:',
            (string)($diag['resultat'] ?? 'En attente'),
            '',
            'Montant estime: ' . number_format((float)($diag['montant_estime'] ?? 0), 2, ',', ' ') . ' DT',
        ];

        $fileName = 'devis-diagnostic-' . (int)$id . '.pdf';
        $this->streamSimplePdf($lines, $fileName);
    }

    private function streamSimplePdf(array $lines, $fileName) {
        $fontSize = 12;
        $lineHeight = 16;
        $x = 50;
        $y = 790;
        $content = "BT\n/F1 $fontSize Tf\n";

        foreach ($lines as $line) {
            if ($y < 60) {
                break;
            }
            $safe = str_replace(['\\\\', '(', ')'], ['\\\\\\\\', '\\\\(', '\\\\)'], $line);
            $content .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", $x, $y, $safe);
            $y -= $lineHeight;
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj";
        $objects[] = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj";
        $objects[] = "5 0 obj << /Length " . strlen($content) . " >> stream\n" . $content . "\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit();
    }
}
