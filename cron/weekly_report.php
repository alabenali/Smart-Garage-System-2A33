<?php
// ============================================
// Smart Garage – Rapport Hebdomadaire (logique fonctionnelle)
// ============================================

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../libs/phpmailer/src/Exception.php';
require_once __DIR__ . '/../libs/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Fonction principale : génère le rapport hebdomadaire complet
 * @return array ['success' => bool, 'message' => string, 'logs' => array]
 */
function generateWeeklyReport(): array
{
    $startTime = microtime(true);
    $logs = [];
    $db = Database::getInstance()->getConnection();

    // 1. Calculer la période (semaine précédente lundi→dimanche)
    $mondayLast = new DateTime('last monday');
    $mondayLast->setTime(0, 0, 0);
    $sundayLast = clone $mondayLast;
    $sundayLast->modify('+6 days');
    $sundayLast->setTime(23, 59, 59);

    $dateDebut = $mondayLast->format('Y-m-d H:i:s');
    $dateFin   = $sundayLast->format('Y-m-d H:i:s');
    $periodeLabel = $mondayLast->format('d/m/Y') . ' au ' . $sundayLast->format('d/m/Y');

    $logs[] = "Période : {$periodeLabel}";

    // 2. Récupérer les stats
    $stats = collectWeeklyStats($db, $dateDebut, $dateFin, $periodeLabel, $mondayLast);
    $logs[] = "Total RDV : {$stats['total_rdv']}";

    // 3. Commentaire IA
    $aiComment = generateAIComment($stats);
    $logs[] = "IA : " . (strpos($aiComment, 'indisponible') !== false ? 'FALLBACK' : 'OK');

    // 4. Générer PDF
    $pdfFileName = 'rapport_semaine_' . $mondayLast->format('Y-m-d') . '.pdf';
    $pdfPath = __DIR__ . '/../storage/' . $pdfFileName;

    if (!is_dir(dirname($pdfPath))) {
        mkdir(dirname($pdfPath), 0755, true);
    }

    generateReportPdf($stats, $aiComment, $pdfPath);
    $logs[] = "PDF généré : {$pdfFileName}";

    // 5. Envoyer email
    $emailResult = sendReportEmail($stats, $aiComment, $pdfPath, $periodeLabel);
    $logs[] = "Email : " . ($emailResult ? 'SUCCESS' : 'FAIL');

    // 6. Supprimer le PDF temporaire
    if (file_exists($pdfPath)) {
        @unlink($pdfPath);
    }

    // 7. Logger
    $elapsed = round(microtime(true) - $startTime, 2);
    $logLine = '[' . date('Y-m-d H:i:s') . '] | RDV: ' . $stats['total_rdv']
             . ' | EMAIL: ' . ($emailResult ? 'SUCCESS' : 'FAIL')
             . ' | TIME: ' . $elapsed . 's';

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logDir . '/weekly_report.log', $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);

    $logs[] = "Durée : {$elapsed}s";

    return [
        'success' => $emailResult,
        'message' => $emailResult
            ? "Rapport envoyé avec succès à " . GERANT_EMAIL
            : "Le rapport a été généré mais l'envoi email a échoué.",
        'logs' => $logs
    ];
}

/**
 * Collecte les statistiques de la semaine
 */
function collectWeeklyStats(PDO $db, string $dateDebut, string $dateFin, string $periodeLabel, DateTime $mondayLast): array
{
    $stats = [
        'periode'                  => $periodeLabel,
        'total_rdv'                => 0,
        'rdv_confirmes'            => 0,
        'rdv_annules'              => 0,
        'rdv_en_attente'           => 0,
        'taux_occupation'          => 0,
        'taux_annulation'          => 0,
        'top_pannes'               => [],
        'rdv_par_jour'             => [],
        'nouveaux_vehicules'       => 0,
        'vehicules_total'          => 0,
        'heure_pic'                => 'N/A',
        'rdv_urgents'              => 0,
        'semaine_precedente_total' => 0,
    ];

    // Total RDV de la semaine
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $stats['total_rdv'] = (int) $stmt->fetchColumn();

    // Par statut
    $stmt = $db->prepare("
        SELECT r.statut, COUNT(*) AS nb
        FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
        GROUP BY r.statut
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        switch ($row['statut']) {
            case 'Confirmé':
            case 'Terminé':
            case 'En cours':
                $stats['rdv_confirmes'] += (int) $row['nb'];
                break;
            case 'Annulé':
                $stats['rdv_annules'] = (int) $row['nb'];
                break;
            case 'En attente':
                $stats['rdv_en_attente'] = (int) $row['nb'];
                break;
        }
    }

    // Taux
    if ($stats['total_rdv'] > 0) {
        $stats['taux_annulation'] = round(($stats['rdv_annules'] / $stats['total_rdv']) * 100, 1);
    }

    // Capacité totale de la semaine (créneaux × capacité)
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(capacite_max), 0) AS cap
        FROM creneau_atelier
        WHERE date_heure BETWEEN :d1 AND :d2
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $capaciteTotale = (int) $stmt->fetchColumn();
    if ($capaciteTotale > 0) {
        $stats['taux_occupation'] = round(($stats['total_rdv'] / $capaciteTotale) * 100, 1);
    }

    // Top pannes
    $stmt = $db->prepare("
        SELECT r.type_intervention, COUNT(*) AS nb
        FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
        GROUP BY r.type_intervention
        ORDER BY nb DESC
        LIMIT 5
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $stats['top_pannes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // RDV par jour
    $stmt = $db->prepare("
        SELECT DATE(c.date_heure) AS jour, COUNT(*) AS nb
        FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
        GROUP BY DATE(c.date_heure)
        ORDER BY jour ASC
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $stats['rdv_par_jour'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nouveaux véhicules de la semaine
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM vehicle WHERE date_ajout BETWEEN :d1 AND :d2
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $stats['nouveaux_vehicules'] = (int) $stmt->fetchColumn();

    // Total véhicules
    $stmt = $db->query("SELECT COUNT(*) FROM vehicle");
    $stats['vehicules_total'] = (int) $stmt->fetchColumn();

    // Heure de pic
    $stmt = $db->prepare("
        SELECT HOUR(c.date_heure) AS h, COUNT(*) AS nb
        FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
        GROUP BY HOUR(c.date_heure)
        ORDER BY nb DESC
        LIMIT 1
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $picRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($picRow) {
        $stats['heure_pic'] = sprintf('%02d:00', $picRow['h']);
    }

    // RDV urgents (score >= 7)
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
          AND r.urgence_score >= 7
    ");
    $stmt->execute([':d1' => $dateDebut, ':d2' => $dateFin]);
    $stats['rdv_urgents'] = (int) $stmt->fetchColumn();

    // Semaine précédente (N-2) pour comparaison
    $prevMonday = clone $mondayLast;
    $prevMonday->modify('-7 days');
    $prevSunday = clone $prevMonday;
    $prevSunday->modify('+6 days');
    $prevSunday->setTime(23, 59, 59);

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM rendezvous_digital r
        INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
        WHERE c.date_heure BETWEEN :d1 AND :d2
    ");
    $stmt->execute([':d1' => $prevMonday->format('Y-m-d H:i:s'), ':d2' => $prevSunday->format('Y-m-d H:i:s')]);
    $stats['semaine_precedente_total'] = (int) $stmt->fetchColumn();

    return $stats;
}

/**
 * Génère un commentaire IA via OpenAI
 */
function generateAIComment(array $stats): string
{
    $fallback = 'Commentaire IA indisponible cette semaine.';

    $apiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (empty($apiKey)) {
        return $fallback;
    }

    $systemPrompt = "Tu es un analyste pour un garage automobile tunisien. "
        . "Tu rédiges un commentaire professionnel en français (3-4 phrases). "
        . "Tu mentionnes points positifs et négatifs. "
        . "Termine par une recommandation concrète. "
        . "Ne commence jamais par 'Voici' ou 'Cette semaine'.";

    $payload = json_encode([
        'model'    => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => json_encode($stats, JSON_UNESCAPED_UNICODE)],
        ],
        'max_tokens'  => 300,
        'temperature' => 0.7,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        return $fallback;
    }

    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';

    return !empty(trim($content)) ? trim($content) : $fallback;
}

/**
 * Génère le PDF du rapport hebdomadaire via TCPDF
 */
function generateReportPdf(array $stats, string $aiComment, string $outputPath): void
{
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Smart Garage');
    $pdf->SetAuthor('Smart Garage');
    $pdf->SetTitle('Rapport Hebdomadaire - ' . $stats['periode']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // Couleurs
    $primary   = [41, 128, 185];
    $dark      = [44, 62, 80];
    $lightGray = [236, 240, 241];
    $white     = [255, 255, 255];
    $green     = [39, 174, 96];
    $red       = [231, 76, 60];

    // En-tête avec fond
    $pdf->SetFillColor(...$primary);
    $pdf->Rect(0, 0, 210, 45, 'F');
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 22);
    $pdf->SetY(10);
    $pdf->Cell(0, 10, 'SMART GARAGE', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Rapport Hebdomadaire', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $stats['periode'], 0, 1, 'C');

    // KPIs principaux
    $pdf->SetY(52);
    $pdf->SetTextColor(...$dark);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Indicateurs Clés (KPI)', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(...$lightGray);

    $kpis = [
        ['Total RDV',           $stats['total_rdv']],
        ['RDV Confirmés',       $stats['rdv_confirmes']],
        ['RDV Annulés',         $stats['rdv_annules']],
        ['RDV En attente',      $stats['rdv_en_attente']],
        ['Taux d\'occupation',  $stats['taux_occupation'] . '%'],
        ['Taux d\'annulation',  $stats['taux_annulation'] . '%'],
        ['RDV Urgents (≥7)',    $stats['rdv_urgents']],
        ['Heure de pic',        $stats['heure_pic']],
    ];

    $fill = false;
    foreach ($kpis as $kpi) {
        $pdf->SetFillColor(...$lightGray);
        $pdf->Cell(90, 8, $kpi[0], 0, 0, 'L', $fill);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(90, 8, $kpi[1], 0, 1, 'R', $fill);
        $pdf->SetFont('helvetica', '', 10);
        $fill = !$fill;
    }

    // Commentaire IA
    $pdf->Ln(5);
    $pdf->SetFillColor(...$primary);
    $pdf->SetTextColor(...$white);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 9, ' Analyse IA', 0, 1, 'L', true);
    $pdf->SetTextColor(...$dark);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Ln(2);
    $pdf->MultiCell(0, 6, $aiComment, 0, 'L');

    // Top pannes
    if (!empty($stats['top_pannes'])) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 9, 'Top Pannes', 0, 1, 'L');

        $pdf->SetFillColor(...$primary);
        $pdf->SetTextColor(...$white);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(120, 8, 'Type d\'intervention', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Nombre', 1, 1, 'C', true);

        $pdf->SetTextColor(...$dark);
        $pdf->SetFont('helvetica', '', 10);
        $fill = false;
        foreach ($stats['top_pannes'] as $panne) {
            $pdf->SetFillColor(...$lightGray);
            $pdf->Cell(120, 7, $panne['type_intervention'], 1, 0, 'L', $fill);
            $pdf->Cell(60, 7, $panne['nb'], 1, 1, 'C', $fill);
            $fill = !$fill;
        }
    }

    // RDV par jour
    if (!empty($stats['rdv_par_jour'])) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 9, 'RDV par Jour', 0, 1, 'L');

        $pdf->SetFillColor(...$primary);
        $pdf->SetTextColor(...$white);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(90, 8, 'Jour', 1, 0, 'L', true);
        $pdf->Cell(90, 8, 'Nombre de RDV', 1, 1, 'C', true);

        $pdf->SetTextColor(...$dark);
        $pdf->SetFont('helvetica', '', 10);
        $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        $fill = false;
        foreach ($stats['rdv_par_jour'] as $jour) {
            $d = new DateTime($jour['jour']);
            $jourNom = $jours[(int)$d->format('w')] . ' ' . $d->format('d/m');
            $pdf->SetFillColor(...$lightGray);
            $pdf->Cell(90, 7, $jourNom, 1, 0, 'L', $fill);
            $pdf->Cell(90, 7, $jour['nb'], 1, 1, 'C', $fill);
            $fill = !$fill;
        }
    }

    // Statistiques secondaires
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 9, 'Statistiques Secondaires', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    $secondary = [
        ['Nouveaux véhicules (semaine)',  $stats['nouveaux_vehicules']],
        ['Total véhicules (parc)',        $stats['vehicules_total']],
        ['RDV semaine N-1 (comparaison)', $stats['semaine_precedente_total']],
    ];

    $fill = false;
    foreach ($secondary as $s) {
        $pdf->SetFillColor(...$lightGray);
        $pdf->Cell(120, 8, $s[0], 0, 0, 'L', $fill);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 8, $s[1], 0, 1, 'R', $fill);
        $pdf->SetFont('helvetica', '', 10);
        $fill = !$fill;
    }

    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 6, 'Smart Garage - Rapport genere le ' . date('d/m/Y a H:i'), 0, 0, 'C');

    $pdf->Output($outputPath, 'F');
}

/**
 * Envoie le rapport par email via PHPMailer
 */
function sendReportEmail(array $stats, string $aiComment, string $pdfPath, string $periode): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, 'Smart Garage');
        $mail->addAddress(GERANT_EMAIL);

        $mail->isHTML(true);
        $mail->Subject = '[Smart Garage] Rapport semaine du ' . $periode;

        // Corps HTML
        $htmlBody = '
        <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;background:#f8f9fa;padding:20px;border-radius:10px;">
            <div style="background:#2980b9;color:#fff;padding:20px;border-radius:8px 8px 0 0;text-align:center;">
                <h1 style="margin:0;font-size:24px;">🔧 Smart Garage</h1>
                <p style="margin:5px 0 0;font-size:14px;">Rapport Hebdomadaire – ' . htmlspecialchars($periode) . '</p>
            </div>
            <div style="background:#fff;padding:20px;border-radius:0 0 8px 8px;">
                <h2 style="color:#2c3e50;font-size:16px;border-bottom:2px solid #2980b9;padding-bottom:8px;">📊 Indicateurs Clés</h2>
                <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                    <tr style="background:#ecf0f1;"><td style="padding:8px;">Total RDV</td><td style="padding:8px;text-align:right;font-weight:bold;">' . $stats['total_rdv'] . '</td></tr>
                    <tr><td style="padding:8px;">RDV Confirmés</td><td style="padding:8px;text-align:right;font-weight:bold;color:#27ae60;">' . $stats['rdv_confirmes'] . '</td></tr>
                    <tr style="background:#ecf0f1;"><td style="padding:8px;">RDV Annulés</td><td style="padding:8px;text-align:right;font-weight:bold;color:#e74c3c;">' . $stats['rdv_annules'] . '</td></tr>
                    <tr><td style="padding:8px;">RDV En attente</td><td style="padding:8px;text-align:right;font-weight:bold;color:#f39c12;">' . $stats['rdv_en_attente'] . '</td></tr>
                    <tr style="background:#ecf0f1;"><td style="padding:8px;">Taux d\'occupation</td><td style="padding:8px;text-align:right;font-weight:bold;">' . $stats['taux_occupation'] . '%</td></tr>
                    <tr><td style="padding:8px;">Taux d\'annulation</td><td style="padding:8px;text-align:right;font-weight:bold;">' . $stats['taux_annulation'] . '%</td></tr>
                    <tr style="background:#ecf0f1;"><td style="padding:8px;">RDV Urgents</td><td style="padding:8px;text-align:right;font-weight:bold;">' . $stats['rdv_urgents'] . '</td></tr>
                    <tr><td style="padding:8px;">Heure de pic</td><td style="padding:8px;text-align:right;font-weight:bold;">' . htmlspecialchars($stats['heure_pic']) . '</td></tr>
                </table>

                <h2 style="color:#2c3e50;font-size:16px;border-bottom:2px solid #2980b9;padding-bottom:8px;">🤖 Analyse IA</h2>
                <p style="color:#555;line-height:1.6;font-style:italic;background:#f0f4f8;padding:12px;border-radius:6px;border-left:4px solid #2980b9;">'
                . nl2br(htmlspecialchars($aiComment)) .
                '</p>

                <div style="margin-top:20px;padding:12px;background:#d5f5e3;border-radius:6px;text-align:center;">
                    <p style="margin:0;color:#27ae60;font-weight:bold;">📎 Le rapport PDF complet est en pièce jointe.</p>
                </div>
            </div>
            <p style="text-align:center;color:#999;font-size:11px;margin-top:15px;">Smart Garage – Système de gestion automobile</p>
        </div>';

        $mail->Body = $htmlBody;
        $mail->AltBody = "Rapport Smart Garage - {$periode}\nTotal RDV: {$stats['total_rdv']}\n{$aiComment}";

        if (file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Smart Garage] Email error: ' . $e->getMessage());
        return false;
    }
}
