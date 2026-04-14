<?php

require_once __DIR__ . '/../config/Database.php';

class CreneauModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function ensureMonthSlots(int $month, int $year): void
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $end = $start->modify('last day of this month 23:59:59');
        $this->ensureRangeSlots($start, $end);
    }

    public function ensureWeekSlots(DateTimeImmutable $weekStart, DateTimeImmutable $weekEnd): void
    {
        $this->ensureRangeSlots($weekStart, $weekEnd);
    }

    private function ensureRangeSlots(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        $current = $start->setTime(0, 0, 0);
        $last = $end->setTime(0, 0, 0);

        while ($current <= $last) {
            if ((int) $current->format('N') !== 7) {
                $this->ensureDaySlots($current->format('Y-m-d'));
            }
            $current = $current->modify('+1 day');
        }
    }

    public function ensureDaySlots(string $date): void
    {
        $checkStmt = $this->db->prepare('SELECT id_creneau FROM creneau_atelier WHERE date_heure = :date_heure LIMIT 1');
        $insertStmt = $this->db->prepare('INSERT INTO creneau_atelier (date_heure, est_heure_creuse, capacite_max) VALUES (:date_heure, :est_heure_creuse, :capacite_max)');

        for ($hour = 8; $hour <= 17; $hour++) {
            $dateTime = sprintf('%s %02d:00:00', $date, $hour);
            $checkStmt->execute([':date_heure' => $dateTime]);

            if (!$checkStmt->fetch()) {
                $isOffPeak = ($hour >= 13 && $hour < 15) ? 1 : 0;
                $insertStmt->execute([
                    ':date_heure' => $dateTime,
                    ':est_heure_creuse' => $isOffPeak,
                    ':capacite_max' => 3,
                ]);
            }
        }
    }

    public function getMonthAvailability(int $month, int $year): array
    {
        $sql = "SELECT 
                  c.id_creneau,
                  c.date_heure,
                  c.est_heure_creuse,
                  c.capacite_max,
                  COUNT(r.id_rdv) AS nb_rdv_pris,
                  (c.capacite_max - COUNT(r.id_rdv)) AS places_restantes
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r 
                  ON r.id_creneau = c.id_creneau
                  AND r.statut IN ('En attente', 'Confirmé', 'En cours')
                WHERE c.date_heure >= NOW()
                  AND MONTH(c.date_heure) = :mois
                  AND YEAR(c.date_heure) = :annee
                GROUP BY c.id_creneau
                ORDER BY c.date_heure ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':mois' => $month,
            ':annee' => $year,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDaySlots(string $date): array
    {
        $sql = "SELECT 
                  c.id_creneau,
                  c.date_heure,
                  c.est_heure_creuse,
                  c.capacite_max,
                  COUNT(r.id_rdv) AS nb_rdv_pris,
                  (c.capacite_max - COUNT(r.id_rdv)) AS places_restantes
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r
                  ON r.id_creneau = c.id_creneau
                  AND r.statut IN ('En attente', 'Confirmé', 'En cours')
                WHERE DATE(c.date_heure) = :date
                  AND c.date_heure >= NOW()
                GROUP BY c.id_creneau
                ORDER BY c.date_heure ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $idCreneau): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM creneau_atelier WHERE id_creneau = :id_creneau LIMIT 1');
        $stmt->execute([':id_creneau' => $idCreneau]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findOrCreateByDateTime(string $dateTime): int
    {
        $select = $this->db->prepare('SELECT id_creneau FROM creneau_atelier WHERE date_heure = :date_heure LIMIT 1');
        $select->execute([':date_heure' => $dateTime]);
        $found = $select->fetch(PDO::FETCH_ASSOC);
        if ($found) {
            return (int) $found['id_creneau'];
        }

        $hour = (int) date('H', strtotime($dateTime));
        $isOffPeak = ($hour >= 13 && $hour < 15) ? 1 : 0;

        $insert = $this->db->prepare('INSERT INTO creneau_atelier (date_heure, est_heure_creuse, capacite_max) VALUES (:date_heure, :est_heure_creuse, :capacite_max)');
        $insert->execute([
            ':date_heure' => $dateTime,
            ':est_heure_creuse' => $isOffPeak,
            ':capacite_max' => 3,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getWeekGridCounts(string $weekStart, string $weekEnd): array
    {
        $sql = "SELECT 
                    c.id_creneau,
                    c.date_heure,
                    c.capacite_max,
                    COUNT(r.id_rdv) AS nb_actifs
                FROM creneau_atelier c
                LEFT JOIN rendezvous_digital r
                    ON r.id_creneau = c.id_creneau
                    AND r.statut IN ('En attente', 'Confirmé', 'En cours')
                WHERE c.date_heure BETWEEN :week_start AND :week_end
                GROUP BY c.id_creneau
                ORDER BY c.date_heure ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':week_start' => $weekStart,
            ':week_end' => $weekEnd,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCapacity(int $idCreneau, int $capacity): bool
    {
        $stmt = $this->db->prepare('UPDATE creneau_atelier SET capacite_max = :capacite_max WHERE id_creneau = :id_creneau');
        return $stmt->execute([
            ':capacite_max' => $capacity,
            ':id_creneau' => $idCreneau,
        ]);
    }
}
