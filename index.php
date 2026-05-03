<?php
// ============================================
// Smart Garage System – Main Router (index.php)
// ============================================
require_once __DIR__ . '/controllers/DiagnosticController.php';
require_once __DIR__ . '/controllers/InterventionController.php';

$diagController = new DiagnosticController();
$interventionController = new InterventionController();

// Read action from query string
$action = isset($_GET['action']) ? $_GET['action'] : 'diagnostics';

// Build conversation previews (count, last message metadata) for a list of interventions.
$buildConversationPreviews = function (array $interventions, int $vehicleId = 0) use ($interventionController): array {
    $previews = [];

    foreach ($interventions as $inter) {
        $interventionId = (int)($inter['id_intervention'] ?? 0);
        if ($interventionId <= 0) {
            continue;
        }

        $conversationMessages = $interventionController->listMessages($interventionId, $vehicleId);
        $lastMessage = !empty($conversationMessages)
            ? $conversationMessages[count($conversationMessages) - 1]
            : null;

        $previews[$interventionId] = [
            'count' => count($conversationMessages),
            'last_content' => $lastMessage['contenu'] ?? '',
            'last_date' => $lastMessage['date_envoi'] ?? null,
            'last_sender' => $lastMessage['expediteur'] ?? null,
        ];
    }

    return $previews;
};

// Sort interventions to keep the freshest conversation at the top.
$sortInterventionsByLastMessage = function (array &$interventions, array $conversationPreviews): void {
    usort($interventions, function ($a, $b) use ($conversationPreviews) {
        $aId = (int)($a['id_intervention'] ?? 0);
        $bId = (int)($b['id_intervention'] ?? 0);

        $aLastDate = $conversationPreviews[$aId]['last_date'] ?? null;
        $bLastDate = $conversationPreviews[$bId]['last_date'] ?? null;

        $aTs = $aLastDate ? strtotime((string)$aLastDate) : 0;
        $bTs = $bLastDate ? strtotime((string)$bLastDate) : 0;

        if ($aTs === $bTs) {
            return $bId <=> $aId;
        }

        return $bTs <=> $aTs;
    });
};

// Resolve selected client intervention with safe fallback to the first available one.
$resolveClientSelectedIntervention = function (array $interventions, int $vehicleId, int $requestedId) use ($interventionController): array {
    $selectedId = $requestedId;
    if ($selectedId <= 0 && !empty($interventions)) {
        $selectedId = (int)($interventions[0]['id_intervention'] ?? 0);
    }

    $selectedIntervention = null;
    if ($selectedId > 0) {
        $selectedIntervention = $interventionController->getClientInterventionDetail($selectedId, $vehicleId);
    }

    if (empty($selectedIntervention) && !empty($interventions)) {
        $fallbackId = (int)($interventions[0]['id_intervention'] ?? 0);
        if ($fallbackId > 0) {
            $selectedIntervention = $interventionController->getClientInterventionDetail($fallbackId, $vehicleId);
            $selectedId = $fallbackId;
        }
    }

    return [
        'id' => $selectedId,
        'intervention' => $selectedIntervention,
    ];
};

switch ($action) {
    case 'dashboard':
        $interventions = $interventionController->getAll(null, 100, 0);
        $statistiques = $interventionController->getStatistiques();
        require __DIR__ . '/views/back/dashboard.php';
        break;

    case 'mes_diagnostics':
        $diagController->handleRequest();
        $vehicles = $diagController->listVehicles();
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
        if ($vehicleId > 0) {
            $diagnostics = $diagController->listByVehicule($vehicleId);
        } else {
            $diagnostics = [];
        }
        require __DIR__ . '/views/front/mes_diagnostics.php';
        break;

    case 'client_dashboard':
        $vehicles = $diagController->listVehicles();
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
        $statsClient = $interventionController->getClientDashboardStats($vehicleId);
        $latestInterventions = $interventionController->getClientInterventions($vehicleId, 5, 0);
        $action = 'client_dashboard';
        require __DIR__ . '/views/front/dashboard_client.php';
        break;

    case 'client_interventions':
        $vehicles = $diagController->listVehicles();
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
        $interventions = $interventionController->getClientInterventions($vehicleId, 100, 0);
        $action = 'client_interventions';
        require __DIR__ . '/views/front/interventions.php';
        break;

    case 'client_messages':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $interventionController->handleRequest();
            $idIntervention = isset($_POST['id_intervention']) ? (int)$_POST['id_intervention'] : 0;
            $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
            $qs = !empty($result['success']) ? 'sent=1' : 'error=1';
            header('Location: index.php?action=client_messages&id=' . $idIntervention . '&vehicle_id=' . $vehicleId . '&' . $qs);
            exit();
        }

        $vehicles = $diagController->listVehicles();
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
        $interventions = $interventionController->getClientInterventions($vehicleId, 100, 0);
        $conversationPreviews = $buildConversationPreviews($interventions, $vehicleId);
        $sortInterventionsByLastMessage($interventions, $conversationPreviews);

        $selectedData = $resolveClientSelectedIntervention(
            $interventions,
            $vehicleId,
            isset($_GET['id']) ? (int)$_GET['id'] : 0
        );
        $selectedInterventionId = (int)$selectedData['id'];
        $selectedIntervention = $selectedData['intervention'];

        $messages = [];
        if (!empty($selectedIntervention)) {
            $messages = $interventionController->listMessages((int)$selectedIntervention['id_intervention'], $vehicleId);
        }

        $action = 'client_messages';
        require __DIR__ . '/views/front/messages.php';
        break;

    case 'intervention_detail':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $interventionController->handleRequest();
            $idIntervention = isset($_POST['id_intervention']) ? (int)$_POST['id_intervention'] : 0;
            $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
            $qs = !empty($result['success']) ? 'updated=1' : 'error=1';
            header('Location: index.php?action=intervention_detail&id=' . $idIntervention . '&vehicle_id=' . $vehicleId . '&' . $qs);
            exit();
        }

        $vehicles = $diagController->listVehicles();
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
        $idIntervention = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $intervention = $interventionController->getClientInterventionDetail($idIntervention, $vehicleId);
        if (!$intervention) {
            header('Location: index.php?action=client_interventions&vehicle_id=' . $vehicleId . '&error=1');
            exit();
        }
        $recentMessages = $interventionController->listMessages($idIntervention, $vehicleId);
        $action = 'client_interventions';
        require __DIR__ . '/views/front/intervention_detail.php';
        break;

    case 'intervention_chat':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $interventionController->handleRequest();
            $idIntervention = isset($_POST['id_intervention']) ? (int)$_POST['id_intervention'] : 0;
            $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
            $qs = !empty($result['success']) ? 'sent=1' : 'error=1';
            header('Location: index.php?action=intervention_chat&id=' . $idIntervention . '&vehicle_id=' . $vehicleId . '&' . $qs);
            exit();
        }

        $vehicles = $diagController->listVehicles();
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
        $interventions = $interventionController->getClientInterventions($vehicleId, 100, 0);
        $conversationPreviews = $buildConversationPreviews($interventions, $vehicleId);
        $sortInterventionsByLastMessage($interventions, $conversationPreviews);

        $selectedData = $resolveClientSelectedIntervention(
            $interventions,
            $vehicleId,
            isset($_GET['id']) ? (int)$_GET['id'] : 0
        );
        $selectedInterventionId = (int)$selectedData['id'];
        $selectedIntervention = $selectedData['intervention'];

        $messages = [];
        if (!empty($selectedIntervention)) {
            $messages = $interventionController->listMessages((int)$selectedIntervention['id_intervention'], $vehicleId);
        }

        $action = 'client_interventions';
        require __DIR__ . '/views/front/intervention_chat.php';
        break;

    case 'diagnostics':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postedAction = $_POST['action'] ?? '';
            $idDiagnostic = isset($_POST['id_diagnostic']) ? (int)$_POST['id_diagnostic'] : 0;

            if ($postedAction === 'accept_request') {
                if ($idDiagnostic <= 0) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                $acceptResult = $diagController->acceptDiagnostic($idDiagnostic);
                error_log('Accept diagnostic #' . $idDiagnostic . ' result: ' . ($acceptResult ? 'true' : 'false'));

                if (!$acceptResult) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                $existingIntervention = $interventionController->getByDiagnostic($idDiagnostic);
                if ($existingIntervention) {
                    header('Location: index.php?action=admin_interventions&already_exists=1');
                    exit();
                }

                // After acceptance, admin must confirm via intervention form.
                header('Location: index.php?action=create_intervention&id_diagnostic=' . $idDiagnostic);
                exit();
            }

            if ($postedAction === 'refuse_request') {
                if ($idDiagnostic <= 0) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                $reason = trim((string)($_POST['raison_refus'] ?? 'Demande refusée par l\'administrateur'));
                $refuseResult = $diagController->refuseDiagnostic($idDiagnostic, $reason);

                if (!$refuseResult) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                header('Location: index.php?action=diagnostics&refused=1');
                exit();
            }

            if ($postedAction === 'update_diagnostic') {
                if ($idDiagnostic <= 0) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                $diagnosticData = [
                    'id_diagnostic' => $idDiagnostic,
                    'id_vehicule' => isset($_POST['id_vehicule']) ? (int)$_POST['id_vehicule'] : 0,
                    'description_probleme' => $_POST['description_probleme'] ?? '',
                    'resultat' => $_POST['resultat'] ?? '',
                    'gravite' => $_POST['gravite'] ?? '',
                    'montant_estime' => isset($_POST['montant_estime']) ? (float)$_POST['montant_estime'] : 0,
                    'status' => $_POST['status'] ?? '',
                    'date_diagnostic' => $_POST['date_diagnostic'] ?? '',
                ];

                $updateResult = $diagController->updateDiagnostic($diagnosticData);

                if (!$updateResult) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                header('Location: index.php?action=diagnostics&updated=1');
                exit();
            }

            if ($postedAction === 'delete_diagnostic') {
                if ($idDiagnostic <= 0) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                $deleteResult = $diagController->deleteDiagnostic($idDiagnostic);

                if (!$deleteResult) {
                    header('Location: index.php?action=diagnostics&error=validation');
                    exit();
                }

                header('Location: index.php?action=diagnostics&deleted=1');
                exit();
            }

            $diagController->handleRequest();
        }

        $vehicles = $diagController->listVehicles();
        $diagnostics = $diagController->list();
        require __DIR__ . '/views/back/diagnostics.php';
        break;

    case 'create_intervention':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Traiter la création d'intervention et retourner JSON
            header('Content-Type: application/json; charset=utf-8');
            
            $idDiagnostic = isset($_POST['id_diagnostic']) ? (int)$_POST['id_diagnostic'] : 0;
            $idTypes = isset($_POST['id_type']) && is_array($_POST['id_type']) ? $_POST['id_type'] : [];
            $description = isset($_POST['description_travail']) ? trim((string)$_POST['description_travail']) : '';
            $coutInitial = isset($_POST['cout_initial']) ? (float)$_POST['cout_initial'] : 0;
            
            // Convertir les types en JSON
            $typesJson = !empty($idTypes) ? json_encode(array_map('intval', $idTypes)) : null;
            
            // Appeler directement la création et retourner le résultat JSON
            $result = $interventionController->createMultiTypes($idDiagnostic, $typesJson, $description, $coutInitial);
            
            echo json_encode($result);
            exit();
        }
        
        // GET requests: rediriger vers admin_interventions
        header('Location: index.php?action=admin_interventions');
        exit();
        break;

    case 'admin_interventions':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $interventionController->handleRequest();
            if (!empty($result['success'])) {
                $actionType = $_POST['action_type'] ?? '';
                $postedAction = $_POST['action'] ?? '';
                if ($actionType === 'terminate') {
                    header('Location: index.php?action=admin_interventions&intervention_updated=1');
                } elseif ($actionType === 'update_quote') {
                    header('Location: index.php?action=admin_interventions&quote_updated=1');
                } elseif ($actionType === 'send_quote_email') {
                    header('Location: index.php?action=admin_interventions&quote_email_sent=1');
                } elseif ($postedAction === 'send_message') {
                    header('Location: index.php?action=admin_interventions&message_sent=1');
                } else {
                    header('Location: index.php?action=admin_interventions&status_updated=1');
                }
                exit();
            }
            $actionType = $_POST['action_type'] ?? '';
            if ($actionType === 'send_quote_email') {
                $mailMsg = isset($result['message']) ? urlencode((string)$result['message']) : '';
                header('Location: index.php?action=admin_interventions&mail_error=1&mail_msg=' . $mailMsg);
            } else {
                header('Location: index.php?action=admin_interventions&error=1');
            }
            exit();
        }

        $action = 'admin_interventions';
        $interventions = $interventionController->getAll();
        $types_intervention = $interventionController->getTypesIntervention();
        $allDiagnostics = $diagController->list();
        $diagnosticsDisponibles = [];
        foreach ($allDiagnostics as $diag) {
            $diagId = (int)($diag['id_diagnostic'] ?? 0);
            if ($diagId <= 0) {
                continue;
            }

            $existingIntervention = $interventionController->getByDiagnostic($diagId);
            if (!$existingIntervention) {
                $diagnosticsDisponibles[] = $diag;
            }
        }

        $interventionMessages = [];
        foreach ($interventions as $inter) {
            $iid = (int)($inter['id_intervention'] ?? 0);
            if ($iid > 0) {
                $interventionMessages[$iid] = $interventionController->listMessages($iid, 0);
            }
        }
        $statistiques = $interventionController->getStatistiques();
        require __DIR__ . '/views/back/admin_interventions.php';
        break;

    case 'messages':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $interventionController->handleRequest();
            $idIntervention = isset($_POST['id_intervention']) ? (int)$_POST['id_intervention'] : 0;
            $qs = !empty($result['success']) ? 'sent=1' : 'error=1';
            header('Location: index.php?action=messages&id=' . $idIntervention . '&' . $qs);
            exit();
        }

        $action = 'messages';
        $interventions = $interventionController->getAll(null, 200, 0);
        $conversationPreviews = $buildConversationPreviews($interventions, 0);
        $sortInterventionsByLastMessage($interventions, $conversationPreviews);

        $selectedInterventionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($selectedInterventionId <= 0 && !empty($interventions)) {
            $selectedInterventionId = (int)($interventions[0]['id_intervention'] ?? 0);
        }

        $selectedIntervention = null;
        if ($selectedInterventionId > 0) {
            $selectedIntervention = $interventionController->getById($selectedInterventionId);
        }

        $messages = [];
        if (!empty($selectedIntervention)) {
            $messages = $interventionController->listMessages((int)$selectedIntervention['id_intervention'], 0);
        }

        require __DIR__ . '/views/back/messages.php';
        break;

    case 'generateDiagnosticPdf':
        $diagController->generateDiagnosticPdf();
        break;

    case 'export_intervention_pdf':
        $idIntervention = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $interventionController->exportInterventionPdf($idIntervention);
        break;

    case 'export_quote_pdf':
        $idIntervention = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $interventionController->exportQuotePdf($idIntervention);
        break;

    // Backward compatibility with previous route name.
    case 'export_intervention_file':
        $idIntervention = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $interventionController->exportInterventionPdf($idIntervention);
        break;

    default:
        $diagController->handleRequest();
        $stats = $diagController->stats();
        $diagnostics = $diagController->list();
        require __DIR__ . '/views/back/dashboard.php';
        break;
}
