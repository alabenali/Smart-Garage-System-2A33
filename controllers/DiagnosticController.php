<?php
require_once __DIR__ . '/../config/Database.php';

class DiagnosticController {

    private $diagnosticColumns = null;
    private $vehicleColumns = null;

    private function diagnosticSelectSql() {
        return 'SELECT d.*, v.immatriculation FROM diagnostic d LEFT JOIN vehicle v ON d.id_vehicule = v.id';
    }

    private function getVehicleColumns() {
        if ($this->vehicleColumns !== null) {
            return $this->vehicleColumns;
        }

        $db = Database::getInstance()->getConnection();
        $columns = [];

        try {
            $query = $db->query('SHOW COLUMNS FROM vehicle');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }

        $this->vehicleColumns = $columns;
        return $this->vehicleColumns;
    }

    private function resolveVehicleColumn(array $candidates) {
        $columns = $this->getVehicleColumns();

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function selectVehicleColumn(array $candidates, $alias) {
        $column = $this->resolveVehicleColumn($candidates);
        return $column ? 'v.' . $column . ' AS ' . $alias : 'NULL AS ' . $alias;
    }

    private function getDiagnosticColumns() {
        if ($this->diagnosticColumns !== null) {
            return $this->diagnosticColumns;
        }

        $db = Database::getInstance()->getConnection();
        $columns = [];

        try {
            $query = $db->query('SHOW COLUMNS FROM diagnostic');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }

        // Auto-migrate media and status fields when they do not exist in legacy schemas.
        $hasMediaPath = in_array('media_path', $columns, true)
            || in_array('media', $columns, true)
            || in_array('image', $columns, true)
            || in_array('photo', $columns, true)
            || in_array('image_path', $columns, true)
            || in_array('photo_path', $columns, true)
            || in_array('piece_jointe', $columns, true)
            || in_array('fichier', $columns, true);
        $hasMediaType = in_array('media_type', $columns, true)
            || in_array('media_mime', $columns, true)
            || in_array('mime_type', $columns, true)
            || in_array('file_type', $columns, true)
            || in_array('type_fichier', $columns, true);
        $hasStatus = in_array('status', $columns, true) || in_array('statut', $columns, true);

        if (!$hasMediaPath || !$hasMediaType || !$hasStatus) {
            try {
                if (!$hasMediaPath) {
                    $db->exec('ALTER TABLE diagnostic ADD COLUMN media_path VARCHAR(255) DEFAULT NULL');
                }

                if (!$hasMediaType) {
                    $db->exec('ALTER TABLE diagnostic ADD COLUMN media_type VARCHAR(100) DEFAULT NULL');
                }

                if (!$hasStatus) {
                    $db->exec("ALTER TABLE diagnostic ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'en attente'");
                    $db->exec("UPDATE diagnostic SET status = 'en attente' WHERE status IS NULL OR TRIM(status) = ''");
                }

                $query = $db->query('SHOW COLUMNS FROM diagnostic');
                $columns = [];
                foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $columns[] = $row['Field'];
                }
            } catch (Exception $e) {
                // Keep working even if migration is not permitted.
            }
        }

        $this->diagnosticColumns = $columns;
        return $this->diagnosticColumns;
    }

    private function resolveDiagnosticColumn(array $candidates) {
        $columns = $this->getDiagnosticColumns();

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function selectDiagnosticColumn(array $candidates, $alias) {
        $column = $this->resolveDiagnosticColumn($candidates);
        return $column ? 'd.' . $column . ' AS ' . $alias : 'NULL AS ' . $alias;
    }

    private function diagnosticOrderColumn() {
        return $this->resolveDiagnosticColumn(['date_diagnostic', 'date', 'created_at', 'id_diagnostic']) ?: 'id_diagnostic';
    }

    private function diagnosticIdColumn() {
        return $this->resolveDiagnosticColumn(['id_diagnostic', 'id']) ?: 'id_diagnostic';
    }

    private function diagnosticVehicleIdColumn() {
        return $this->resolveDiagnosticColumn(['id_vehicule', 'vehicle_id']) ?: 'id_vehicule';
    }

    private function buildDiagnosticSelectSql() {
        $vehicleIdColumn = $this->diagnosticVehicleIdColumn();
        return 'SELECT '
            . $this->selectDiagnosticColumn(['id_diagnostic', 'id'], 'id_diagnostic') . ', '
            . $this->selectDiagnosticColumn(['id_vehicule', 'vehicle_id'], 'id_vehicule') . ', '
            . $this->selectDiagnosticColumn(['description_probleme', 'description'], 'description_probleme') . ', '
            . $this->selectDiagnosticColumn(['resultat', 'result'], 'resultat') . ', '
            . $this->selectDiagnosticColumn(['gravite'], 'gravite') . ', '
            . $this->selectDiagnosticColumn(['montant_estime', 'montant'], 'montant_estime') . ', '
            . $this->selectDiagnosticColumn(['status', 'statut'], 'status') . ', '
            . $this->selectDiagnosticColumn(['date_diagnostic', 'date'], 'date_diagnostic') . ', '
            . $this->selectDiagnosticColumn(['media_path', 'media', 'image', 'photo', 'image_path', 'photo_path', 'piece_jointe', 'fichier'], 'media_path') . ', '
            . $this->selectDiagnosticColumn(['media_type', 'media_mime', 'mime_type', 'file_type', 'type_fichier'], 'media_type') . ', '
                . $this->selectVehicleColumn(['immatriculation', 'matricule'], 'immatriculation') . ', '
                . $this->selectVehicleColumn(['marque', 'brand'], 'vehicle_marque') . ', '
                . $this->selectVehicleColumn(['modele', 'model'], 'vehicle_modele') . ', '
                . $this->selectVehicleColumn(['photo', 'image', 'photo_path', 'image_path'], 'vehicle_photo') . ' '
            . 'FROM diagnostic d LEFT JOIN vehicle v ON d.' . $vehicleIdColumn . ' = v.id';
    }

    private function uploadDiagnosticMedia($file) {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [false, false];
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            return [false, false];
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogg',
        ];

        $detectedMimeType = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
            }
        }

        if (!$detectedMimeType || !isset($allowedMimeTypes[$detectedMimeType])) {
            return [false, false];
        }

        $uploadDir = __DIR__ . '/../uploads/diagnostics/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            return [false, false];
        }

        $extension = $allowedMimeTypes[$detectedMimeType];
        $fileName = 'diagnostic_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [false, false];
        }

        return ['uploads/diagnostics/' . $fileName, $detectedMimeType];
    }

    public function listVehicles() {
        $db = Database::getInstance()->getConnection();

        try {
            $query = $db->query('SELECT id, immatriculation FROM vehicle ORDER BY immatriculation ASC');
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function list() {
        $db = Database::getInstance()->getConnection();

        try {
            $orderColumn = $this->diagnosticOrderColumn();
            $liste = $db->query($this->buildDiagnosticSelectSql() . ' ORDER BY d.' . $orderColumn . ' DESC');
            return $liste->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function listByVehicule($idVehicule) {
        $db = Database::getInstance()->getConnection();

        try {
            $orderColumn = $this->diagnosticOrderColumn();
            $vehicleIdColumn = $this->diagnosticVehicleIdColumn();
            $query = $db->prepare($this->buildDiagnosticSelectSql() . ' WHERE d.' . $vehicleIdColumn . ' = :id_vehicule ORDER BY d.' . $orderColumn . ' DESC');
            $query->execute([
                'id_vehicule' => (int)$idVehicule,
            ]);

            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function stats() {
        $db = Database::getInstance()->getConnection();

        try {
            $statusColumn = $this->getStatusColumnName();
            $statusCol = $statusColumn ? $statusColumn : 'status';
            $normalizedStatusExpr = "LOWER(TRIM(REPLACE(REPLACE(" . $statusCol . ", '_', ' '), 'é', 'e')))";
            $normalizedGraviteExpr = "LOWER(TRIM(REPLACE(REPLACE(gravite, 'É', 'E'), 'é', 'e')))";
            $amountColumn = $this->resolveDiagnosticColumn(['montant_estime', 'montant']);
            $amountExpr = $amountColumn ? 'COALESCE(' . $amountColumn . ', 0)' : '0';

            $total = (int)$db->query('SELECT COUNT(*) FROM diagnostic')->fetchColumn();
            $completed = (int)$db->query("SELECT COUNT(*) FROM diagnostic WHERE " . $normalizedStatusExpr . " IN ('termine', 'terminee')")->fetchColumn();
            
            return [
                'total' => $total,
                'urgent' => (int)$db->query("SELECT COUNT(*) FROM diagnostic WHERE " . $normalizedGraviteExpr . " IN ('eleve', 'urgent', 'haute')")->fetchColumn(),
                'waiting' => (int)$db->query("SELECT COUNT(*) FROM diagnostic WHERE " . $normalizedStatusExpr . " IN ('en attente', 'en attente de traitement', 'en cours')")->fetchColumn(),
                'completed' => $completed,
                'accepted' => (int)$db->query("SELECT COUNT(*) FROM diagnostic WHERE " . $normalizedStatusExpr . " IN ('accepte', 'acceptee')")->fetchColumn(),
                'refused' => (int)$db->query("SELECT COUNT(*) FROM diagnostic WHERE " . $normalizedStatusExpr . " IN ('refuse', 'refusee')")->fetchColumn(),
                'estimated_total' => (float)$db->query("SELECT COALESCE(SUM(" . $amountExpr . "), 0) FROM diagnostic")->fetchColumn(),
                'estimated_avg' => (float)$db->query("SELECT COALESCE(AVG(" . $amountExpr . "), 0) FROM diagnostic")->fetchColumn(),
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function addDiagnostic($diagnostic) {
        $db = Database::getInstance()->getConnection();

        try {
            $idVehicule = isset($diagnostic['id_vehicule']) ? (int)$diagnostic['id_vehicule'] : 0;
            $descriptionProbleme = trim((string)($diagnostic['description_probleme'] ?? ''));
            $resultat = trim((string)($diagnostic['resultat'] ?? 'En attente de traitement'));
            $gravite = trim((string)($diagnostic['gravite'] ?? 'Faible'));
            $montantEstime = isset($diagnostic['montant_estime']) ? (float)$diagnostic['montant_estime'] : 0;
            $status = trim((string)($diagnostic['status'] ?? 'en attente'));
            $dateDiagnostic = !empty($diagnostic['date_diagnostic']) ? $diagnostic['date_diagnostic'] : date('Y-m-d');
            $mediaPath = $diagnostic['media_path'] ?? null;
            $mediaType = $diagnostic['media_type'] ?? null;

            if ($idVehicule <= 0 || $descriptionProbleme === '') {
                return false;
            }

            $columns = $this->getDiagnosticColumns();
            $fieldMap = [
                'id_vehicule' => $idVehicule,
                'vehicle_id' => $idVehicule,
                'description_probleme' => $descriptionProbleme,
                'description' => $descriptionProbleme,
                'resultat' => $resultat,
                'result' => $resultat,
                'gravite' => $gravite,
                'montant_estime' => $montantEstime,
                'montant' => $montantEstime,
                'status' => $status,
                'statut' => $status,
                'date_diagnostic' => $dateDiagnostic,
                'date' => $dateDiagnostic,
                'media_path' => $mediaPath,
                'media' => $mediaPath,
                'image' => $mediaPath,
                'photo' => $mediaPath,
                'image_path' => $mediaPath,
                'photo_path' => $mediaPath,
                'piece_jointe' => $mediaPath,
                'fichier' => $mediaPath,
                'media_type' => $mediaType,
                'media_mime' => $mediaType,
                'mime_type' => $mediaType,
                'file_type' => $mediaType,
                'type_fichier' => $mediaType,
            ];

            $insertFields = [];
            $placeholders = [];
            $values = [];

            foreach ($fieldMap as $column => $value) {
                if (in_array($column, $columns, true) && !in_array($column, $insertFields, true)) {
                    $insertFields[] = $column;
                    $placeholders[] = ':' . $column;
                    $values[$column] = $value;
                }
            }

            if (empty($insertFields)) {
                return false;
            }

            $query = $db->prepare('INSERT INTO diagnostic (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $placeholders) . ')');
            $query->execute($values);

            return true;
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function addDiagnosticAndGetId($diagnostic) {
        $db = Database::getInstance()->getConnection();

        try {
            $idVehicule = isset($diagnostic['id_vehicule']) ? (int)$diagnostic['id_vehicule'] : 0;
            $descriptionProbleme = trim((string)($diagnostic['description_probleme'] ?? ''));
            $resultat = trim((string)($diagnostic['resultat'] ?? 'En attente de traitement'));
            $gravite = trim((string)($diagnostic['gravite'] ?? 'Faible'));
            $montantEstime = isset($diagnostic['montant_estime']) ? (float)$diagnostic['montant_estime'] : 0;
            $status = trim((string)($diagnostic['status'] ?? 'en attente'));
            $dateDiagnostic = !empty($diagnostic['date_diagnostic']) ? $diagnostic['date_diagnostic'] : date('Y-m-d');
            $mediaPath = $diagnostic['media_path'] ?? null;
            $mediaType = $diagnostic['media_type'] ?? null;

            if ($idVehicule <= 0 || $descriptionProbleme === '') {
                return 0;
            }

            $columns = $this->getDiagnosticColumns();
            $fieldMap = [
                'id_vehicule' => $idVehicule,
                'vehicle_id' => $idVehicule,
                'description_probleme' => $descriptionProbleme,
                'description' => $descriptionProbleme,
                'resultat' => $resultat,
                'result' => $resultat,
                'gravite' => $gravite,
                'montant_estime' => $montantEstime,
                'montant' => $montantEstime,
                'status' => $status,
                'statut' => $status,
                'date_diagnostic' => $dateDiagnostic,
                'date' => $dateDiagnostic,
                'media_path' => $mediaPath,
                'media' => $mediaPath,
                'image' => $mediaPath,
                'photo' => $mediaPath,
                'image_path' => $mediaPath,
                'photo_path' => $mediaPath,
                'piece_jointe' => $mediaPath,
                'fichier' => $mediaPath,
                'media_type' => $mediaType,
                'media_mime' => $mediaType,
                'mime_type' => $mediaType,
                'file_type' => $mediaType,
                'type_fichier' => $mediaType,
            ];

            $insertFields = [];
            $placeholders = [];
            $values = [];

            foreach ($fieldMap as $column => $value) {
                if (in_array($column, $columns, true) && !in_array($column, $insertFields, true)) {
                    $insertFields[] = $column;
                    $placeholders[] = ':' . $column;
                    $values[$column] = $value;
                }
            }

            if (empty($insertFields)) {
                return 0;
            }

            $query = $db->prepare('INSERT INTO diagnostic (' . implode(', ', $insertFields) . ') VALUES (' . implode(', ', $placeholders) . ')');
            $query->execute($values);

            return (int)$db->lastInsertId();
        } catch (Exception $e) {
            error_log('Erreur addDiagnosticAndGetId: ' . $e->getMessage());
            return 0;
        }
    }

    public function updateDiagnostic($diagnostic) {
        $db = Database::getInstance()->getConnection();

        try {
            $columns = $this->getDiagnosticColumns();
            $updateParts = [];
            $values = [
                'id_diagnostic' => (int)$diagnostic['id_diagnostic'],
            ];

            $updates = [
                'description_probleme' => $diagnostic['description_probleme'] ?? null,
                'description' => $diagnostic['description_probleme'] ?? null,
                'resultat' => $diagnostic['resultat'] ?? null,
                'result' => $diagnostic['resultat'] ?? null,
                'gravite' => $diagnostic['gravite'] ?? null,
                'montant_estime' => $diagnostic['montant_estime'] ?? null,
                'montant' => $diagnostic['montant_estime'] ?? null,
                'status' => $diagnostic['status'] ?? null,
                'statut' => $diagnostic['status'] ?? null,
                'date_diagnostic' => $diagnostic['date_diagnostic'] ?? null,
                'date' => $diagnostic['date_diagnostic'] ?? null,
            ];

            foreach ($updates as $column => $value) {
                if (in_array($column, $columns, true)) {
                    $updateParts[] = $column . ' = :' . $column;
                    $values[$column] = $value;
                }
            }

            if (empty($updateParts)) {
                return false;
            }

            $idColumn = $this->diagnosticIdColumn();
            $query = $db->prepare('UPDATE diagnostic SET ' . implode(', ', $updateParts) . ' WHERE ' . $idColumn . ' = :id_diagnostic');
            $query->execute($values);

            return true;
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function deleteDiagnostic($id) {
        $db = Database::getInstance()->getConnection();

        try {
            $idColumn = $this->diagnosticIdColumn();
            
            // Supprimer d'abord les interventions associées
            $db->prepare('DELETE FROM intervention WHERE id_diagnostic = :id')->execute([
                'id' => (int)$id,
            ]);
            
            // Puis supprimer le diagnostic
            $query = $db->prepare('DELETE FROM diagnostic WHERE ' . $idColumn . ' = :id');
            $query->execute([
                'id' => (int)$id,
            ]);

            return true;
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function updateDiagnosticStatus($id, $status) {
        $db = Database::getInstance()->getConnection();

        try {
            $columns = $this->getDiagnosticColumns();
            $statusColumn = in_array('status', $columns, true) ? 'status' : (in_array('statut', $columns, true) ? 'statut' : null);

            if (!$statusColumn) {
                return false;
            }

            $idColumn = $this->diagnosticIdColumn();
            $query = $db->prepare('UPDATE diagnostic SET ' . $statusColumn . ' = :status WHERE ' . $idColumn . ' = :id');
            $query->execute([
                'id' => (int)$id,
                'status' => $status,
            ]);

            return true;
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function updateDiagnosticMedia($id, $mediaPath, $mediaType) {
        $db = Database::getInstance()->getConnection();

        try {
            $mediaPathColumn = $this->resolveDiagnosticColumn(['media_path', 'media', 'image', 'photo', 'image_path', 'photo_path', 'piece_jointe', 'fichier']);
            $mediaTypeColumn = $this->resolveDiagnosticColumn(['media_type', 'media_mime', 'mime_type', 'file_type', 'type_fichier']);

            if (!$mediaPathColumn && !$mediaTypeColumn) {
                return false;
            }

            $setParts = [];
            $values = ['id' => (int)$id];

            if ($mediaPathColumn) {
                $setParts[] = $mediaPathColumn . ' = :media_path';
                $values['media_path'] = $mediaPath;
            }

            if ($mediaTypeColumn) {
                $setParts[] = $mediaTypeColumn . ' = :media_type';
                $values['media_type'] = $mediaType;
            }

            $idColumn = $this->diagnosticIdColumn();
            $query = $db->prepare('UPDATE diagnostic SET ' . implode(', ', $setParts) . ' WHERE ' . $idColumn . ' = :id');
            $query->execute($values);

            return $query->rowCount() > 0;
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function getDiagnosticById($id) {
        $db = Database::getInstance()->getConnection();

        try {
            $idColumn = $this->diagnosticIdColumn();
            $query = $db->prepare($this->buildDiagnosticSelectSql() . ' WHERE d.' . $idColumn . ' = :id');
            $query->execute([
                'id' => (int)$id,
            ]);

            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            die('Erreur: ' . $e->getMessage());
        }
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
            return;
        }

        switch ($_POST['action']) {
            case 'add_admin_diagnostic':
                $uploadResult = $this->uploadDiagnosticMedia($_FILES['media_file'] ?? null);
                if ($uploadResult === [false, false]) {
                    header('Location: index.php?action=diagnostics&error=media');
                    exit();
                }

                $diagnostic = [
                    'id_vehicule' => isset($_POST['id_vehicule']) ? (int)$_POST['id_vehicule'] : 0,
                    'description_probleme' => trim((string)($_POST['description_probleme'] ?? '')),
                    'resultat' => trim((string)($_POST['resultat'] ?? 'Diagnostic atelier saisi par le mecanicien')),
                    'gravite' => 'Moyen',
                    'montant_estime' => isset($_POST['montant_estime']) ? (float)$_POST['montant_estime'] : 0,
                    'status' => trim((string)($_POST['status'] ?? 'accepte')),
                    'date_diagnostic' => !empty($_POST['date_diagnostic']) ? (string)$_POST['date_diagnostic'] : date('Y-m-d'),
                    'media_path' => $uploadResult[0],
                    'media_type' => $uploadResult[1],
                ];

                $createdId = $this->addDiagnosticAndGetId($diagnostic);
                if ($createdId > 0) {
                    $createInterventionNow = isset($_POST['create_intervention_now']) && $_POST['create_intervention_now'] === '1';
                    if ($createInterventionNow) {
                        header('Location: index.php?action=create_intervention&id_diagnostic=' . $createdId);
                        exit();
                    }

                    header('Location: index.php?action=diagnostics&created=1');
                    exit();
                }

                header('Location: index.php?action=diagnostics&error=validation');
                exit();

            case 'add_client':
                $uploadResult = $this->uploadDiagnosticMedia($_FILES['media_file'] ?? null);
                if ($uploadResult === [false, false]) {
                    header('Location: index.php?action=mes_diagnostics&error=media');
                    exit();
                }

                $diagnostic = [
                    'id_vehicule' => isset($_POST['id_vehicule']) ? (int)$_POST['id_vehicule'] : 0,
                    'description_probleme' => trim((string)($_POST['description_probleme'] ?? '')),
                    'resultat' => 'En attente de traitement',
                    'gravite' => trim((string)($_POST['gravite'] ?? 'Faible')),
                    'montant_estime' => 0,
                    'status' => 'en attente',
                    'date_diagnostic' => date('Y-m-d'),
                    'media_path' => $uploadResult[0],
                    'media_type' => $uploadResult[1],
                ];

                if ($this->addDiagnostic($diagnostic)) {
                    $redirectVehicle = (int)$diagnostic['id_vehicule'];
                    header('Location: index.php?action=mes_diagnostics&vehicle_id=' . $redirectVehicle . '&created=1');
                    exit();
                }

                header('Location: index.php?action=mes_diagnostics&error=1');
                exit();

            case 'add_media':
                $idDiagnostic = isset($_POST['id_diagnostic']) ? (int)$_POST['id_diagnostic'] : 0;
                $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;
                $uploadResult = $this->uploadDiagnosticMedia($_FILES['media_file'] ?? null);

                if ($idDiagnostic <= 0 || $uploadResult === [false, false] || $uploadResult === [null, null]) {
                    $location = 'index.php?action=mes_diagnostics&error=media';
                    if ($vehicleId > 0) {
                        $location .= '&vehicle_id=' . $vehicleId;
                    }

                    header('Location: ' . $location);
                    exit();
                }

                if ($this->updateDiagnosticMedia($idDiagnostic, $uploadResult[0], $uploadResult[1])) {
                    $location = 'index.php?action=mes_diagnostics&media_updated=1';
                    if ($vehicleId > 0) {
                        $location .= '&vehicle_id=' . $vehicleId;
                    }

                    header('Location: ' . $location);
                    exit();
                }

                $location = 'index.php?action=mes_diagnostics&error=media';
                if ($vehicleId > 0) {
                    $location .= '&vehicle_id=' . $vehicleId;
                }

                header('Location: ' . $location);
                exit();

            case 'delete':
                $id = isset($_POST['id_diagnostic']) ? (int)$_POST['id_diagnostic'] : 0;
                if ($id > 0) {
                    $this->deleteDiagnostic($id);
                    header('Location: index.php?action=diagnostics&deleted=1');
                    exit();
                }
                break;

            case 'update_status':
                $id = isset($_POST['id_diagnostic']) ? (int)$_POST['id_diagnostic'] : 0;
                $status = trim((string)($_POST['status'] ?? ''));
                $vehicleId = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : 0;

                if ($id > 0 && $status !== '') {
                    $this->updateDiagnosticStatus($id, $status);
                    $location = 'index.php?action=mes_diagnostics&updated=1';
                    if ($vehicleId > 0) {
                        $location .= '&vehicle_id=' . $vehicleId;
                    }

                    header('Location: ' . $location);
                    exit();
                }
                break;
        }

        header('Location: index.php?action=diagnostics&error=1');
        exit();
    }

    // ============================================
    // Acceptance/Refusal Management
    // ============================================
    private function getStatusColumnName() {
        $columns = $this->getDiagnosticColumns();
        if (in_array('status', $columns, true)) {
            return 'status';
        }
        if (in_array('statut', $columns, true)) {
            return 'statut';
        }
        return null;
    }

    private function getDiagnosticStatusAllowedValues($statusColumn) {
        $db = Database::getInstance()->getConnection();

        try {
            $query = $db->query('SHOW COLUMNS FROM diagnostic LIKE ' . $db->quote($statusColumn));
            $row = $query ? $query->fetch(PDO::FETCH_ASSOC) : null;
            if (!$row || empty($row['Type'])) {
                return [];
            }

            if (preg_match('/^enum\((.*)\)$/i', (string)$row['Type'], $matches) !== 1) {
                return [];
            }

            $raw = $matches[1];
            $parts = str_getcsv($raw, ',', "'");
            $values = [];
            foreach ($parts as $part) {
                $v = trim((string)$part);
                if ($v !== '') {
                    $values[] = $v;
                }
            }
            return $values;
        } catch (Exception $e) {
            return [];
        }
    }

    private function resolveAcceptedStatusValue($statusColumn) {
        $allowed = $this->getDiagnosticStatusAllowedValues($statusColumn);
        if (empty($allowed)) {
            return 'accepte';
        }

        $candidates = ['accepte', 'accepté', 'en_cours', 'en cours', 'termine', 'terminé'];
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return $allowed[0];
    }

    private function resolveRefusedStatusValue($statusColumn) {
        $allowed = $this->getDiagnosticStatusAllowedValues($statusColumn);
        if (empty($allowed)) {
            return 'refuse';
        }

        $candidates = ['refuse', 'refusé', 'annule', 'annulé', 'termine', 'terminé'];
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return $allowed[0];
    }

    /**
     * Récupère la liste des diagnostics en attente (pour l'admin)
     */
    public function getPendingDiagnostics() {
        $db = Database::getInstance()->getConnection();

        try {
            $statusColumn = $this->getStatusColumnName();
            $orderColumn = $this->diagnosticOrderColumn();

            if (!$statusColumn) {
                $query = $db->query($this->buildDiagnosticSelectSql() . ' ORDER BY d.' . $orderColumn . ' DESC');
                return $query->fetchAll(PDO::FETCH_ASSOC);
            }

            $query = $db->query(
                $this->buildDiagnosticSelectSql()
                . " WHERE LOWER(TRIM(d." . $statusColumn . ")) IN ('en_attente', 'en attente', 'en attente de traitement')"
                . ' ORDER BY d.' . $orderColumn . ' DESC'
            );
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getPendingDiagnostics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la liste des diagnostics acceptés (en attente d'intervention)
     */
    public function getAcceptedDiagnostics() {
        $db = Database::getInstance()->getConnection();

        try {
            $statusColumn = $this->getStatusColumnName();
            if (!$statusColumn) {
                return [];
            }

            $acceptedStatus = $this->resolveAcceptedStatusValue($statusColumn);
            $orderColumn = $this->diagnosticOrderColumn();
            $query = $db->query($this->buildDiagnosticSelectSql() . " WHERE d." . $statusColumn . " = '" . $acceptedStatus . "' ORDER BY d." . $orderColumn . " DESC");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getAcceptedDiagnostics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Accepte un diagnostic et le change en statut 'accepte'
     */
    public function acceptDiagnostic($id, $resultat = null, $montant_estime = null) {
        $db = Database::getInstance()->getConnection();

        try {
            $columns = $this->getDiagnosticColumns();
            $statusColumn = $this->getStatusColumnName();
            $idColumn = $this->diagnosticIdColumn();

            // Legacy schema fallback: if no status/statut column exists,
            // still allow acceptance by ensuring the diagnostic exists.
            if (!$statusColumn) {
                $existsQuery = $db->prepare('SELECT COUNT(*) FROM diagnostic WHERE ' . $idColumn . ' = :id');
                $existsQuery->execute(['id' => (int)$id]);
                return ((int)$existsQuery->fetchColumn()) > 0;
            }

            $acceptedStatus = $this->resolveAcceptedStatusValue($statusColumn);
            $updates = [$statusColumn . ' = :status'];
            $values = [
                'id' => (int)$id,
                'status' => $acceptedStatus
            ];

            // Optionnellement mettre à jour le résultat et montant
            if ($resultat !== null && in_array('resultat', $columns, true)) {
                $updates[] = 'resultat = :resultat';
                $values['resultat'] = $resultat;
            }

            if ($montant_estime !== null && in_array('montant_estime', $columns, true)) {
                $updates[] = 'montant_estime = :montant_estime';
                $values['montant_estime'] = (float)$montant_estime;
            }

            $query = $db->prepare('UPDATE diagnostic SET ' . implode(', ', $updates) . ' WHERE ' . $idColumn . ' = :id');
            $result = $query->execute($values);

            return $result;
        } catch (Exception $e) {
            error_log('Erreur acceptDiagnostic: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refuse un diagnostic et le change en statut 'refuse'
     */
    public function refuseDiagnostic($id, $raison_refus = null) {
        $db = Database::getInstance()->getConnection();

        try {
            $columns = $this->getDiagnosticColumns();
            $statusColumn = $this->getStatusColumnName();
            $idColumn = $this->diagnosticIdColumn();

            // Legacy schema fallback: if no status/statut column exists,
            // still allow refusal by writing a refusal note when possible.
            if (!$statusColumn) {
                $existsQuery = $db->prepare('SELECT COUNT(*) FROM diagnostic WHERE ' . $idColumn . ' = :id');
                $existsQuery->execute(['id' => (int)$id]);
                if (((int)$existsQuery->fetchColumn()) <= 0) {
                    return false;
                }

                if ($raison_refus !== null && in_array('resultat', $columns, true)) {
                    $query = $db->prepare('UPDATE diagnostic SET resultat = :resultat WHERE ' . $idColumn . ' = :id');
                    $query->execute([
                        'id' => (int)$id,
                        'resultat' => 'REFUSÉ: ' . $raison_refus,
                    ]);
                }

                return true;
            }

            $refusedStatus = $this->resolveRefusedStatusValue($statusColumn);
            $updates = [$statusColumn . ' = :status'];
            $values = [
                'id' => (int)$id,
                'status' => $refusedStatus
            ];

            // Si une raison de refus est fournie et qu'une colonne existe
            if ($raison_refus !== null && in_array('resultat', $columns, true)) {
                $updates[] = 'resultat = :resultat';
                $values['resultat'] = 'REFUSÉ: ' . $raison_refus;
            }

            $query = $db->prepare('UPDATE diagnostic SET ' . implode(', ', $updates) . ' WHERE ' . $idColumn . ' = :id');
            $result = $query->execute($values);

            return $result;
        } catch (Exception $e) {
            error_log('Erreur refuseDiagnostic: ' . $e->getMessage());
            return false;
        }
    }

    public function generateDiagnosticPdf() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            header('Location: index.php?action=diagnostics&error=1');
            exit();
        }

        $diag = $this->getDiagnosticById($id);
        if (!$diag) {
            header('Location: index.php?action=diagnostics&error=1');
            exit();
        }

        $lines = [
            'Devis Diagnostic - Smart Garage',
            '',
            'Reference: #' . ($diag['id_diagnostic'] ?? ''),
            'Date: ' . (isset($diag['date_diagnostic']) ? date('d/m/Y', strtotime($diag['date_diagnostic'])) : date('d/m/Y')),
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

        $this->streamSimplePdf($lines, 'devis-diagnostic-' . $id . '.pdf');
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

            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $content .= sprintf("1 0 0 1 %d %d Tm (%s) Tj\n", $x, $y, $safe);
            $y -= $lineHeight;
        }

        $content .= "ET";

        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
        $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
        $objects[] = '5 0 obj << /Length ' . strlen($content) . " >> stream\n" . $content . "\nendstream endobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= 'startxref' . "\n" . $xrefOffset . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit();
    }
}
