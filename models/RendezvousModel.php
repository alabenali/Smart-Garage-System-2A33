<?php

require_once __DIR__ . '/../config/Database.php';

class RendezvousModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function countActiveByCreneau(int $idCreneau): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rendezvous_digital WHERE id_creneau = :id_creneau AND statut IN ('En attente', 'Confirmé', 'En cours')");
        $stmt->execute([':id_creneau' => $idCreneau]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO rendezvous_digital (
                    id_creneau,
                    nom_client,
                    prenom_client,
                    telephone_client,
                    email_client,
                    id_vehicle,
                    type_intervention,
                    description_panne,
                    remise_eco_appliquee,
                    statut,
                    notes,
                    date_creation,
                    date_modification
                ) VALUES (
                    :id_creneau,
                    :nom_client,
                    :prenom_client,
                    :telephone_client,
                    :email_client,
                    :id_vehicle,
                    :type_intervention,
                    :description_panne,
                    :remise_eco_appliquee,
                    :statut,
                    :notes,
                    NOW(),
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_creneau' => (int) $data['id_creneau'],
            ':nom_client' => $data['nom_client'],
            ':prenom_client' => $data['prenom_client'],
            ':telephone_client' => $data['telephone_client'],
            ':email_client' => $data['email_client'] !== '' ? $data['email_client'] : null,
            ':id_vehicle' => isset($data['id_vehicle']) ? (int) $data['id_vehicle'] : null,
            ':type_intervention' => $data['type_intervention'],
            ':description_panne' => $data['description_panne'] !== '' ? $data['description_panne'] : null,
            ':remise_eco_appliquee' => $data['remise_eco_appliquee'],
            ':statut' => $data['statut'] ?? 'En attente',
            ':notes' => $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findDetailedById(int $idRdv): ?array
    {
        $sql = "SELECT 
                    r.*, c.date_heure, c.est_heure_creuse, c.capacite_max,
                    v.immatriculation, v.marque, v.modele, v.annee, v.kilometrage, v.carburant
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE r.id_rdv = :id_rdv
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_rdv' => $idRdv]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getByCreneau(int $idCreneau): array
    {
        $sql = "SELECT 
                    r.id_rdv,
                    r.nom_client,
                    r.prenom_client,
                    r.telephone_client,
                    r.email_client,
                    r.type_intervention,
                    r.description_panne,
                    r.statut,
                    r.remise_eco_appliquee,
                    r.notes,
                    r.date_creation,
                    v.immatriculation,
                    v.marque,
                    v.modele
                FROM rendezvous_digital r
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE r.id_creneau = :id_creneau
                ORDER BY r.date_creation ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_creneau' => $idCreneau]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $idRdv, string $status): bool
    {
        $sql = 'UPDATE rendezvous_digital SET statut = :statut, date_modification = NOW() WHERE id_rdv = :id_rdv';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':statut' => $status,
            ':id_rdv' => $idRdv,
        ]);
    }

    public function getWeekDetailed(string $weekStart, string $weekEnd): array
    {
        $sql = "SELECT 
                  c.id_creneau,
                  c.date_heure,
                  c.est_heure_creuse,
                  c.capacite_max,
                  r.id_rdv,
                  r.nom_client,
                  r.prenom_client,
                  r.telephone_client,
                  r.email_client,
                  r.type_intervention,
                  r.description_panne,
                  r.statut,
                  r.remise_eco_appliquee,
                  r.notes,
                  v.marque,
                  v.modele,
                  v.immatriculation,
                  v.annee,
                  v.kilometrage
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r ON r.id_creneau = c.id_creneau
                  AND r.statut != 'Annulé'
                LEFT JOIN vehicle v ON v.id = r.id_vehicle
                WHERE c.date_heure BETWEEN :debut_semaine AND :fin_semaine
                ORDER BY c.date_heure ASC, r.date_creation ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':debut_semaine' => $weekStart,
            ':fin_semaine' => $weekEnd,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFiltered(array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT(r.nom_client, " ", r.prenom_client) LIKE :search OR r.telephone_client LIKE :search OR v.immatriculation LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT 
                    r.id_rdv,
                    c.date_heure,
                    r.nom_client,
                    r.prenom_client,
                    r.telephone_client,
                    r.type_intervention,
                    r.statut,
                    v.immatriculation,
                    v.marque,
                    v.modele
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.date_heure DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countFiltered(array $filters): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT(r.nom_client, " ", r.prenom_client) LIKE :search OR r.telephone_client LIKE :search OR v.immatriculation LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = 'SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau LEFT JOIN vehicle v ON v.id = r.id_vehicle';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getFilteredForExport(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'r.statut = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(c.date_heure) = :filter_date';
            $params[':filter_date'] = $filters['date'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(CONCAT(r.nom_client, " ", r.prenom_client) LIKE :search OR r.telephone_client LIKE :search OR v.immatriculation LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql = "SELECT 
                    c.date_heure,
                    r.nom_client,
                    r.prenom_client,
                    r.telephone_client,
                    r.email_client,
                    v.immatriculation,
                    v.marque,
                    v.modele,
                    r.type_intervention,
                    r.statut,
                    r.remise_eco_appliquee
                FROM rendezvous_digital r
                INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
                LEFT JOIN vehicle v ON v.id = r.id_vehicle";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.date_heure DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuickStats(string $dayStart, string $dayEnd, string $weekStart, string $weekEnd): array
    {
        $stats = [];

        $stmtDay = $this->db->prepare('SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau WHERE c.date_heure BETWEEN :start_day AND :end_day');
        $stmtDay->execute([
            ':start_day' => $dayStart,
            ':end_day' => $dayEnd,
        ]);
        $stats['rdv_jour'] = (int) $stmtDay->fetchColumn();

        $stmtWeek = $this->db->prepare('SELECT COUNT(*) FROM rendezvous_digital r INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau WHERE c.date_heure BETWEEN :start_week AND :end_week');
        $stmtWeek->execute([
            ':start_week' => $weekStart,
            ':end_week' => $weekEnd,
        ]);
        $stats['rdv_semaine'] = (int) $stmtWeek->fetchColumn();

        $stmtPending = $this->db->query("SELECT COUNT(*) FROM rendezvous_digital WHERE statut = 'En attente'");
        $stats['rdv_attente'] = (int) $stmtPending->fetchColumn();

        $stmtFill = $this->db->prepare("SELECT 
                COALESCE(SUM(c.capacite_max), 0) AS total_capacity,
                COALESCE(SUM(active_counts.nb_rdv), 0) AS total_rdv
            FROM creneau_atelier c
            LEFT JOIN (
                SELECT id_creneau, COUNT(*) AS nb_rdv
                FROM rendezvous_digital
                WHERE statut IN ('En attente', 'Confirmé', 'En cours')
                GROUP BY id_creneau
            ) active_counts ON active_counts.id_creneau = c.id_creneau
            WHERE c.date_heure BETWEEN :start_week AND :end_week");
        $stmtFill->execute([
            ':start_week' => $weekStart,
            ':end_week' => $weekEnd,
        ]);
        $fillData = $stmtFill->fetch(PDO::FETCH_ASSOC);

        $capacity = (int) ($fillData['total_capacity'] ?? 0);
        $totalRdv = (int) ($fillData['total_rdv'] ?? 0);
        $stats['taux_remplissage'] = $capacity > 0 ? round(($totalRdv / $capacity) * 100, 2) : 0;

        return $stats;
    }
}
