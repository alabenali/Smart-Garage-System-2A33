<?php
// ============================================
// Intervention Controller
// ============================================
// Gère les interventions (CRUD, état, coûts)
// Une intervention est liée à un diagnostic accepté

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Intervention.php';
require_once __DIR__ . '/../models/Message.php';

class InterventionController {
    private $db;
    private $table = 'intervention';
    private $messageModel;
    private $typeInterventionColumns = null;
    private $diagnosticColumns = null;
    private $vehicleColumns = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->ensureInterventionSchema();
        $this->ensureTypeInterventionSchema();
        $this->messageModel = new Message();
    }

    private function ensureInterventionSchema() {
        try {
            $columns = [];
            $query = $this->db->query('SHOW COLUMNS FROM intervention');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }

            if (!in_array('statut_devis', $columns, true)) {
                $this->db->exec("ALTER TABLE intervention ADD COLUMN statut_devis ENUM('en_attente','accepte','refuse','en_negociation') NOT NULL DEFAULT 'en_attente'");
            }
            if (!in_array('devis_pdf_path', $columns, true)) {
                $this->db->exec('ALTER TABLE intervention ADD COLUMN devis_pdf_path VARCHAR(255) DEFAULT NULL');
            }
            if (!in_array('date_envoi_devis', $columns, true)) {
                $this->db->exec('ALTER TABLE intervention ADD COLUMN date_envoi_devis DATETIME DEFAULT NULL');
            }
            if (!in_array('date_reponse_devis', $columns, true)) {
                $this->db->exec('ALTER TABLE intervention ADD COLUMN date_reponse_devis DATETIME DEFAULT NULL');
            }
            if (!in_array('type_prices', $columns, true)) {
                $this->db->exec("ALTER TABLE intervention ADD COLUMN type_prices TEXT DEFAULT NULL");
            }
            if (!in_array('type_total', $columns, true)) {
                $this->db->exec("ALTER TABLE intervention ADD COLUMN type_total DECIMAL(10,2) DEFAULT 0.00");
            }
        } catch (Exception $e) {
            // Keep controller operational even when schema migration is not permitted.
        }
    }

    private function ensureTypeInterventionSchema() {
        try {
            $columns = [];
            $query = $this->db->query('SHOW COLUMNS FROM type_intervention');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }

            if (!in_array('prix', $columns, true)) {
                $this->db->exec("ALTER TABLE type_intervention ADD COLUMN prix DECIMAL(10,2) NOT NULL DEFAULT 0.00");
            }
        } catch (Exception $e) {
            // Keep controller operational even when schema migration is not permitted.
        }
    }

    private function getTypeInterventionColumns() {
        if ($this->typeInterventionColumns !== null) {
            return $this->typeInterventionColumns;
        }

        $columns = [];
        try {
            $query = $this->db->query('SHOW COLUMNS FROM type_intervention');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $e) {
            $columns = [];
        }

        $this->typeInterventionColumns = $columns;
        return $this->typeInterventionColumns;
    }

    private function resolveTypeInterventionColumn(array $candidates) {
        $columns = $this->getTypeInterventionColumns();
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return null;
    }

    private function typeInterventionIdColumn() {
        return $this->resolveTypeInterventionColumn(['id_type', 'id']) ?: 'id_type';
    }

    private function typeInterventionNameSelect($alias = 'type_nom') {
        $column = $this->resolveTypeInterventionColumn(['nom', 'nom_type', 'label']);
        return $column ? ('t.' . $column . ' AS ' . $alias) : ('NULL AS ' . $alias);
    }

    private function typeInterventionDescriptionSelect($alias = 'description') {
        $column = $this->resolveTypeInterventionColumn(['description', 'details']);
        return $column ? ('t.' . $column . ' AS ' . $alias) : ('NULL AS ' . $alias);
    }

    private function typeInterventionPrixSelect($alias = 'type_prix') {
        $column = $this->resolveTypeInterventionColumn(['prix']);
        return $column ? ('t.' . $column . ' AS ' . $alias) : ('NULL AS ' . $alias);
    }

    private function getDiagnosticColumns() {
        if ($this->diagnosticColumns !== null) {
            return $this->diagnosticColumns;
        }

        $columns = [];
        try {
            $query = $this->db->query('SHOW COLUMNS FROM diagnostic');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $e) {
            $columns = [];
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

    private function diagnosticIdColumn() {
        return $this->resolveDiagnosticColumn(['id_diagnostic', 'id']) ?: 'id_diagnostic';
    }

    private function diagnosticStatusColumn() {
        return $this->resolveDiagnosticColumn(['status', 'statut', 'etat', 'state']);
    }

    private function diagnosticVehicleIdColumn() {
        return $this->resolveDiagnosticColumn(['id_vehicule', 'vehicle_id']);
    }

    private function selectDiagnosticColumn(array $candidates, $alias) {
        $column = $this->resolveDiagnosticColumn($candidates);
        return $column ? ('d.' . $column . ' AS ' . $alias) : ('NULL AS ' . $alias);
    }

    private function getVehicleColumns() {
        if ($this->vehicleColumns !== null) {
            return $this->vehicleColumns;
        }

        $columns = [];
        try {
            $query = $this->db->query('SHOW COLUMNS FROM vehicle');
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row['Field'];
            }
        } catch (Exception $e) {
            $columns = [];
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
        return $column ? ('v.' . $column . ' AS ' . $alias) : ('NULL AS ' . $alias);
    }

    private function isAcceptedStatus($status) {
        if ($status === null || $status === '') {
            return true;
        }

        $normalized = mb_strtolower(trim((string)$status), 'UTF-8');
        $accepted = ['accepte', 'accepté', 'accepted', 'en_cours', 'en cours', 'termine', 'terminé'];
        return in_array($normalized, $accepted, true);
    }

    // ============================================
    // 1. CREATE - Créer une intervention
    // ============================================
    /**
     * Crée une nouvelle intervention après acceptation d'un diagnostic
     * Paramètres requis: id_diagnostic, id_type, description_travail, cout_initial
     * 
     * @param int $id_diagnostic
     * @param int $id_type
     * @param string $description_travail
     * @param float $cout_initial
     * @return array [success => bool, id_intervention => int|null, message => string]
     */
    public function create($id_diagnostic, $id_type, $description_travail, $cout_initial) {
        try {
            // Validation basique (id_type peut être vide)
            if (empty($id_diagnostic) || empty($description_travail) || $cout_initial === '') {
                return [
                    'success' => false,
                    'message' => 'Tous les champs sont requis'
                ];
            }

            if ($cout_initial < 0) {
                return [
                    'success' => false,
                    'message' => 'Le coût initial ne peut pas être négatif'
                ];
            }

            // Vérifier que le diagnostic existe et qu'il a le statut "accepte"
            $diagnostic = $this->getDiagnosticStatus($id_diagnostic);
            if (!$diagnostic) {
                return [
                    'success' => false,
                    'message' => 'Diagnostic non trouvé'
                ];
            }

            if (!$this->isAcceptedStatus($diagnostic['status'] ?? null)) {
                return [
                    'success' => false,
                    'message' => 'Le diagnostic doit être accepté avant de créer une intervention'
                ];
            }

            // Vérifier qu'une intervention n'existe pas déjà pour ce diagnostic
            $existing = $this->getByDiagnostic($id_diagnostic);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Une intervention existe déjà pour ce diagnostic'
                ];
            }

            // Vérifier que le type existe si fourni
            if (!empty($id_type) && !$this->typeInterventionExists($id_type)) {
                return [
                    'success' => false,
                    'message' => 'Type d\'intervention invalide'
                ];
            }

            // Préparer et exécuter la requête (id_type peut être NULL)
            $sql = "INSERT INTO {$this->table} 
                    (id_diagnostic, id_type, description_travail, cout_initial, statut, statut_devis) 
                    VALUES (?, ?, ?, ?, 'planifiée', 'en_attente')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id_diagnostic,
                !empty($id_type) ? $id_type : null,
                $description_travail,
                (float)$cout_initial
            ]);

            $id_intervention = $this->db->lastInsertId();

            return [
                'success' => true,
                'id_intervention' => $id_intervention,
                'message' => 'Intervention créée avec succès'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crée une intervention avec plusieurs types d'intervention (stockés en JSON)
     */
    public function createMultiTypes($id_diagnostic, $types_json, $description_travail, $cout_initial) {
        try {
            // Validation basique
            if (empty($id_diagnostic) || empty($description_travail) || $cout_initial === '') {
                return [
                    'success' => false,
                    'message' => 'Tous les champs sont requis'
                ];
            }

            if ($cout_initial < 0) {
                return [
                    'success' => false,
                    'message' => 'Le coût initial ne peut pas être négatif'
                ];
            }

            // Vérifier que le diagnostic existe et qu'il a le statut "accepte"
            $diagnostic = $this->getDiagnosticStatus($id_diagnostic);
            if (!$diagnostic) {
                return [
                    'success' => false,
                    'message' => 'Diagnostic non trouvé'
                ];
            }

            if (!$this->isAcceptedStatus($diagnostic['status'] ?? null)) {
                return [
                    'success' => false,
                    'message' => 'Le diagnostic doit être accepté avant de créer une intervention'
                ];
            }

            // Vérifier qu'une intervention n'existe pas déjà pour ce diagnostic
            $existing = $this->getByDiagnostic($id_diagnostic);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Une intervention existe déjà pour ce diagnostic'
                ];
            }

            // Valider les types si fournis
            if (!empty($types_json)) {
                $types = json_decode($types_json, true);
                if (!is_array($types)) {
                    return [
                        'success' => false,
                        'message' => 'Format de types invalide'
                    ];
                }
                
                // Vérifier que chaque type existe
                foreach ($types as $typeId) {
                    if (!$this->typeInterventionExists($typeId)) {
                        return [
                            'success' => false,
                            'message' => 'Type d\'intervention invalide: ' . $typeId
                        ];
                    }
                }
            }

            // Préparer et exécuter la requête avec types JSON
            $sql = "INSERT INTO {$this->table} 
                    (id_diagnostic, id_type, description_travail, cout_initial, statut, statut_devis) 
                    VALUES (?, ?, ?, ?, 'planifiée', 'en_attente')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id_diagnostic,
                $types_json,
                $description_travail,
                (float)$cout_initial
            ]);

            $id_intervention = $this->db->lastInsertId();

            return [
                'success' => true,
                'id_intervention' => $id_intervention,
                'message' => 'Intervention créée avec succès'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage()
            ];
        }
    }

    // ============================================
    // 2. READ - Récupérer les interventions
    // ============================================
    /**
     * Récupère une intervention par ID avec les infos diagnostic et type
     */
    public function getById($id_intervention) {
        try {
            $typeIdColumn = $this->typeInterventionIdColumn();
            $diagIdColumn = $this->diagnosticIdColumn();
            $diagVehicleIdColumn = $this->diagnosticVehicleIdColumn();
            $vehicleIdColumn = $this->resolveVehicleColumn(['id']) ?: 'id';
            $vehicleJoinCondition = $diagVehicleIdColumn
                ? ('d.' . $diagVehicleIdColumn . ' = v.' . $vehicleIdColumn)
                : '1 = 0';

            $sql = "SELECT i.*, "
                // $rows[] = ['designation' => 'Batterie', 'prix' => number_format($stdPrices['batterie'], 2, '.', ' ') . ' DT'];
                // $rows[] = ['designation' => 'Climatisation', 'prix' => number_format($stdPrices['climatisation'], 2, '.', ' ') . ' DT'];
                // $rows[] = ['designation' => 'Diagnostic électronique', 'prix' => number_format($stdPrices['diagnostic_electronique'], 2, '.', ' ') . ' DT'];
                . $this->selectDiagnosticColumn(['montant_estime', 'montant'], 'montant_estime') . ", "
                . $this->selectDiagnosticColumn(['id_vehicule', 'vehicle_id'], 'id_vehicule') . ", "
                . $this->selectVehicleColumn(['marque', 'brand'], 'vehicle_marque') . ", "
                . $this->selectVehicleColumn(['modele', 'model'], 'vehicle_modele') . ", "
                . $this->selectVehicleColumn(['immatriculation', 'license_plate', 'registration'], 'immatriculation') . ", "
                . $this->selectVehicleColumn(['couleur', 'color'], 'vehicle_couleur') . ", "
                . $this->selectVehicleColumn(['annee', 'year'], 'vehicle_annee') . ", "
                . $this->selectVehicleColumn(['kilometrage', 'km'], 'vehicle_km') . ", "
                . $this->selectVehicleColumn(['carburant', 'fuel'], 'vehicle_carburant') . ", "
                // $rows[] = ['designation' => 'Batterie', 'prix' => number_format($stdPrices['batterie'], 2, '.', ' ') . ' DT'];
                // $rows[] = ['designation' => 'Climatisation', 'prix' => number_format($stdPrices['climatisation'], 2, '.', ' ') . ' DT'];
                // $rows[] = ['designation' => 'Diagnostic électronique', 'prix' => number_format($stdPrices['diagnostic_electronique'], 2, '.', ' ') . ' DT'];
                . $this->typeInterventionPrixSelect('type_prix') . "
                    FROM {$this->table} i
                    JOIN diagnostic d ON i.id_diagnostic = d." . $diagIdColumn . "
                    LEFT JOIN vehicle v ON " . $vehicleJoinCondition . "
                    LEFT JOIN type_intervention t ON i.id_type = t." . $typeIdColumn . "
                    WHERE i.id_intervention = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_intervention]);
            $inter = $stmt->fetch();

            // Post-traitement: gérer les cas où `id_type` contient un JSON
            if ($inter) {
                $idTypeVal = $inter['id_type'] ?? null;
                // Si id_type est un JSON (liste de types), récupérer les noms et prix
                if (is_string($idTypeVal) && (str_starts_with(trim($idTypeVal), '[') || str_starts_with(trim($idTypeVal), '{'))) {
                    $typeIds = json_decode($idTypeVal, true);
                    if (is_array($typeIds) && !empty($typeIds)) {
                        $nameColumn = $this->resolveTypeInterventionColumn(['nom', 'nom_type', 'label']);
                        $priceColumn = $this->resolveTypeInterventionColumn(['prix']);
                        if ($nameColumn) {
                            $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
                            $selectCols = $nameColumn . ' AS nom';
                            if ($priceColumn) {
                                $selectCols .= ', ' . $priceColumn . ' AS prix';
                            }
                            $sqlTypes = "SELECT id_type, " . $selectCols . " FROM type_intervention WHERE id_type IN ($placeholders) ORDER BY " . $nameColumn;
                            $stmt2 = $this->db->prepare($sqlTypes);
                            $stmt2->execute(array_map('intval', $typeIds));
                            $types = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            if (!empty($types)) {
                                $names = array_map(fn($t) => $t['nom'], $types);
                                $inter['type_nom'] = implode(', ', $names);
                                $inter['type_items'] = $types;
                                if ($priceColumn) {
                                    $sum = 0.0;
                                    foreach ($types as $t) {
                                        $sum += (float)($t['prix'] ?? 0);
                                    }
                                    $inter['type_prix'] = $sum;
                                }
                            }
                        }
                    }
                }
            }

            return $inter;

        } catch (Exception $e) {
            error_log('Erreur getById: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère une intervention par ID diagnostic
     */
    public function getByDiagnostic($id_diagnostic) {
        try {
            $typeIdColumn = $this->typeInterventionIdColumn();
            $sql = "SELECT i.*, " . $this->typeInterventionNameSelect('type_nom') . "
                    FROM {$this->table} i
                    LEFT JOIN type_intervention t ON i.id_type = t." . $typeIdColumn . "
                    WHERE i.id_diagnostic = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_diagnostic]);
            return $stmt->fetch();

        } catch (Exception $e) {
            error_log('Erreur getByDiagnostic: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère toutes les interventions avec filtres optionnels
     */
    public function getAll($statut = null, $limit = 50, $offset = 0) {
        try {
            $diagIdColumn = $this->diagnosticIdColumn();
            $diagVehicleIdColumn = $this->diagnosticVehicleIdColumn();
            $vehicleIdColumn = $this->resolveVehicleColumn(['id']) ?: 'id';
            $vehicleJoinCondition = $diagVehicleIdColumn
                ? ('d.' . $diagVehicleIdColumn . ' = v.' . $vehicleIdColumn)
                : '1 = 0';

            $sql = "SELECT i.*, "
                . $this->selectDiagnosticColumn(['id_vehicule', 'vehicle_id'], 'id_vehicule') . ", "
                . $this->selectDiagnosticColumn(['description_probleme', 'description'], 'description_probleme') . ", "
                . $this->selectDiagnosticColumn(['gravite'], 'gravite') . ", "
                . $this->selectVehicleColumn(['marque', 'brand'], 'vehicle_marque') . ", "
                . $this->selectVehicleColumn(['modele', 'model'], 'vehicle_modele') . ", "
                . $this->selectVehicleColumn(['immatriculation', 'matricule'], 'immatriculation') . ", "
                . "'' AS type_nom "
                . "FROM {$this->table} i "
                . "JOIN diagnostic d ON i.id_diagnostic = d." . $diagIdColumn . " "
                . "LEFT JOIN vehicle v ON " . $vehicleJoinCondition;

            if ($statut) {
                $sql .= " WHERE i.statut = ?";
                $stmt = $this->db->prepare($sql . " ORDER BY i.date_debut DESC LIMIT ? OFFSET ?");
                $stmt->execute([$statut, $limit, $offset]);
            } else {
                $stmt = $this->db->prepare($sql . " ORDER BY i.date_debut DESC LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
            }

            // Post-traiter les résultats pour transformer id_type JSON en noms
            $results = $stmt->fetchAll();
            foreach ($results as &$row) {
                $row['type_nom'] = $this->getTypeNamesFromJson($row['id_type'] ?? null);
            }
            unset($row);

            return $results;

        } catch (Exception $e) {
            error_log('Erreur getAll: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Convertir JSON de types en noms séparés par virgule
     */
    private function getTypeNamesFromJson($typesJson) {
        if (empty($typesJson)) {
            return '-';
        }

        try {
            $typeIds = json_decode($typesJson, true);
            if (!is_array($typeIds) || empty($typeIds)) {
                return '-';
            }

            // Récupérer les noms des types (la colonne s'appelle nom_type)
            $placeholders = implode(',', array_fill(0, count($typeIds), '?'));
            $sql = "SELECT nom_type FROM type_intervention WHERE id_type IN ($placeholders) ORDER BY nom_type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($typeIds);
            $types = $stmt->fetchAll();

            if (empty($types)) {
                return '-';
            }

            $names = array_map(fn($t) => $t['nom_type'], $types);
            return implode(', ', $names);
        } catch (Exception $e) {
            error_log('Erreur getTypeNamesFromJson: ' . $e->getMessage());
            return '-';
        }
    }

    public function getClientDashboardStats($vehicleId = 0) {
        try {
            $diagVehicleIdColumn = $this->diagnosticVehicleIdColumn();
            $statusExpr = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(i.statut), 'é', 'e'), 'è', 'e'), 'ê', 'e'), 'à', 'a'))";

            $sql = "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN " . $statusExpr . " IN ('en_cours', 'en cours') THEN 1 ELSE 0 END) AS en_cours,
                        SUM(CASE WHEN " . $statusExpr . " IN ('terminee', 'termine') THEN 1 ELSE 0 END) AS terminees,
                        SUM(CASE WHEN i.statut_devis IN ('en_attente', 'en_negociation') THEN 1 ELSE 0 END) AS en_attente_devis
                    FROM {$this->table} i
                    JOIN diagnostic d ON i.id_diagnostic = d." . $this->diagnosticIdColumn();

            if ($diagVehicleIdColumn && (int)$vehicleId > 0) {
                $sql .= ' WHERE d.' . $diagVehicleIdColumn . ' = ?';
                $stmt = $this->db->prepare($sql);
                $stmt->execute([(int)$vehicleId]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }

            return $stmt->fetch() ?: [
                'total' => 0,
                'en_cours' => 0,
                'terminees' => 0,
                'en_attente_devis' => 0,
            ];
        } catch (Exception $e) {
            error_log('Erreur getClientDashboardStats: ' . $e->getMessage());
            return [
                'total' => 0,
                'en_cours' => 0,
                'terminees' => 0,
                'en_attente_devis' => 0,
            ];
        }
    }

    public function getClientInterventions($vehicleId = 0, $limit = 100, $offset = 0) {
        try {
            $typeIdColumn = $this->typeInterventionIdColumn();
            $diagIdColumn = $this->diagnosticIdColumn();
            $diagVehicleIdColumn = $this->diagnosticVehicleIdColumn();
            $vehicleIdColumn = $this->resolveVehicleColumn(['id']) ?: 'id';
            $vehicleJoinCondition = $diagVehicleIdColumn
                ? ('d.' . $diagVehicleIdColumn . ' = v.' . $vehicleIdColumn)
                : '1 = 0';

            $sql = "SELECT i.*, "
                . $this->selectDiagnosticColumn(['id_vehicule', 'vehicle_id'], 'id_vehicule') . ", "
                . $this->selectDiagnosticColumn(['description_probleme', 'description'], 'description_probleme') . ", "
                . $this->selectVehicleColumn(['marque', 'brand'], 'vehicle_marque') . ", "
                . $this->selectVehicleColumn(['modele', 'model'], 'vehicle_modele') . ", "
                . $this->selectVehicleColumn(['immatriculation', 'matricule'], 'immatriculation') . ", "
                . $this->typeInterventionNameSelect('type_nom') . ", "
                . $this->typeInterventionPrixSelect('type_prix') . "
                    FROM {$this->table} i
                    JOIN diagnostic d ON i.id_diagnostic = d." . $diagIdColumn . "
                    LEFT JOIN vehicle v ON " . $vehicleJoinCondition . "
                    LEFT JOIN type_intervention t ON i.id_type = t." . $typeIdColumn;

            if ($diagVehicleIdColumn && (int)$vehicleId > 0) {
                $sql .= ' WHERE d.' . $diagVehicleIdColumn . ' = ?';
                $stmt = $this->db->prepare($sql . ' ORDER BY i.date_debut DESC LIMIT ? OFFSET ?');
                $stmt->execute([(int)$vehicleId, (int)$limit, (int)$offset]);
            } else {
                $stmt = $this->db->prepare($sql . ' ORDER BY i.date_debut DESC LIMIT ? OFFSET ?');
                $stmt->execute([(int)$limit, (int)$offset]);
            }

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur getClientInterventions: ' . $e->getMessage());
            return [];
        }
    }

    public function getClientInterventionDetail($idIntervention, $vehicleId = 0) {
        $inter = $this->getById((int)$idIntervention);
        if (!$inter) {
            return null;
        }

        if ((int)$vehicleId <= 0) {
            return $inter;
        }

        if ((int)($inter['id_vehicule'] ?? 0) !== (int)$vehicleId) {
            return null;
        }

        return $inter;
    }

    public function listMessages($idIntervention, $vehicleId = 0) {
        $inter = $this->getClientInterventionDetail((int)$idIntervention, (int)$vehicleId);
        if (!$inter) {
            return [];
        }
        return $this->messageModel->listByIntervention((int)$idIntervention);
    }

    public function listMessagesByVehicle($vehicleId) {
        $vehicleId = (int)$vehicleId;
        if ($vehicleId <= 0) {
            return [];
        }

        $diagIdColumn = $this->diagnosticIdColumn();
        $diagVehicleIdColumn = $this->diagnosticVehicleIdColumn();
        if (!$diagVehicleIdColumn) {
            return [];
        }

        $vehicleIdColumn = $this->resolveVehicleColumn(['id']) ?: 'id';
        $vehicleJoinCondition = 'd.' . $diagVehicleIdColumn . ' = v.' . $vehicleIdColumn;

        $sql = "SELECT m.id_message, m.id_intervention, m.expediteur, m.contenu, m.date_envoi, "
            . $this->selectVehicleColumn(['immatriculation', 'matricule'], 'immatriculation') . "
                FROM message m
                JOIN intervention i ON m.id_intervention = i.id_intervention
                JOIN diagnostic d ON i.id_diagnostic = d." . $diagIdColumn . "
                LEFT JOIN vehicle v ON " . $vehicleJoinCondition . "
                WHERE d." . $diagVehicleIdColumn . " = ?
                ORDER BY m.date_envoi ASC, m.id_message ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sendMessage($idIntervention, $sender, $content, $vehicleId = 0) {
        $inter = $this->getClientInterventionDetail((int)$idIntervention, (int)$vehicleId);
        if (!$inter) {
            return ['success' => false, 'message' => 'Intervention introuvable'];
        }

        $ok = $this->messageModel->create((int)$idIntervention, (string)$sender, (string)$content);
        if (!$ok) {
            return ['success' => false, 'message' => 'Message invalide'];
        }

        if ((string)$sender === 'client') {
            $this->setQuoteStatus((int)$idIntervention, 'en_negociation');
        }

        return ['success' => true, 'message' => 'Message envoye'];
    }

    public function decideQuote($idIntervention, $decision, $vehicleId = 0) {
        $inter = $this->getClientInterventionDetail((int)$idIntervention, (int)$vehicleId);
        if (!$inter) {
            return ['success' => false, 'message' => 'Intervention introuvable'];
        }

        $decision = trim((string)$decision);
        if (!in_array($decision, ['accepte', 'refuse'], true)) {
            return ['success' => false, 'message' => 'Decision invalide'];
        }

        $sql = "UPDATE {$this->table} SET statut_devis = ?, date_reponse_devis = NOW() WHERE id_intervention = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$decision, (int)$idIntervention]);

        if ($decision === 'accepte') {
            $this->sendMessage((int)$idIntervention, 'client', 'Je confirme mon accord pour le devis.', (int)$vehicleId);
        } else {
            $this->sendMessage((int)$idIntervention, 'client', 'Je refuse ce devis. Merci de proposer une alternative.', (int)$vehicleId);
        }

        return ['success' => true, 'message' => 'Reponse devis enregistree'];
    }

    public function setQuoteStatus($idIntervention, $status) {
        $allowed = ['en_attente', 'accepte', 'refuse', 'en_negociation'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET statut_devis = ? WHERE id_intervention = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, (int)$idIntervention]);
    }

    public function updateQuoteCost($idIntervention, $newCost) {
        $newCost = (float)$newCost;
        if ($newCost < 0) {
            return ['success' => false, 'message' => 'Montant invalide'];
        }

        // Optional third parameter: typePrices array
        $typePrices = null;
        if (func_num_args() >= 3) {
            $typePrices = func_get_arg(2);
            if (is_array($typePrices)) {
                // Normalize values to floats and compute total
                $normalized = [];
                $sum = 0.0;
                foreach ($typePrices as $k => $v) {
                    $val = $v === null ? 0.0 : (float)$v;
                    $normalized[$k] = $val;
                    $sum += $val;
                }
                $typePricesJson = json_encode($normalized);
            } else {
                $typePricesJson = null;
                $sum = 0.0;
            }
        } else {
            $typePricesJson = null;
            $sum = 0.0;
        }

        $sql = "UPDATE {$this->table}
                SET cout_initial = ?, statut_devis = 'en_attente', date_envoi_devis = NOW(), type_prices = ?, type_total = ?
                WHERE id_intervention = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newCost, $typePricesJson, $sum, (int)$idIntervention]);

        return ['success' => true, 'message' => 'Devis mis a jour'];
    }

    // ============================================
    // 3. UPDATE - Mettre à jour une intervention
    // ============================================
    /**
     * Met à jour le statut d'une intervention
     * valeurs: planifiée, en_cours, terminée
     */
    public function updateStatut($id_intervention, $statut) {
        try {
            $statuts_valides = ['planifiée', 'en_cours', 'terminée'];
            if (!in_array($statut, $statuts_valides)) {
                return [
                    'success' => false,
                    'message' => 'Statut invalide'
                ];
            }

            $sql = "UPDATE {$this->table} SET statut = ? WHERE id_intervention = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$statut, $id_intervention]);

            return [
                'success' => true,
                'message' => 'Statut mis à jour'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Met à jour la date de début d'une intervention (quand elle commence)
     */
    public function updateDateDebut($id_intervention, $date_debut) {
        try {
            $sql = "UPDATE {$this->table} SET date_debut = ? WHERE id_intervention = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date_debut, $id_intervention]);

            return [
                'success' => true,
                'message' => 'Date de début mise à jour'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Met à jour la date de fin d'une intervention
     */
    public function updateDateFin($id_intervention, $date_fin) {
        try {
            $sql = "UPDATE {$this->table} SET date_fin = ? WHERE id_intervention = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$date_fin, $id_intervention]);

            return [
                'success' => true,
                'message' => 'Date de fin mise à jour'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Met à jour la date de fin et le coût final (intervention terminée)
     */
    public function terminate($id_intervention, $cout_final, $date_fin = null, $statut = 'terminée') {
        try {
            // Validation
            if ($cout_final === '' || $cout_final === null) {
                return [
                    'success' => false,
                    'message' => 'Le coût final est requis'
                ];
            }

            if ($cout_final < 0) {
                return [
                    'success' => false,
                    'message' => 'Le coût final ne peut pas être négatif'
                ];
            }

            // Vérifier que le coût final >= coût initial (si applicable)
            $inter = $this->getById($id_intervention);
            if ($inter && $inter['cout_initial'] && $cout_final < $inter['cout_initial']) {
                return [
                    'success' => false,
                    'message' => 'Le coût final ne peut pas être inférieur au coût initial'
                ];
            }

            $statuts_valides = ['planifiée', 'en_cours', 'terminée'];
            if (!in_array($statut, $statuts_valides, true)) {
                $statut = 'terminée';
            }

            $date_fin = $date_fin ?: date('Y-m-d');

            $sql = "UPDATE {$this->table} 
                    SET cout_final = ?, date_fin = ?, statut = ? 
                    WHERE id_intervention = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(float)$cout_final, $date_fin, $statut, $id_intervention]);

            return [
                'success' => true,
                'message' => 'Intervention terminée'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Met à jour complètement une intervention
     */
    public function update($id_intervention, $data) {
        try {
            $allowed_fields = ['description_travail', 'cout_initial', 'id_type'];
            $fields_to_update = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields) && $value !== null && $value !== '') {
                    if ($field === 'cout_initial' && $value < 0) {
                        return [
                            'success' => false,
                            'message' => 'Le coût initial ne peut pas être négatif'
                        ];
                    }
                    $fields_to_update[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($fields_to_update)) {
                return [
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour'
                ];
            }

            $values[] = $id_intervention;
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields_to_update) . " WHERE id_intervention = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            return [
                'success' => true,
                'message' => 'Intervention mise à jour'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    // ============================================
    // 4. DELETE - Supprimer une intervention
    // ============================================
    /**
     * Supprime une intervention (et réinitialise le diagnostic à "accepte")
     */
    public function delete($id_intervention) {
        try {
            // Récupérer l'ID du diagnostic
            $inter = $this->getById($id_intervention);
            if (!$inter) {
                return [
                    'success' => false,
                    'message' => 'Intervention non trouvée'
                ];
            }

            $id_diagnostic = $inter['id_diagnostic'];

            // Supprimer l'intervention
            $sql = "DELETE FROM {$this->table} WHERE id_intervention = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_intervention]);

            // Réinitialiser le diagnostic à "accepte"
            $statusColumn = $this->diagnosticStatusColumn();
            $diagIdColumn = $this->diagnosticIdColumn();
            if ($statusColumn) {
                $sql_diag = 'UPDATE diagnostic SET ' . $statusColumn . " = 'accepte' WHERE " . $diagIdColumn . ' = ?';
                $stmt_diag = $this->db->prepare($sql_diag);
                $stmt_diag->execute([$id_diagnostic]);
            }

            return [
                'success' => true,
                'message' => 'Intervention supprimée'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    // ============================================
    // 5. Helper Methods
    // ============================================
    /**
     * Récupère le statut d'un diagnostic
     */
    private function getDiagnosticStatus($id_diagnostic) {
        try {
            $idColumn = $this->diagnosticIdColumn();
            $statusColumn = $this->diagnosticStatusColumn();
            $statusSelect = $statusColumn ? ($statusColumn . ' AS status') : 'NULL AS status';
            $sql = 'SELECT ' . $idColumn . ' AS id_diagnostic, ' . $statusSelect . ' FROM diagnostic WHERE ' . $idColumn . ' = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_diagnostic]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log('Erreur getDiagnosticStatus: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie qu'un type d'intervention existe
     */
    private function typeInterventionExists($id_type) {
        try {
            $idColumn = $this->typeInterventionIdColumn();
            $sql = "SELECT " . $idColumn . " FROM type_intervention WHERE " . $idColumn . " = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_type]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Erreur typeInterventionExists: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les types d'intervention pour les dropdowns
     */
    public function getTypesIntervention() {
        try {
            $idColumn = $this->typeInterventionIdColumn();
            $nameColumn = $this->resolveTypeInterventionColumn(['nom', 'nom_type', 'label']);
            $descriptionColumn = $this->resolveTypeInterventionColumn(['description', 'details']);
            $prixColumn = $this->resolveTypeInterventionColumn(['prix']);

            if (!$nameColumn) {
                return [];
            }

            $sql = "SELECT " . $idColumn . " AS id_type, "
                . $nameColumn . " AS nom, "
                . ($descriptionColumn ? ($descriptionColumn . " AS description") : "NULL AS description")
                . ", " . ($prixColumn ? ($prixColumn . " AS prix") : "0 AS prix")
                . " FROM type_intervention ORDER BY " . $nameColumn;
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur getTypesIntervention: ' . $e->getMessage());
            return [];
        }
    }

    public function updateTypePrices(array $pricesById) {
        try {
            if (empty($pricesById)) {
                return ['success' => false, 'message' => 'Aucun prix à enregistrer'];
            }

            $idColumn = $this->typeInterventionIdColumn();
            $priceColumn = $this->resolveTypeInterventionColumn(['prix']);
            if (!$priceColumn) {
                return ['success' => false, 'message' => 'Colonne prix introuvable'];
            }

            $this->db->beginTransaction();
            $sql = 'UPDATE type_intervention SET ' . $priceColumn . ' = ? WHERE ' . $idColumn . ' = ?';
            $stmt = $this->db->prepare($sql);

            foreach ($pricesById as $idType => $price) {
                $idType = (int)$idType;
                $price = max(0, (float)$price);
                if ($idType <= 0) {
                    continue;
                }
                $stmt->execute([$price, $idType]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Prix des types mis à jour'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    /**
     * Récupère les statistiques des interventions
     */
    public function getStatistiques() {
        try {
            $statusExpr = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(statut), 'é', 'e'), 'è', 'e'), 'ê', 'e'), 'à', 'a'))";

            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN " . $statusExpr . " IN ('planifiee', 'planifie') THEN 1 ELSE 0 END) as planifiees,
                        SUM(CASE WHEN " . $statusExpr . " IN ('en_cours', 'en cours') THEN 1 ELSE 0 END) as en_cours,
                        SUM(CASE WHEN " . $statusExpr . " IN ('terminee', 'termine') THEN 1 ELSE 0 END) as terminees,
                        ROUND(SUM(CASE WHEN " . $statusExpr . " IN ('terminee', 'termine') THEN COALESCE(cout_final, cout_initial, 0) ELSE 0 END), 2) as benefice_total
                    FROM {$this->table}";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch() ?: [
                'total' => 0,
                'planifiees' => 0,
                'en_cours' => 0,
                'terminees' => 0,
                'benefice_total' => 0,
            ];
        } catch (Exception $e) {
            error_log('Erreur getStatistiques: ' . $e->getMessage());
            return [
                'total' => 0,
                'planifiees' => 0,
                'en_cours' => 0,
                'terminees' => 0,
                'benefice_total' => 0,
            ];
        }
    }

    /**
     * Exporte une intervention terminee en PDF.
     */
    public function exportInterventionPdf($id_intervention) {
        $idIntervention = (int)$id_intervention;
        if ($idIntervention <= 0) {
            header('Location: index.php?action=admin_interventions&error=1');
            exit();
        }

        $inter = $this->getById($idIntervention);
        if (!$inter) {
            header('Location: index.php?action=admin_interventions&error=1');
            exit();
        }

        if (($inter['statut'] ?? '') !== 'terminée') {
            header('Location: index.php?action=admin_interventions&error=1');
            exit();
        }

        $this->streamInterventionPdf($inter);
    }

    public function exportQuotePdf($id_intervention) {
        $idIntervention = (int)$id_intervention;
        if ($idIntervention <= 0) {
            header('Location: index.php?action=client_interventions&error=1');
            exit();
        }

        $inter = $this->getById($idIntervention);
        if (!$inter) {
            header('Location: index.php?action=client_interventions&error=1');
            exit();
        }

        $this->streamQuotePdf($inter);
    }

    private function getMailConfig() {
        $defaults = [
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'username' => '',
            'password' => '',
            'from_email' => 'noreply@smart-garage.local',
            'from_name' => 'Smart Garage',
            'timeout' => 15,
        ];

        $configFile = __DIR__ . '/../config/mail.php';
        if (!file_exists($configFile)) {
            return $defaults;
        }

        $loaded = include $configFile;
        if (!is_array($loaded)) {
            return $defaults;
        }

        return array_merge($defaults, $loaded);
    }

    private function smtpReadResponse($socket) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4) {
                break;
            }
            if ($line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    private function smtpWrite($socket, $command) {
        fwrite($socket, $command . "\r\n");
    }

    private function smtpExpect($response, array $codes) {
        foreach ($codes as $code) {
            if (strpos($response, (string)$code) === 0) {
                return true;
            }
        }
        return false;
    }

    private function encodeHeaderValue($value) {
        $text = (string)$value;
        if ($text === '') {
            return '';
        }

        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    private function sendWithSmtp(array $mailData) {
        $config = $this->getMailConfig();
        $host = trim((string)$config['host']);
        $port = (int)$config['port'];
        $encryption = strtolower(trim((string)$config['encryption']));
        $username = trim((string)$config['username']);
        $password = (string)$config['password'];
        $timeout = max(5, (int)$config['timeout']);

        if ($host === '' || $port <= 0) {
            return [
                'success' => false,
                'message' => 'SMTP non configure. Remplissez config/mail.php.',
            ];
        }

        $transportHost = ($encryption === 'ssl') ? ('ssl://' . $host) : $host;
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($transportHost, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            return [
                'success' => false,
                'message' => 'Connexion SMTP impossible (' . $host . ':' . $port . ').',
            ];
        }

        stream_set_timeout($socket, $timeout);
        $response = $this->smtpReadResponse($socket);
        if (!$this->smtpExpect($response, [220])) {
            fclose($socket);
            return ['success' => false, 'message' => 'Serveur SMTP indisponible.'];
        }

        $this->smtpWrite($socket, 'EHLO localhost');
        $response = $this->smtpReadResponse($socket);
        if (!$this->smtpExpect($response, [250])) {
            $this->smtpWrite($socket, 'HELO localhost');
            $response = $this->smtpReadResponse($socket);
            if (!$this->smtpExpect($response, [250])) {
                fclose($socket);
                return ['success' => false, 'message' => 'Handshake SMTP echoue.'];
            }
        }

        if ($encryption === 'tls') {
            $this->smtpWrite($socket, 'STARTTLS');
            $response = $this->smtpReadResponse($socket);
            if (!$this->smtpExpect($response, [220])) {
                fclose($socket);
                return ['success' => false, 'message' => 'STARTTLS non accepte par le serveur SMTP.'];
            }

            $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoOk !== true) {
                fclose($socket);
                return ['success' => false, 'message' => 'Activation TLS impossible.'];
            }

            $this->smtpWrite($socket, 'EHLO localhost');
            $response = $this->smtpReadResponse($socket);
            if (!$this->smtpExpect($response, [250])) {
                fclose($socket);
                return ['success' => false, 'message' => 'EHLO apres TLS echoue.'];
            }
        }

        if ($username !== '') {
            $this->smtpWrite($socket, 'AUTH LOGIN');
            $response = $this->smtpReadResponse($socket);
            if (!$this->smtpExpect($response, [334])) {
                fclose($socket);
                return ['success' => false, 'message' => 'AUTH LOGIN refusee par le serveur SMTP.'];
            }

            $this->smtpWrite($socket, base64_encode($username));
            $response = $this->smtpReadResponse($socket);
            if (!$this->smtpExpect($response, [334])) {
                fclose($socket);
                return ['success' => false, 'message' => 'Nom utilisateur SMTP invalide.'];
            }

            $this->smtpWrite($socket, base64_encode($password));
            $response = $this->smtpReadResponse($socket);
            if (!$this->smtpExpect($response, [235])) {
                fclose($socket);
                return ['success' => false, 'message' => 'Mot de passe SMTP invalide.'];
            }
        }

        $fromEmail = trim((string)$config['from_email']);
        $fromName = trim((string)$config['from_name']);
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            fclose($socket);
            return ['success' => false, 'message' => 'Adresse expediteur SMTP invalide.'];
        }

        $toEmail = (string)$mailData['to'];
        $subject = (string)$mailData['subject'];
        $body = (string)$mailData['body'];
        $attachment = isset($mailData['attachment']) && is_array($mailData['attachment'])
            ? $mailData['attachment']
            : null;

        $this->smtpWrite($socket, 'MAIL FROM:<' . $fromEmail . '>');
        $response = $this->smtpReadResponse($socket);
        if (!$this->smtpExpect($response, [250])) {
            fclose($socket);
            return ['success' => false, 'message' => 'MAIL FROM refuse par le serveur SMTP.'];
        }

        $this->smtpWrite($socket, 'RCPT TO:<' . $toEmail . '>');
        $response = $this->smtpReadResponse($socket);
        if (!$this->smtpExpect($response, [250, 251])) {
            fclose($socket);
            return ['success' => false, 'message' => 'Adresse destinataire rejetee par le serveur SMTP.'];
        }

        $this->smtpWrite($socket, 'DATA');
        $response = $this->smtpReadResponse($socket);
        if (!$this->smtpExpect($response, [354])) {
            fclose($socket);
            return ['success' => false, 'message' => 'Commande DATA refusee par le serveur SMTP.'];
        }

        $messageHeaders = [];
        $messageHeaders[] = 'Date: ' . date(DATE_RFC2822);
        $messageHeaders[] = 'From: ' . $this->encodeHeaderValue($fromName) . ' <' . $fromEmail . '>';
        $messageHeaders[] = 'To: <' . $toEmail . '>';
        $messageHeaders[] = 'Subject: ' . $this->encodeHeaderValue($subject);
        $messageHeaders[] = 'MIME-Version: 1.0';
        $mimeBody = '';

        if ($attachment && !empty($attachment['content'])) {
            $boundary = '=_SmartGarage_' . md5(uniqid((string)microtime(true), true));
            $filename = (string)($attachment['filename'] ?? 'devis.pdf');
            $mimeType = (string)($attachment['mime'] ?? 'application/pdf');
            $attachmentB64 = chunk_split(base64_encode((string)$attachment['content']));

            $messageHeaders[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

            $mimeBody .= '--' . $boundary . "\n";
            $mimeBody .= "Content-Type: text/plain; charset=UTF-8\n";
            $mimeBody .= "Content-Transfer-Encoding: 8bit\n\n";
            $mimeBody .= $body . "\n\n";
            $mimeBody .= '--' . $boundary . "\n";
            $mimeBody .= 'Content-Type: ' . $mimeType . '; name="' . $filename . "\"\n";
            $mimeBody .= "Content-Transfer-Encoding: base64\n";
            $mimeBody .= 'Content-Disposition: attachment; filename="' . $filename . "\"\n\n";
            $mimeBody .= $attachmentB64 . "\n";
            $mimeBody .= '--' . $boundary . '--';
        } else {
            $messageHeaders[] = 'Content-Type: text/plain; charset=UTF-8';
            $messageHeaders[] = 'Content-Transfer-Encoding: 8bit';
            $mimeBody = $body;
        }

        $normalizedBody = str_replace(["\r\n", "\r"], "\n", $mimeBody);
        $normalizedBody = str_replace("\n.", "\n..", $normalizedBody);
        $dataPayload = implode("\r\n", $messageHeaders) . "\r\n\r\n" . str_replace("\n", "\r\n", $normalizedBody) . "\r\n.";

        fwrite($socket, $dataPayload . "\r\n");
        $response = $this->smtpReadResponse($socket);
        if (!$this->smtpExpect($response, [250])) {
            fclose($socket);
            return ['success' => false, 'message' => 'Le serveur SMTP a refuse le contenu du message.'];
        }

        $this->smtpWrite($socket, 'QUIT');
        fclose($socket);

        return ['success' => true, 'message' => 'Devis envoye via SMTP'];
    }

    public function sendQuoteEmail($idIntervention, $email) {
        $inter = $this->getById((int)$idIntervention);
        if (!$inter) {
            return ['success' => false, 'message' => 'Intervention introuvable'];
        }

        $email = trim((string)$email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email invalide'];
        }

        $config = $this->getMailConfig();
        $fromEmail = trim((string)$config['from_email']);
        $fromName = trim((string)$config['from_name']);
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = 'noreply@smart-garage.local';
        }
        if ($fromName === '') {
            $fromName = 'Smart Garage';
        }

        $subject = 'Devis intervention #' . (int)$idIntervention . ' - Smart Garage';
        $typeIntervention = trim((string)($inter['type_nom'] ?? 'Non specifie'));
        $descriptionTravaux = trim((string)($inter['description_travail'] ?? 'Non specifiee'));
        $descriptionProbleme = trim((string)($inter['description_probleme'] ?? ''));
        $body = "Bonjour,\n\n"
            . "Votre devis est disponible en piece jointe (PDF).\n"
            . "Intervention #" . (int)$idIntervention . "\n"
            . "Type: " . $typeIntervention . "\n"
            . "Description travaux: " . $descriptionTravaux . "\n"
            . ($descriptionProbleme !== '' ? ("Probleme signale: " . $descriptionProbleme . "\n") : '')
            . "Montant estime: " . number_format((float)($inter['cout_initial'] ?? 0), 2, ',', ' ') . " DT\n\n"
            . "Cordialement,\nSmart Garage";

        $quotePdf = $this->buildQuotePdfDocument($inter);
        $attachment = [
            'filename' => (string)$quotePdf['filename'],
            'content' => (string)$quotePdf['content'],
            'mime' => 'application/pdf',
        ];

        $boundary = '=_SmartGarage_' . md5(uniqid((string)$idIntervention, true));
        $mimeMessage = '';
        $mimeMessage .= '--' . $boundary . "\r\n";
        $mimeMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mimeMessage .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $mimeMessage .= $body . "\r\n\r\n";
        $mimeMessage .= '--' . $boundary . "\r\n";
        $mimeMessage .= 'Content-Type: application/pdf; name="' . $attachment['filename'] . "\"\r\n";
        $mimeMessage .= "Content-Transfer-Encoding: base64\r\n";
        $mimeMessage .= 'Content-Disposition: attachment; filename="' . $attachment['filename'] . "\"\r\n\r\n";
        $mimeMessage .= chunk_split(base64_encode($attachment['content'])) . "\r\n";
        $mimeMessage .= '--' . $boundary . '--';

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'Reply-To: ' . $fromEmail;
        $headersString = implode("\r\n", $headers);

        $sent = @mail($email, $subject, $mimeMessage, $headersString);
        if (!$sent) {
            $smtpResult = $this->sendWithSmtp([
                'to' => $email,
                'subject' => $subject,
                'body' => $body,
                'attachment' => $attachment,
            ]);

            if (empty($smtpResult['success'])) {
                error_log(
                    'Mail devis echec: intervention=' . (int)$idIntervention
                    . ', to=' . $email
                    . ', SMTP=' . (string)ini_get('SMTP')
                    . ', smtp_port=' . (string)ini_get('smtp_port')
                    . ', sendmail_path=' . (string)ini_get('sendmail_path')
                    . ', smtp_fallback=' . (string)($smtpResult['message'] ?? 'unknown')
                );

                return [
                    'success' => false,
                    'message' => (string)($smtpResult['message'] ?? 'Echec envoi email.'),
                ];
            }
        }

        $sql = "UPDATE {$this->table} SET date_envoi_devis = NOW() WHERE id_intervention = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$idIntervention]);

        return ['success' => true, 'message' => 'Devis envoye'];
    }

    // Backward compatibility with previous method name.
    public function exportInterventionFile($id_intervention) {
        $this->exportInterventionPdf($id_intervention);
    }

    private function pdfSanitizeText($text) {
        $value = (string)$text;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', $value);
        return trim((string)$value);
    }

    private function pdfEscapeText($text) {
        $safe = $this->pdfSanitizeText($text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safe);
    }

    private function pdfWrapText($text, $maxChars) {
        $clean = $this->pdfSanitizeText($text);
        if ($clean === '') {
            return [''];
        }

        $wrapped = wordwrap($clean, $maxChars, "\n", true);
        return explode("\n", $wrapped);
    }

    private function streamInterventionPdf(array $inter) {
        $idIntervention = (int)($inter['id_intervention'] ?? 0);
        $title = 'FICHIER INTERVENTION N' . chr(176) . sprintf('%02d', $idIntervention);
        $dateLabel = 'Date: ' . date('d/m/Y');
        $basePrice = ($inter['cout_final'] !== null)
            ? (float)$inter['cout_final']
            : (float)($inter['cout_initial'] ?? 0);
        $tvaAmount = $basePrice * 0.19;
        $timbreAmount = 1.000;
        $grandTotal = $basePrice + $tvaAmount + $timbreAmount;

        $rows = [];
        $rows[] = ['designation' => 'Matricule: ' . (string)($inter['immatriculation'] ?? 'N/A') . ' - Vehicule: ' . (string)($inter['vehicle_marque'] ?? 'N/A') . ' ' . (string)($inter['vehicle_modele'] ?? ''), 'prix' => ''];
        $rows[] = ['designation' => 'Description travaux: ' . (string)($inter['description_travail'] ?? 'N/A'), 'prix' => ''];

        // Types: single or multiple
        // Load stored standard type prices if available
        $stdPrices = ['batterie' => 0.0, 'climatisation' => 0.0, 'diagnostic_electronique' => 0.0];
        if (!empty($inter['type_prices'])) {
            $decoded = json_decode($inter['type_prices'], true);
            if (is_array($decoded)) {
                foreach ($stdPrices as $k => $_) {
                    if (isset($decoded[$k])) $stdPrices[$k] = (float)$decoded[$k];
                }
            }
        }

        if (!empty($inter['type_items']) && is_array($inter['type_items'])) {
            $rows[] = ['designation' => 'Types d\'intervention sélectionnés :', 'prix' => ''];
            foreach ($inter['type_items'] as $ti) {
                $p = isset($ti['prix']) && $ti['prix'] !== null ? number_format((float)$ti['prix'], 2, '.', ' ') . ' DT' : '';
                $rows[] = ['designation' => ' - ' . (string)($ti['nom'] ?? 'N/A'), 'prix' => $p];
            }
        } else {
            $rows[] = ['designation' => 'Type intervention: ' . (string)($inter['type_nom'] ?? 'N/A'), 'prix' => (isset($inter['type_prix']) && $inter['type_prix'] > 0 ? number_format((float)$inter['type_prix'], 2, '.', ' ') . ' DT' : '')];
        }

        $rows[] = ['designation' => 'Date debut: ' . (!empty($inter['date_debut']) ? (string)$inter['date_debut'] : 'N/A'), 'prix' => ''];
        $rows[] = ['designation' => 'Date fin: ' . (!empty($inter['date_fin']) ? (string)$inter['date_fin'] : 'N/A'), 'prix' => ''];
        $rows[] = ['designation' => 'Prix HT', 'prix' => number_format($basePrice, 2, '.', ' ') . ' DT'];
        $rows[] = ['designation' => 'TVA 19%', 'prix' => number_format($tvaAmount, 2, '.', ' ') . ' DT'];
        $rows[] = ['designation' => 'Timbre', 'prix' => number_format($timbreAmount, 2, '.', ' ') . ' DT'];
        $rows[] = ['designation' => 'Prix total', 'prix' => number_format($grandTotal, 2, '.', ' ') . ' DT'];

        $pageWidth = 595;
        $pageHeight = 842;
        $content = "0.5 w\n";

        $titleFont = 18;
        $titleWidth = strlen($this->pdfSanitizeText($title)) * ($titleFont * 0.52);
        $titleX = max(40, ($pageWidth - $titleWidth) / 2);
        // Header with extra spacing.
        $content .= sprintf("BT /F1 11 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", 455, 810, $this->pdfEscapeText($dateLabel));
        $content .= sprintf("BT /F1 %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $titleFont, $titleX, 760, $this->pdfEscapeText($title));

        $tableX = 45;
        $tableY = 700;
        $colDesignW = 385;
        $colPrixW = 120;
        $rowPad = 6;
        $lineH = 13;

        $headerH = 18;
        $content .= sprintf("BT /F1 12 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $tableX + 8, $tableY + 7, $this->pdfEscapeText('Designation'));
        $content .= sprintf("BT /F1 12 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $tableX + $colDesignW + 8, $tableY + 7, $this->pdfEscapeText('Prix'));

        // Start a bit lower than the header to avoid visual overlap with the first description line.
        $currentY = $tableY - 10;
        foreach ($rows as $index => $row) {
            $designLines = $this->pdfWrapText((string)$row['designation'], 62);
            $prixLines = $this->pdfWrapText((string)$row['prix'], 20);
            $lineCount = max(count($designLines), count($prixLines));
            $rowH = max(22, $rowPad * 2 + ($lineCount * $lineH));

            if ($currentY - $rowH < 50) {
                break;
            }

            $rowTopY = $currentY;
            $textY = $rowTopY - $rowPad - 11;
            foreach ($designLines as $line) {
                $content .= sprintf("BT /F1 10 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $tableX + 6, $textY, $this->pdfEscapeText($line));
                $textY -= $lineH;
            }

            $priceY = $rowTopY - $rowPad - 11;
            foreach ($prixLines as $line) {
                $priceText = $this->pdfEscapeText($line);
                $priceTextWidth = strlen($this->pdfSanitizeText($line)) * 5.2;
                $priceX = max(
                    $tableX + $colDesignW + 6,
                    $tableX + $colDesignW + $colPrixW - 8 - $priceTextWidth
                );
                $content .= sprintf("BT /F1 10 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $priceX, $priceY, $priceText);
                $priceY -= $lineH;
            }

            // Horizontal separators in the pricing block (as requested in the design).
            if (in_array($index, [5, 6, 7, 8], true)) {
                $content .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $tableX, $rowTopY, $tableX + $colDesignW + $colPrixW, $rowTopY);
            }

            $currentY -= $rowH;
        }

        // Frame the table area: outer border, column separator, and header divider.
        $tableTop = $tableY + $headerH;
        $tableBottom = $currentY;
        $tableWidth = $colDesignW + $colPrixW;
        $tableHeight = $tableTop - $tableBottom;

        if ($tableHeight > 0) {
            $content .= "0.8 w\n";
            $content .= sprintf("%.2f %.2f %.2f %.2f re S\n", $tableX, $tableBottom, $tableWidth, $tableHeight);
            $content .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $tableX + $colDesignW, $tableBottom, $tableX + $colDesignW, $tableTop);
            $content .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $tableX, $tableY, $tableX + $tableWidth, $tableY);
            $content .= "0.5 w\n";
        }

        // Signature section at the bottom.
        $sigY = 90;
        $content .= sprintf("BT /F1 11 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", 370, $sigY + 30, $this->pdfEscapeText('Signature:'));
        $content .= sprintf("%.2f %.2f m %.2f %.2f l S\n", 370, $sigY + 20, 540, $sigY + 20);

        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
        $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
        $objects[] = '5 0 obj << /Length ' . strlen($content) . " >> stream\n" . $content . "\nendstream endobj";

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
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        $filename = 'fiche-intervention-' . $idIntervention . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit();
    }

    private function streamQuotePdf(array $inter) {
        $pdfDoc = $this->buildQuotePdfDocument($inter);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pdfDoc['filename'] . '"');
        header('Content-Length: ' . strlen($pdfDoc['content']));
        echo $pdfDoc['content'];
        exit();
    }

    private function buildQuotePdfDocument(array $inter) {
        $idIntervention = (int)($inter['id_intervention'] ?? 0);
        $title = 'DEVIS INTERVENTION N' . chr(176) . sprintf('%02d', $idIntervention);
        $dateLabel = 'Date: ' . date('d/m/Y');
        $basePrice = (float)($inter['cout_initial'] ?? 0);
        $tvaAmount = $basePrice * 0.19;
        $grandTotal = $basePrice + $tvaAmount;

        $rows = [];
        $rows[] = ['designation' => 'Matricule: ' . (string)($inter['immatriculation'] ?? 'N/A'), 'prix' => ''];

        // Load stored standard type prices if available
        $stdPrices = ['batterie' => 0.0, 'climatisation' => 0.0, 'diagnostic_electronique' => 0.0];
        if (!empty($inter['type_prices'])) {
            $decoded = json_decode($inter['type_prices'], true);
            if (is_array($decoded)) {
                foreach ($stdPrices as $k => $_) {
                    if (isset($decoded[$k])) $stdPrices[$k] = (float)$decoded[$k];
                }
            }
        }

        if (!empty($inter['type_items']) && is_array($inter['type_items'])) {
            $rows[] = ['designation' => 'Types d\'intervention sélectionnés :', 'prix' => ''];
            $sumSelectedTypes = 0.0;
            foreach ($inter['type_items'] as $ti) {
                $typeName = trim((string)($ti['nom'] ?? 'N/A'));
                $typePrice = isset($ti['prix']) && $ti['prix'] !== null ? (float)$ti['prix'] : 0.0;
                $sumSelectedTypes += $typePrice;
                $p = number_format($typePrice, 2, '.', ' ') . ' DT';
                $rows[] = ['designation' => ' - ' . $typeName, 'prix' => $p];
            }
            $totalTypes = isset($inter['type_total']) && (float)$inter['type_total'] > 0
                ? (float)$inter['type_total']
                : $sumSelectedTypes;
            $rows[] = ['designation' => 'Prix total des types', 'prix' => number_format($totalTypes, 2, '.', ' ') . ' DT'];
        } else {
            $rows[] = ['designation' => 'Type intervention: ' . (string)($inter['type_nom'] ?? 'N/A'), 'prix' => (isset($inter['type_prix']) && $inter['type_prix'] > 0 ? number_format((float)$inter['type_prix'], 2, '.', ' ') . ' DT' : '')];
            $totalTypes = isset($inter['type_total']) && (float)$inter['type_total'] > 0 ? (float)$inter['type_total'] : (float)($inter['type_prix'] ?? 0);
            $rows[] = ['designation' => 'Prix total des types', 'prix' => number_format($totalTypes, 2, '.', ' ') . ' DT'];
        }

        $rows[] = ['designation' => 'Description travaux: ' . (string)($inter['description_travail'] ?? 'N/A'), 'prix' => ''];
        $rows[] = ['designation' => 'Prix HT', 'prix' => number_format($basePrice, 2, '.', ' ') . ' DT'];
        $rows[] = ['designation' => 'TVA 19%', 'prix' => number_format($tvaAmount, 2, '.', ' ') . ' DT'];
        $rows[] = ['designation' => 'Total TTC', 'prix' => number_format($grandTotal, 2, '.', ' ') . ' DT'];

        $pageWidth = 595;
        $pageHeight = 842;
        $content = "0.5 w\n";
        $titleFont = 18;
        $titleWidth = strlen($this->pdfSanitizeText($title)) * ($titleFont * 0.52);
        $titleX = max(40, ($pageWidth - $titleWidth) / 2);
        $content .= sprintf("BT /F1 11 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", 455, 810, $this->pdfEscapeText($dateLabel));
        $content .= sprintf("BT /F1 %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $titleFont, $titleX, 760, $this->pdfEscapeText($title));

        $tableX = 45;
        $tableY = 700;
        $colDesignW = 385;
        $colPrixW = 120;
        $rowPad = 6;
        $lineH = 13;
        $headerH = 18;

        $content .= sprintf("BT /F1 12 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $tableX + 8, $tableY + 7, $this->pdfEscapeText('Designation'));
        $content .= sprintf("BT /F1 12 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $tableX + $colDesignW + 8, $tableY + 7, $this->pdfEscapeText('Prix'));

        $currentY = $tableY - 10;
        foreach ($rows as $row) {
            $designLines = $this->pdfWrapText((string)$row['designation'], 62);
            $prixLines = $this->pdfWrapText((string)$row['prix'], 20);
            $lineCount = max(count($designLines), count($prixLines));
            $rowH = max(22, $rowPad * 2 + ($lineCount * $lineH));

            $rowTopY = $currentY;
            $textY = $rowTopY - $rowPad - 11;
            foreach ($designLines as $line) {
                $content .= sprintf("BT /F1 10 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $tableX + 6, $textY, $this->pdfEscapeText($line));
                $textY -= $lineH;
            }

            $priceY = $rowTopY - $rowPad - 11;
            foreach ($prixLines as $line) {
                $priceText = $this->pdfEscapeText($line);
                $priceTextWidth = strlen($this->pdfSanitizeText($line)) * 5.2;
                $priceX = max(
                    $tableX + $colDesignW + 6,
                    $tableX + $colDesignW + $colPrixW - 8 - $priceTextWidth
                );
                $content .= sprintf("BT /F1 10 Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $priceX, $priceY, $priceText);
                $priceY -= $lineH;
            }

            $currentY -= $rowH;
        }

        $tableTop = $tableY + $headerH;
        $tableBottom = $currentY;
        $tableWidth = $colDesignW + $colPrixW;
        $tableHeight = $tableTop - $tableBottom;
        if ($tableHeight > 0) {
            $content .= sprintf("%.2f %.2f %.2f %.2f re S\n", $tableX, $tableBottom, $tableWidth, $tableHeight);
            $content .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $tableX + $colDesignW, $tableBottom, $tableX + $colDesignW, $tableTop);
            $content .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $tableX, $tableY, $tableX + $tableWidth, $tableY);
        }

        $objects = [];
        $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
        $objects[] = '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj';
        $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
        $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
        $objects[] = '5 0 obj << /Length ' . strlen($content) . " >> stream\n" . $content . "\nendstream endobj";

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
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return [
            'filename' => 'devis-intervention-' . $idIntervention . '.pdf',
            'content' => $pdf,
        ];
    }

    // ============================================
    // 6. Request Handler (pour routing général)
    // ============================================
    /**
     * Gère les requêtes HTTP selon l'action
     */
    public function handleRequest() {
        // action_type comes from admin forms and must take precedence over query action.
        $action = $_REQUEST['action_type'] ?? ($_REQUEST['action'] ?? null);

        switch ($action) {
            case 'create_intervention':
                return $this->handleCreateIntervention();

            case 'update_statut':
                return $this->handleUpdateStatut();

            case 'terminate':
                return $this->handleTerminate();

            case 'update_quote':
                return $this->handleUpdateQuote();

            case 'update_type_prices':
                return $this->handleUpdateTypePrices();

            case 'update_intervention_info':
                return $this->handleUpdateInterventionInfo();

            case 'upload_intervention_media':
                return $this->handleUploadInterventionMedia();

            case 'send_quote_email':
                return $this->handleSendQuoteEmail();

            case 'send_intervention_info':
                return $this->handleSendInterventionInfo();

            case 'send_message':
                return $this->handleSendMessage();

            case 'accept_quote':
                return $this->handleClientQuoteDecision('accepte');

            case 'refuse_quote':
                return $this->handleClientQuoteDecision('refuse');

            default:
                return [
                    'success' => false,
                    'message' => 'Action non reconnue'
                ];
        }
    }

    private function handleUpdateInterventionInfo() {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        if ($idIntervention <= 0) {
            return ['success' => false, 'message' => 'Parametres manquants'];
        }

        $description = trim((string)($_POST['description_travail'] ?? ''));
        $idType = $_POST['id_type'] ?? null;
        $coutInitial = $_POST['cout_initial'] ?? null;
        $statut = trim((string)($_POST['statut'] ?? ''));
        $dateDebut = trim((string)($_POST['date_debut'] ?? ''));
        $dateFin = trim((string)($_POST['date_fin'] ?? ''));

        if ($dateDebut !== '' && $dateFin !== '' && $dateFin < $dateDebut) {
            return ['success' => false, 'message' => 'La date de fin doit etre superieure ou egale a la date de debut'];
        }

        $updateData = [];
        if ($description !== '') {
            $updateData['description_travail'] = $description;
        }
        if ($idType !== null && $idType !== '') {
            $updateData['id_type'] = $idType;
        }
        if ($coutInitial !== null && $coutInitial !== '') {
            $updateData['cout_initial'] = (float)$coutInitial;
        }

        if (!empty($updateData)) {
            $result = $this->update($idIntervention, $updateData);
            if (empty($result['success'])) {
                return $result;
            }
        }

        if ($statut !== '') {
            $statusResult = $this->updateStatut($idIntervention, $statut);
            if (empty($statusResult['success'])) {
                return $statusResult;
            }
        }

        if ($dateDebut !== '') {
            $this->updateDateDebut($idIntervention, $dateDebut);
        }
        if ($dateFin !== '') {
            $this->updateDateFin($idIntervention, $dateFin);
        }

        return ['success' => true, 'message' => 'Intervention mise a jour'];
    }

    private function handleUploadInterventionMedia() {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        if ($idIntervention <= 0) {
            return ['success' => false, 'message' => 'Parametres manquants'];
        }

        if (empty($_FILES['media_file']) || !isset($_FILES['media_file']['tmp_name'])) {
            return ['success' => false, 'message' => 'Fichier manquant'];
        }

        $file = $_FILES['media_file'];
        if (!empty($file['error'])) {
            return ['success' => false, 'message' => 'Erreur fichier'];
        }

        $maxSize = 10 * 1024 * 1024;
        if (!empty($file['size']) && $file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Fichier trop volumineux'];
        }

        $originalName = (string)($file['name'] ?? 'media');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            return ['success' => false, 'message' => 'Type de fichier non autorise'];
        }

        $targetDir = __DIR__ . '/../uploads/interventions';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        if ($safeBase === '') {
            $safeBase = 'media';
        }
        $filename = $safeBase . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Impossible d\'enregistrer le fichier'];
        }

        $relativePath = 'uploads/interventions/' . $filename;
        $message = 'Media: ' . $relativePath;
        $ok = $this->messageModel->create($idIntervention, 'admin', $message);
        if (!$ok) {
            return ['success' => false, 'message' => 'Echec d\'enregistrement du message'];
        }

        return ['success' => true, 'message' => 'Media ajoute'];
    }

    private function handleCreateIntervention() {
        $result = $this->create(
            $_POST['id_diagnostic'] ?? 0,
            $_POST['id_type'] ?? 0,
            $_POST['description_travail'] ?? '',
            $_POST['cout_initial'] ?? 0
        );
        return $result;
    }

    private function handleUpdateStatut() {
        if (empty($_POST['id_intervention']) || empty($_POST['statut'])) {
            return ['success' => false, 'message' => 'Paramètres manquants'];
        }

        $idIntervention = (int)$_POST['id_intervention'];
        $statut = trim((string)$_POST['statut']);
        $dateDebut = trim((string)($_POST['date_debut'] ?? ''));
        $dateFin = trim((string)($_POST['date_fin'] ?? ''));

        if ($dateDebut === '' || $dateFin === '') {
            return ['success' => false, 'message' => 'Date de début et date de fin sont obligatoires'];
        }

        if ($dateFin < $dateDebut) {
            return ['success' => false, 'message' => 'La date de fin doit être supérieure ou égale à la date de début'];
        }

        $statusResult = $this->updateStatut($idIntervention, $statut);
        if (empty($statusResult['success'])) {
            return $statusResult;
        }

        $this->updateDateDebut($idIntervention, $dateDebut);
        $this->updateDateFin($idIntervention, $dateFin);

        return [
            'success' => true,
            'message' => 'Statut et date mis à jour'
        ];
    }

    private function handleTerminate() {
        if (empty($_POST['id_intervention']) || $_POST['cout_final'] === '') {
            return ['success' => false, 'message' => 'Paramètres manquants'];
        }
        return $this->terminate(
            $_POST['id_intervention'],
            $_POST['cout_final'],
            $_POST['date_fin'] ?? null,
            'terminée'
        );
    }

    private function handleSendMessage() {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $sender = trim((string)($_POST['sender'] ?? 'client'));
        $content = trim((string)($_POST['contenu'] ?? ''));

        return $this->sendMessage($idIntervention, $sender, $content, $vehicleId);
    }

    private function handleClientQuoteDecision($decision) {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        return $this->decideQuote($idIntervention, $decision, $vehicleId);
    }

    private function handleUpdateQuote() {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        $newCost = $_POST['cout_initial'] ?? null;
        $note = trim((string)($_POST['note_admin'] ?? ''));

        if ($idIntervention <= 0 || $newCost === null || $newCost === '') {
            return ['success' => false, 'message' => 'Parametres manquants'];
        }

        // Collect standard type prices if provided
        $typePrices = [
            'batterie' => isset($_POST['type_batterie']) ? (float)$_POST['type_batterie'] : null,
            'climatisation' => isset($_POST['type_climatisation']) ? (float)$_POST['type_climatisation'] : null,
            'diagnostic_electronique' => isset($_POST['type_diagnostic_electronique']) ? (float)$_POST['type_diagnostic_electronique'] : null,
        ];

        $result = $this->updateQuoteCost($idIntervention, $newCost, $typePrices);
        if (!empty($result['success']) && $note !== '') {
            $this->sendMessage($idIntervention, 'admin', $note, 0);
        }
        return $result;
    }

    private function handleUpdateTypePrices() {
        $prices = $_POST['type_prices'] ?? [];
        if (!is_array($prices) || empty($prices)) {
            return ['success' => false, 'message' => 'Paramètres manquants'];
        }

        return $this->updateTypePrices($prices);
    }

    private function handleSendQuoteEmail() {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        $email = trim((string)($_POST['client_email'] ?? ''));
        if ($idIntervention <= 0 || $email === '') {
            return ['success' => false, 'message' => 'Parametres manquants'];
        }
        return $this->sendQuoteEmail($idIntervention, $email);
    }

    private function handleSendInterventionInfo() {
        $idIntervention = (int)($_POST['id_intervention'] ?? 0);
        if ($idIntervention <= 0) {
            return ['success' => false, 'message' => 'Parametres manquants'];
        }
        return $this->sendInterventionInfo($idIntervention);
    }

    /**
     * Envoie les informations complètes d'une intervention au client via le système de messages interne.
     */
    public function sendInterventionInfo(int $idIntervention) {
        try {
            $inter = $this->getById($idIntervention);
            if (empty($inter)) {
                return ['success' => false, 'message' => 'Intervention introuvable'];
            }

            $lines = [];
            $lines[] = "Bonjour,";
            $lines[] = "Voici les informations concernant votre intervention #" . (int)$idIntervention . ":";
            $lines[] = "";
            $lines[] = "Matricule: " . ($inter['immatriculation'] ?? 'N/A');
            $lines[] = "Véhicule: " . trim(($inter['vehicle_marque'] ?? '') . ' ' . ($inter['vehicle_modele'] ?? ''));
            $lines[] = "Description travaux: " . ($inter['description_travail'] ?? 'N/A');

            // Types
            if (!empty($inter['type_items']) && is_array($inter['type_items'])) {
                $lines[] = "Types d'intervention sélectionnés:";
                foreach ($inter['type_items'] as $ti) {
                    $p = isset($ti['prix']) && $ti['prix'] !== null ? number_format((float)$ti['prix'], 2, '.', ' ') . ' DT' : '—';
                    $lines[] = " - " . ($ti['nom'] ?? 'N/A') . " : " . $p;
                }
            } else {
                $lines[] = "Type intervention: " . ($inter['type_nom'] ?? 'N/A');
            }

            $lines[] = "";
            if (isset($inter['cout_final']) && $inter['cout_final'] !== null) {
                $lines[] = "Cout final: " . number_format((float)$inter['cout_final'], 2, '.', ' ') . ' DT';
            }
            $lines[] = "";
            $lines[] = "Date debut: " . (!empty($inter['date_debut']) ? (string)$inter['date_debut'] : 'N/A');
            $lines[] = "Date fin: " . (!empty($inter['date_fin']) ? (string)$inter['date_fin'] : 'N/A');
            $lines[] = "";
            $lines[] = "Cordialement,";
            $lines[] = ($GLOBALS['config']['from_name'] ?? 'Smart Garage');

            $content = implode("\n", $lines);

            $ok = $this->messageModel->create($idIntervention, 'admin', $content);
            if (!$ok) {
                return ['success' => false, 'message' => 'Echec d\'enregistrement du message'];
            }

            return ['success' => true, 'message' => 'Message envoye au client'];
        } catch (Exception $e) {
            error_log('Erreur sendInterventionInfo: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur interne'];
        }
    }
}
