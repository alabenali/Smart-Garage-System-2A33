<?php
// ============================================
// ContrÃ´leur Garantie â€“ Dashboard + API JSON
// ============================================

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Garantie.php';

class GarantieController
{
    private $conn;
    private $model;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->model = new Garantie();
    }

    // â”€â”€ Dashboard garanties (BackOffice) â”€â”€
    public function index()
    {
        $filtre = trim($_GET['filtre'] ?? 'toutes');
        $validFiltres = ['toutes', 'actives', 'bientot', 'expirees', 'remplacees'];
        if (!in_array($filtre, $validFiltres, true)) {
            $filtre = 'toutes';
        }

        $stats     = $this->model->getStats();
        $garanties = $this->model->getAll($filtre);

        $pageTitle = 'Garanties';
        $action    = 'manageGaranties';
        $success   = isset($_GET['success']) ? htmlspecialchars((string) $_GET['success']) : '';
        $error     = isset($_GET['error']) ? htmlspecialchars((string) $_GET['error']) : '';

        require __DIR__ . '/../views/back/garanties_index.php';
    }

    // â”€â”€ CrÃ©er les garanties aprÃ¨s une commande â”€â”€
    public function createAfterCommande(int $id_commande, array $items, int $id_client)
    {
        $datePose = date('Y-m-d');

        foreach ($items as $item) {
            $id_piece = (int) ($item['id_piece'] ?? 0);
            if ($id_piece <= 0) continue;

            // Chaque piece achetee recoit une garantie de 30 jours.
            $duree = 1;

            try {
                $this->model->createFromCommande(
                    $id_commande,
                    $id_piece,
                    $id_client,
                    $datePose,
                    $duree
                );
            } catch (Throwable $e) {
                // Ne pas bloquer la commande si une garantie Ã©choue
                error_log('Erreur crÃ©ation garantie piÃ¨ce #' . $id_piece . ' : ' . $e->getMessage());
            }
        }
    }

    // â”€â”€ API JSON : Garanties d'un client â”€â”€
    public function byClient()
    {
        $id_client = isset($_GET['id_client']) ? (int) $_GET['id_client'] : 0;

        header('Content-Type: application/json; charset=utf-8');

        if ($id_client <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID client invalide.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $data = $this->model->getByClient($id_client);
        echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // â”€â”€ API JSON : Marquer une garantie comme remplacÃ©e â”€â”€
    public function marquerRemplacee()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=manageGaranties');
            exit;
        }

        $id_garantie = isset($_POST['id_garantie']) ? (int) $_POST['id_garantie'] : 0;
        $notes       = trim($_POST['notes'] ?? '');

        if ($id_garantie <= 0) {
            header('Location: index.php?action=manageGaranties&error=' . rawurlencode('ID garantie invalide.'));
            exit;
        }

        try {
            $this->model->markRemplacee($id_garantie, $notes);
            header('Location: index.php?action=manageGaranties&success=' . rawurlencode('Garantie #' . $id_garantie . ' marquÃ©e comme remplacÃ©e.'));
        } catch (Throwable $e) {
            header('Location: index.php?action=manageGaranties&error=' . rawurlencode($e->getMessage()));
        }
        exit;
    }

    // â”€â”€ API JSON : Test alertes (sans envoi SMS) â”€â”€
    public function testAlertes()
    {
        header('Content-Type: application/json; charset=utf-8');

        $alertes = $this->model->getAlertesToSend();

        echo json_encode([
            'success'     => true,
            'nb_alertes'  => count($alertes),
            'alertes'     => $alertes,
            'timestamp'   => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // â”€â”€ DÃ©tail d'une garantie (JSON) â”€â”€
    public function detail()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        header('Content-Type: application/json; charset=utf-8');

        $garantie = $this->model->getById($id);
        if (!$garantie) {
            echo json_encode(['success' => false, 'message' => 'Garantie introuvable.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $garantie], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
