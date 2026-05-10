<?php

require_once __DIR__ . '/../config/Database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfController
{
    private $pdfEnabled = false;
    private $pdfError = '';
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();

        foreach ($this->getAutoloadCandidates() as $autoloadPath) {
            if (!is_file($autoloadPath)) {
                continue;
            }

            require_once $autoloadPath;

            if (class_exists(Dompdf::class) && class_exists(Options::class)) {
                $this->pdfEnabled = true;
                return;
            }
        }

        $this->pdfError = 'Export PDF indisponible: dependance Dompdf introuvable. Configurez SMART_GARAGE_VENDOR_AUTOLOAD.';
    }

    public function exportCommandes()
    {
        if (!$this->ensurePdfEnabled('manageCommandes')) {
            return;
        }

        $commandes = $this->getAllCommandes();
        $headers = ['ID', 'Nom', 'Prenom', 'Telephone', 'Piece', 'Qte', 'Montant (DT)', 'Statut', 'Date'];
        $rows = [];

        foreach ($commandes as $c) {
            $rows[] = [
                (string) ($c['id_commande'] ?? ''),
                (string) ($c['nom_client'] ?? ''),
                (string) ($c['prenom_client'] ?? ''),
                (string) ($c['telephone'] ?? ''),
                (string) ($c['piece_nom'] ?? ''),
                (string) ($c['quantite'] ?? ''),
                number_format((float) ($c['montant_total'] ?? 0), 2, ',', ' ') . ' DT',
                (string) ($c['statut'] ?? ''),
                !empty($c['date_commande']) ? date('d/m/Y H:i', strtotime((string) $c['date_commande'])) : '',
            ];
        }

        $html = $this->buildTablePdfHtml('Export des commandes', date('d/m/Y H:i'), $headers, $rows);
        $this->downloadPdf($html, 'commandes_' . date('Y-m-d') . '.pdf', 'landscape');
    }

    public function exportCommande()
    {
        if (!$this->ensurePdfEnabled('manageCommandes')) {
            return;
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $commande = $this->getCommandeById($id);

        if (!$commande) {
            header('Location: index.php?action=manageCommandes&error=' . rawurlencode('Commande introuvable pour export PDF'));
            exit;
        }

        $html = $this->buildCommandeDetailHtml($commande);
        $this->downloadPdf($html, 'commande_' . $id . '.pdf', 'portrait');
    }

    public function exportDemandes()
    {
        if (!$this->ensurePdfEnabled('managePieces')) {
            return;
        }

        $filePath = __DIR__ . '/../database/demandes_piece.json';
        $demandes = [];

        if (is_file($filePath) && is_readable($filePath)) {
            $raw = file_get_contents($filePath);
            if ($raw !== false && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $demandes = $decoded;
                }
            }
        }

        $headers = ['ID', 'Date', 'Nom', 'Prenom', 'Telephone', 'Piece demandee', 'Marque', 'Description', 'Qte'];
        $rows = [];

        foreach ($demandes as $d) {
            $rows[] = [
                (string) ($d['id'] ?? ''),
                !empty($d['date_demande']) ? date('d/m/Y H:i', strtotime((string) $d['date_demande'])) : '',
                (string) ($d['nom_client'] ?? ''),
                (string) ($d['prenom_client'] ?? ''),
                (string) ($d['telephone'] ?? ''),
                (string) ($d['nom_piece'] ?? ''),
                (string) ($d['marque'] ?? ''),
                (string) ($d['description'] ?? ''),
                (string) ($d['quantite'] ?? ''),
            ];
        }

        $html = $this->buildTablePdfHtml('Export des demandes de pieces', date('d/m/Y H:i'), $headers, $rows);
        $this->downloadPdf($html, 'demandes_' . date('Y-m-d') . '.pdf', 'landscape');
    }

    private function buildTablePdfHtml($title, $dateText, array $headers, array $rows)
    {
        $logoHtml = $this->buildLogoHtml();
        $thead = '';
        foreach ($headers as $header) {
            $thead .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $tbody = '';
        if (empty($rows)) {
            $tbody = '<tr><td colspan="' . count($headers) . '">Aucune donnee disponible</td></tr>';
        } else {
            foreach ($rows as $row) {
                $tbody .= '<tr>';
                foreach ($row as $cell) {
                    $tbody .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $tbody .= '</tr>';
            }
        }

        return '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 50px 28px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e3550; }
    .hero { background: linear-gradient(135deg, #173252, #c43d2f); color: #ffffff; padding: 18px 20px; border-radius: 18px; }
    .brand { display: inline-flex; align-items: center; gap: 10px; }
    .brand img { width: 34px; height: 34px; object-fit: contain; background: #ffffff; border-radius: 10px; padding: 5px; }
    .brand span { font-size: 16px; font-weight: bold; }
    .hero h1 { margin: 10px 0 4px; font-size: 24px; }
    .hero p { margin: 0; font-size: 11px; color: rgba(255,255,255,0.82); }
    .table-wrap { margin-top: 20px; border: 1px solid #d9dee5; border-radius: 16px; overflow: hidden; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { padding: 8px 9px; text-align: center; vertical-align: middle; word-wrap: break-word; border-bottom: 1px solid #edf1f5; }
    thead th { background: #f4f7fb; color: #6f7e8f; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
    tbody tr:nth-child(even) td { background: #fbfcfe; }
    .footer { margin-top: 12px; text-align: center; font-size: 10px; color: #8b97a6; }
</style>
</head>
<body>
    <div class="hero">
        ' . $logoHtml . '
        <h1>Smart Garage</h1>
        <p>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' | Genere le ' . htmlspecialchars($dateText, ENT_QUOTES, 'UTF-8') . '</p>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr>' . $thead . '</tr></thead>
            <tbody>' . $tbody . '</tbody>
        </table>
    </div>
    <div class="footer">Document Smart Garage</div>
</body>
</html>';
    }

    private function buildCommandeDetailHtml(array $commande)
    {
        $logoHtml = $this->buildLogoHtml();
        $client = trim((string) (($commande['prenom_client'] ?? '') . ' ' . ($commande['nom_client'] ?? '')));
        $dateCommande = !empty($commande['date_commande']) ? date('d/m/Y H:i', strtotime((string) $commande['date_commande'])) : '-';
        $price = number_format((float) ($commande['piece_prix_unitaire'] ?? 0), 2, ',', ' ');
        $total = number_format((float) ($commande['montant_total'] ?? 0), 2, ',', ' ');
        return '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 34px; }
    body { font-family: DejaVu Sans, sans-serif; color: #1e3550; font-size: 12px; }
    .sheet { border: 1px solid #d9dee5; border-radius: 24px; overflow: hidden; }
    .header { background: linear-gradient(135deg, #173252, #c43d2f); color: #ffffff; padding: 24px 28px; }
    .brand { display: inline-flex; align-items: center; gap: 12px; }
    .brand img { width: 40px; height: 40px; object-fit: contain; background: #ffffff; border-radius: 12px; padding: 6px; }
    .brand span { font-size: 20px; font-weight: bold; }
    .header h1 { margin: 14px 0 6px; font-size: 28px; }
    .header p { margin: 0; color: rgba(255,255,255,0.82); }
    .content { padding: 26px 28px 30px; background: #ffffff; }
    .topline { display: table; width: 100%; margin-bottom: 22px; }
    .topline .left { display: table-cell; width: 100%; vertical-align: top; }
    .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #8b97a6; margin-bottom: 6px; }
    .value { font-size: 15px; font-weight: bold; }
    .card { border: 1px solid #e6ebf1; border-radius: 18px; padding: 18px; margin-top: 16px; }
    .card h2 { margin: 0 0 12px; font-size: 18px; }
    .grid { display: table; width: 100%; }
    .grid .row { display: table-row; }
    .grid .cell-label, .grid .cell-value { display: table-cell; padding: 8px 0; border-bottom: 1px solid #edf1f5; }
    .grid .cell-label { width: 42%; color: #6f7e8f; }
    .grid .last .cell-label, .grid .last .cell-value { border-bottom: none; }
    .totals { margin-top: 18px; border-radius: 18px; background: #f4f7fb; padding: 18px; }
    .totals .amount { font-size: 28px; font-weight: bold; color: #173252; }
    .footer { margin-top: 18px; color: #8b97a6; font-size: 10px; text-align: center; }
</style>
</head>
<body>
    <div class="sheet">
        <div class="header">
            ' . $logoHtml . '
            <h1>Commande #' . (int) $commande['id_commande'] . '</h1>
            <p>Document client Smart Garage | ' . htmlspecialchars($dateCommande, ENT_QUOTES, 'UTF-8') . '</p>
        </div>
        <div class="content">
            <div class="topline">
                <div class="left">
                    <div class="label">Client</div>
                    <div class="value">' . htmlspecialchars($client, ENT_QUOTES, 'UTF-8') . '</div>
                </div>
            </div>

            <div class="card">
                <h2>Details de la commande</h2>
                <div class="grid">
                    <div class="row"><div class="cell-label">Reference piece</div><div class="cell-value">' . htmlspecialchars((string) ($commande['piece_reference'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</div></div>
                    <div class="row"><div class="cell-label">Nom de la piece</div><div class="cell-value">' . htmlspecialchars((string) ($commande['piece_nom'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</div></div>
                    <div class="row"><div class="cell-label">Telephone client</div><div class="cell-value">' . htmlspecialchars((string) ($commande['telephone'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</div></div>
                    <div class="row"><div class="cell-label">Prix unitaire</div><div class="cell-value">' . $price . ' DT</div></div>
                    <div class="row last"><div class="cell-label">Quantite</div><div class="cell-value">' . (int) ($commande['quantite'] ?? 0) . '</div></div>
                </div>
            </div>

            <div class="totals">
                <div class="label">Montant total</div>
                <div class="amount">' . $total . ' DT</div>
            </div>

            <div class="footer">Merci pour votre confiance | Smart Garage</div>
        </div>
    </div>
</body>
</html>';
    }

    private function buildLogoHtml()
    {
        $logoPath = realpath(__DIR__ . '/../views/assets/images/logo-custom.png');
        if ($logoPath === false || !is_file($logoPath)) {
            return '<div class="brand"><span>Smart Garage</span></div>';
        }

        $normalized = str_replace('\\', '/', $logoPath);
        $src = 'file:///' . ltrim($normalized, '/');

        return '<div class="brand"><img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="Smart Garage Logo"><span>Smart Garage</span></div>';
    }

    private function downloadPdf($html, $filename, $orientation = 'landscape')
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    private function ensurePdfEnabled($fallbackAction)
    {
        if ($this->pdfEnabled) {
            return true;
        }

        $message = $this->pdfError !== ''
            ? $this->pdfError
            : 'Export PDF indisponible: dependance Dompdf introuvable.';

        header('Location: index.php?action=' . rawurlencode($fallbackAction) . '&error=' . rawurlencode($message));
        exit;
    }

    private function getAutoloadCandidates()
    {
        $candidates = [];
        $envPath = getenv('SMART_GARAGE_VENDOR_AUTOLOAD');

        if ($envPath !== false && trim($envPath) !== '') {
            $candidates[] = $envPath;
        }

        $candidates[] = dirname(__DIR__) . '/vendor/autoload.php';
        $candidates[] = dirname(__DIR__, 2) . '/vendor/autoload.php';
        $candidates[] = dirname(__DIR__, 3) . '/vendor/autoload.php';

        return array_values(array_unique($candidates));
    }

    private function getAllCommandes()
    {
        $sql = 'SELECT
                    c.*,
                    p.nom AS piece_nom,
                    p.reference AS piece_reference,
                    p.prix_unitaire AS piece_prix_unitaire
                FROM commandes c
                INNER JOIN pieces p ON p.id_piece = c.id_piece
                ORDER BY c.date_commande DESC, c.id_commande DESC';

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    private function getCommandeById($id)
    {
        $sql = 'SELECT
                    c.*,
                    p.nom AS piece_nom,
                    p.reference AS piece_reference,
                    p.prix_unitaire AS piece_prix_unitaire
                FROM commandes c
                INNER JOIN pieces p ON p.id_piece = c.id_piece
                WHERE c.id_commande = :id_commande';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_commande' => (int) $id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }
}
