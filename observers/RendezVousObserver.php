<?php

declare(strict_types=1);

class RendezVousObserver
{
    private PDO $db;
    private UrgenceService $urgenceService;

    public function __construct(PDO $db, ?UrgenceService $urgenceService = null)
    {
        $this->db = $db;
        $this->urgenceService = $urgenceService ?? new UrgenceService();
    }

    public function computeUrgence(array $rdv, ?int $rdvId = null): array
    {
        $temoins = $this->extractTemoins($rdv['temoins_panne'] ?? []);
        $temoins = $this->mergeDetectedTemoins($temoins, $rdv);
        $typePanne = (string) ($rdv['type_intervention'] ?? '');
        $km = $this->resolveKm($rdv);

        $base = $this->urgenceService->calculerUrgence($temoins, $typePanne, $km);
        $context = $this->buildContext($rdv, $rdvId);
        $bonus = $this->urgenceService->calculerScoreAvance($context);

        $score = $this->urgenceService->clampScore($base['score'] + $bonus['score']);
        $details = array_merge($base['details'], $bonus['details']);

        return [
            'score' => $score,
            'details' => $details,
        ];
    }

    public function shouldBroadcast(int $score): bool
    {
        return $score >= $this->urgenceService->getUrgentMinScore();
    }

    public function fetchRdvSummary(int $rdvId): ?array
    {
        $stmt = $this->db->prepare("SELECT
                r.id_rdv,
                c.date_heure,
                r.type_intervention,
                r.description_panne,
                r.circonstances_panne,
                r.temoins_panne,
                r.statut,
                r.urgence_score,
                r.urgence_details
            FROM rendezvous_digital r
            INNER JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
            WHERE r.id_rdv = :id_rdv
            LIMIT 1");
        $stmt->execute([':id_rdv' => $rdvId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function resolveKm(array $rdv): int
    {
        if (isset($rdv['kilometrage']) && is_numeric($rdv['kilometrage'])) {
            return (int) $rdv['kilometrage'];
        }

        if (!empty($rdv['id_vehicle'])) {
            $stmt = $this->db->prepare('SELECT kilometrage FROM vehicle WHERE id = :id');
            $stmt->execute([':id' => (int) $rdv['id_vehicle']]);
            $km = $stmt->fetchColumn();
            if (is_numeric($km)) {
                return (int) $km;
            }
        }

        return 0;
    }

    private function buildContext(array $rdv, ?int $rdvId): array
    {
        $context = [];

        if (!empty($rdv['date_creation'])) {
            $context['date_creation'] = (string) $rdv['date_creation'];
        } elseif ($rdvId !== null) {
            $stmt = $this->db->prepare('SELECT date_creation FROM rendezvous_digital WHERE id_rdv = :id_rdv');
            $stmt->execute([':id_rdv' => $rdvId]);
            $date = $stmt->fetchColumn();
            if ($date !== false) {
                $context['date_creation'] = (string) $date;
            }
        }

        if (!empty($rdv['id_creneau'])) {
            $stmt = $this->db->prepare('SELECT capacite_max FROM creneau_atelier WHERE id_creneau = :id_creneau');
            $stmt->execute([':id_creneau' => (int) $rdv['id_creneau']]);
            $capacite = $stmt->fetchColumn();
            if (is_numeric($capacite)) {
                $context['capacite_max'] = (int) $capacite;
            }

            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM rendezvous_digital
                WHERE id_creneau = :id_creneau
                AND statut IN ('En attente', 'Confirmé', 'En cours')");
            $stmtCount->execute([':id_creneau' => (int) $rdv['id_creneau']]);
            $context['nb_actifs'] = (int) $stmtCount->fetchColumn();
        }

        if (!empty($rdv['id_vehicle'])) {
            $sql = 'SELECT COUNT(*) FROM rendezvous_digital WHERE id_vehicle = :id_vehicle';
            $params = [':id_vehicle' => (int) $rdv['id_vehicle']];
            if ($rdvId !== null) {
                $sql .= ' AND id_rdv != :id_rdv';
                $params[':id_rdv'] = $rdvId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $context['historique_count'] = (int) $stmt->fetchColumn();
        }

        return $context;
    }

    private function extractTemoins($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function mergeDetectedTemoins(array $temoins, array $rdv): array
    {
        $description = $this->extractDescriptionText($rdv);
        if ($description === '') {
            return $temoins;
        }

        $detected = $this->urgenceService->detecterTemoinsDepuisTexte($description);
        if (empty($detected)) {
            return $temoins;
        }

        return array_values(array_unique(array_merge($temoins, $detected)));
    }

    private function extractDescriptionText(array $rdv): string
    {
        $parts = [];

        if (!empty($rdv['description_panne'])) {
            $parts[] = (string) $rdv['description_panne'];
        }

        if (!empty($rdv['panne_data_json']) && is_string($rdv['panne_data_json'])) {
            $decoded = json_decode($rdv['panne_data_json'], true);
            if (is_array($decoded)) {
                foreach (['symptomes', 'description'] as $key) {
                    if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                        $parts[] = $decoded[$key];
                    }
                }
            }
        }

        return trim(implode(' ', $parts));
    }
}
