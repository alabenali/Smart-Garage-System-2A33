<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/loyalty_rules.php';

class LoyaltyService
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function getOrCreateAccount(string $email, string $nom, string $prenom, string $tel): array
    {
        $email = $this->normalizeEmail($email);
        if ($email === '') {
            $this->log('Compte fidelite ignore: email client manquant.');
            return [];
        }

        $stmt = $this->db->prepare('SELECT * FROM loyalty_account WHERE client_email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($account) {
            return $account;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO loyalty_account (client_nom, client_prenom, client_email, client_telephone, derniere_activite)
             VALUES (:nom, :prenom, :email, :telephone, NOW())'
        );
        $stmt->execute([
            ':nom' => $this->cleanName($nom),
            ':prenom' => $this->cleanName($prenom),
            ':email' => $email,
            ':telephone' => trim($tel),
        ]);

        $id = (int) $this->db->lastInsertId();
        $stmt = $this->db->prepare('SELECT * FROM loyalty_account WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function calculerPoints(int $id_rdv): array
    {
        $rdv = $this->findRdv($id_rdv);
        if (!$rdv) {
            return ['points_base' => 0, 'bonus' => 0, 'total' => 0, 'detail' => 'RDV introuvable'];
        }

        $type = trim((string) ($rdv['type_intervention'] ?? ''));
        $pointsBase = $this->pointsForType($type);
        $bonus = 0;
        $details = [];
        $details[] = ($type !== '' ? $type : 'Intervention') . ' +' . $pointsBase;

        if ((int) ($rdv['est_heure_creuse'] ?? 0) === 1) {
            $bonus += (int) LOYALTY_RULES['bonus_heure_creuse'];
            $details[] = 'Heure creuse +' . (int) LOYALTY_RULES['bonus_heure_creuse'];
        }

        $email = $this->normalizeEmail((string) ($rdv['email_client'] ?? ''));
        if ($email !== '' && $this->isFirstRewardedRdv($email, $id_rdv)) {
            $bonus += (int) LOYALTY_RULES['bonus_premier_rdv'];
            $details[] = 'Premier RDV +' . (int) LOYALTY_RULES['bonus_premier_rdv'];
        }

        $notes = strtolower((string) ($rdv['notes'] ?? ''));
        if (strpos($notes, 'parrain') !== false || strpos($notes, 'recommand') !== false) {
            $bonus += (int) LOYALTY_RULES['bonus_parrainage'];
            $details[] = 'Parrainage +' . (int) LOYALTY_RULES['bonus_parrainage'];
        }

        $total = max(0, $pointsBase + $bonus);
        return [
            'points_base' => $pointsBase,
            'bonus' => $bonus,
            'total' => $total,
            'detail' => implode(', ', $details),
        ];
    }

    public function attribuerPoints(int $id_rdv): bool
    {
        $rdv = $this->findRdv($id_rdv);
        if (!$rdv) {
            $this->log('Attribution fidelite impossible: RDV #' . $id_rdv . ' introuvable.');
            return false;
        }

        $email = $this->normalizeEmail((string) ($rdv['email_client'] ?? ''));
        if ($email === '') {
            $this->log('Attribution fidelite ignoree: email absent pour RDV #' . $id_rdv . '.');
            return false;
        }

        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $ownTransaction = true;
            } else {
                $ownTransaction = false;
            }

            $duplicate = $this->db->prepare(
                "SELECT id FROM loyalty_transactions WHERE id_rdv = :id_rdv AND type = 'gain' LIMIT 1"
            );
            $duplicate->execute([':id_rdv' => $id_rdv]);
            if ($duplicate->fetchColumn()) {
                if ($ownTransaction) {
                    $this->db->commit();
                }
                return true;
            }

            $account = $this->getOrCreateAccount(
                $email,
                (string) ($rdv['nom_client'] ?? ''),
                (string) ($rdv['prenom_client'] ?? ''),
                (string) ($rdv['telephone_client'] ?? '')
            );

            if (empty($account['id'])) {
                if ($ownTransaction) {
                    $this->db->rollBack();
                }
                return false;
            }

            $calculation = $this->calculerPoints($id_rdv);
            $points = max(0, (int) $calculation['total']);
            if ($points <= 0) {
                if ($ownTransaction) {
                    $this->db->commit();
                }
                return true;
            }

            $insert = $this->db->prepare(
                "INSERT INTO loyalty_transactions (loyalty_id, id_rdv, type, points, description)
                 VALUES (:loyalty_id, :id_rdv, 'gain', :points, :description)"
            );
            $insert->execute([
                ':loyalty_id' => (int) $account['id'],
                ':id_rdv' => $id_rdv,
                ':points' => $points,
                ':description' => (string) $calculation['detail'],
            ]);

            $update = $this->db->prepare(
                'UPDATE loyalty_account
                 SET points_total = points_total + :points, derniere_activite = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                ':points' => $points,
                ':id' => (int) $account['id'],
            ]);

            $this->recalculerPalier((int) $account['id']);

            if ($ownTransaction) {
                $this->db->commit();
            }

            return true;
        } catch (Throwable $e) {
            if (isset($ownTransaction) && $ownTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->log('Erreur attribution fidelite RDV #' . $id_rdv . ': ' . $e->getMessage());
            return false;
        }
    }

    public function recalculerPalier(int $loyalty_id): string
    {
        $stmt = $this->db->prepare('SELECT points_restants FROM loyalty_account WHERE id = :id');
        $stmt->execute([':id' => $loyalty_id]);
        $points = max(0, (int) $stmt->fetchColumn());

        $palier = 'Bronze';
        if ($points >= 600) {
            $palier = 'Platinum';
        } elseif ($points >= 300) {
            $palier = 'Or';
        } elseif ($points >= 100) {
            $palier = 'Argent';
        }

        $update = $this->db->prepare('UPDATE loyalty_account SET palier_actuel = :palier WHERE id = :id');
        $update->execute([':palier' => $palier, ':id' => $loyalty_id]);

        return $palier;
    }

    public function utiliserPoints(int $loyalty_id, int $points, string $description): bool
    {
        $points = max(0, $points);
        if ($loyalty_id <= 0 || $points <= 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('SELECT points_restants FROM loyalty_account WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $loyalty_id]);
            $restants = $stmt->fetchColumn();
            if ($restants === false || (int) $restants < $points) {
                $this->db->rollBack();
                return false;
            }

            $insert = $this->db->prepare(
                "INSERT INTO loyalty_transactions (loyalty_id, type, points, description)
                 VALUES (:loyalty_id, 'utilisation', :points, :description)"
            );
            $insert->execute([
                ':loyalty_id' => $loyalty_id,
                ':points' => $points,
                ':description' => trim($description),
            ]);

            $update = $this->db->prepare(
                'UPDATE loyalty_account
                 SET points_utilises = points_utilises + :points, derniere_activite = NOW()
                 WHERE id = :id'
            );
            $update->execute([':points' => $points, ':id' => $loyalty_id]);

            $this->recalculerPalier($loyalty_id);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->log('Erreur utilisation points fidelite: ' . $e->getMessage());
            return false;
        }
    }

    public function getHistorique(int $loyalty_id, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            'SELECT id, loyalty_id, id_rdv, type, points, description, date_transaction
             FROM loyalty_transactions
             WHERE loyalty_id = :id
             ORDER BY date_transaction DESC, id DESC
             LIMIT ' . $limit
        );
        $stmt->execute([':id' => $loyalty_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProgression(int $loyalty_id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM loyalty_account WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $loyalty_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) {
            return [];
        }

        $paliers = $this->getPaliers();
        $currentName = (string) ($account['palier_actuel'] ?? 'Bronze');
        $points = max(0, (int) ($account['points_restants'] ?? 0));
        $current = $paliers[$currentName] ?? ['points_requis' => 0, 'nom' => $currentName];
        $next = null;

        foreach ($paliers as $palier) {
            if ((int) $palier['points_requis'] > $points) {
                $next = $palier;
                break;
            }
        }

        if (!$next) {
            return [
                'palier_actuel' => $currentName,
                'prochain' => null,
                'points_restants' => 0,
                'progression_pct' => 100,
                'points_actuels' => $points,
                'points_requis_prochain' => $points,
                'points_manquants' => 0,
                'account' => $account,
                'palier' => $current,
                'prochain_palier' => null,
            ];
        }

        $currentFloor = (int) ($current['points_requis'] ?? 0);
        $nextRequired = (int) $next['points_requis'];
        $span = max(1, $nextRequired - $currentFloor);
        $progression = (int) round((($points - $currentFloor) / $span) * 100);
        $progression = max(0, min(100, $progression));

        return [
            'palier_actuel' => $currentName,
            'prochain' => $next['nom'],
            'points_restants' => max(0, $nextRequired - $points),
            'progression_pct' => $progression,
            'points_actuels' => $points,
            'points_requis_prochain' => $nextRequired,
            'points_manquants' => max(0, $nextRequired - $points),
            'account' => $account,
            'palier' => $current,
            'prochain_palier' => $next,
        ];
    }

    public function expirePointsInactifs(): int
    {
        $months = max(1, (int) LOYALTY_RULES['expiration_mois']);
        $stmt = $this->db->prepare(
            'SELECT id, points_restants
             FROM loyalty_account
             WHERE points_restants > 0
               AND COALESCE(derniere_activite, date_inscription) < DATE_SUB(NOW(), INTERVAL ' . $months . ' MONTH)'
        );
        $stmt->execute();
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $expired = 0;
        foreach ($accounts as $account) {
            $points = max(0, (int) $account['points_restants']);
            if ($points <= 0) {
                continue;
            }

            try {
                $this->db->beginTransaction();
                $insert = $this->db->prepare(
                    "INSERT INTO loyalty_transactions (loyalty_id, type, points, description)
                     VALUES (:loyalty_id, 'expiration', :points, :description)"
                );
                $insert->execute([
                    ':loyalty_id' => (int) $account['id'],
                    ':points' => $points,
                    ':description' => 'Expiration pour inactivite',
                ]);

                $update = $this->db->prepare(
                    'UPDATE loyalty_account SET points_utilises = points_utilises + :points WHERE id = :id'
                );
                $update->execute([':points' => $points, ':id' => (int) $account['id']]);
                $this->recalculerPalier((int) $account['id']);
                $this->db->commit();
                $expired++;
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $this->log('Erreur expiration fidelite compte #' . (int) $account['id'] . ': ' . $e->getMessage());
            }
        }

        return $expired;
    }

    public function getAccountByEmail(string $email): ?array
    {
        $email = $this->normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM loyalty_account WHERE client_email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        return $account ?: null;
    }

    public function getPaliers(): array
    {
        try {
            $stmt = $this->db->query('SELECT * FROM loyalty_paliers ORDER BY points_requis ASC');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $rows = [];
        }

        if (!$rows) {
            $rows = [
                ['nom' => 'Bronze', 'points_requis' => 0, 'couleur_hex' => '#CD7F32', 'icone' => 'B', 'avantage_desc' => 'Acces au programme fidelite', 'remise_pct' => 0],
                ['nom' => 'Argent', 'points_requis' => 100, 'couleur_hex' => '#C0C0C0', 'icone' => 'A', 'avantage_desc' => '5% de remise sur chaque intervention', 'remise_pct' => 5],
                ['nom' => 'Or', 'points_requis' => 300, 'couleur_hex' => '#FFD700', 'icone' => 'O', 'avantage_desc' => '10% de remise + vidange offerte/an', 'remise_pct' => 10],
                ['nom' => 'Platinum', 'points_requis' => 600, 'couleur_hex' => '#E5E4E2', 'icone' => 'P', 'avantage_desc' => '15% de remise + priorite creneaux', 'remise_pct' => 15],
            ];
        }

        $paliers = [];
        foreach ($rows as $row) {
            $paliers[(string) $row['nom']] = $row;
        }
        return $paliers;
    }

    public function getAdminStats(): array
    {
        $stats = [
            'membres_total' => 0,
            'points_distribues' => 0,
            'palier_moyen' => 'Bronze',
            'recompenses_utilisees' => 0,
            'repartition' => [],
            'top_clients' => [],
        ];

        $stats['membres_total'] = (int) $this->db->query('SELECT COUNT(*) FROM loyalty_account')->fetchColumn();
        $stats['points_distribues'] = (int) $this->db->query("SELECT COALESCE(SUM(points), 0) FROM loyalty_transactions WHERE type IN ('gain','bonus')")->fetchColumn();
        $stats['recompenses_utilisees'] = (int) $this->db->query("SELECT COUNT(*) FROM loyalty_transactions WHERE type = 'utilisation'")->fetchColumn();

        $rankSql = "SELECT CASE palier_actuel
                    WHEN 'Bronze' THEN 1 WHEN 'Argent' THEN 2 WHEN 'Or' THEN 3 WHEN 'Platinum' THEN 4
                    ELSE 1 END AS rang
                    FROM loyalty_account";
        $ranks = $this->db->query($rankSql)->fetchAll(PDO::FETCH_COLUMN);
        if ($ranks) {
            $avg = (int) round(array_sum(array_map('intval', $ranks)) / count($ranks));
            $stats['palier_moyen'] = [1 => 'Bronze', 2 => 'Argent', 3 => 'Or', 4 => 'Platinum'][$avg] ?? 'Bronze';
        }

        $paliers = $this->getPaliers();
        foreach ($paliers as $name => $palier) {
            $stats['repartition'][$name] = [
                'nom' => $name,
                'icone' => $palier['icone'] ?? '',
                'couleur_hex' => $palier['couleur_hex'] ?? '#E85D04',
                'count' => 0,
            ];
        }

        $rows = $this->db->query('SELECT palier_actuel, COUNT(*) AS nb FROM loyalty_account GROUP BY palier_actuel')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $name = (string) $row['palier_actuel'];
            if (isset($stats['repartition'][$name])) {
                $stats['repartition'][$name]['count'] = (int) $row['nb'];
            }
        }

        $stats['top_clients'] = $this->getTopClients(10, false);
        return $stats;
    }

    public function getTopClients(int $limit = 10, bool $anonymized = false): array
    {
        $limit = max(1, min(100, $limit));
        $rows = $this->db->query(
            "SELECT la.id, la.client_nom, la.client_prenom, la.client_email, la.points_restants, la.points_total,
                    la.palier_actuel, la.derniere_activite,
                    COUNT(DISTINCT lt.id_rdv) AS nb_rdv,
                    MAX(r.date_modification) AS dernier_rdv
             FROM loyalty_account la
             LEFT JOIN loyalty_transactions lt ON lt.loyalty_id = la.id AND lt.type = 'gain'
             LEFT JOIN rendezvous_digital r ON r.id_rdv = lt.id_rdv
             GROUP BY la.id
             ORDER BY la.points_restants DESC, la.points_total DESC
             LIMIT " . $limit
        )->fetchAll(PDO::FETCH_ASSOC);

        if (!$anonymized) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $prenom = trim((string) ($row['client_prenom'] ?? 'Client'));
            $nom = trim((string) ($row['client_nom'] ?? ''));
            $row['nom_affiche'] = $prenom . ($nom !== '' ? ' ' . strtoupper(substr($nom, 0, 1)) . '.' : '');
            unset($row['client_email']);
        }
        unset($row);

        return $rows;
    }

    public function getAllMembers(): array
    {
        $stmt = $this->db->query(
            'SELECT id, client_nom, client_prenom, client_email, client_telephone, points_total,
                    points_utilises, points_restants, palier_actuel, date_inscription, derniere_activite
             FROM loyalty_account
             ORDER BY points_restants DESC, client_nom ASC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function attribuerPointsManuels(string $email, int $points, string $description): bool
    {
        $points = max(0, $points);
        $email = $this->normalizeEmail($email);
        if ($email === '' || $points <= 0 || trim($description) === '') {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $account = $this->getAccountByEmail($email);
            if (!$account) {
                $account = $this->getOrCreateAccount($email, '', '', '');
            }
            if (empty($account['id'])) {
                $this->db->rollBack();
                return false;
            }

            $insert = $this->db->prepare(
                "INSERT INTO loyalty_transactions (loyalty_id, type, points, description)
                 VALUES (:loyalty_id, 'bonus', :points, :description)"
            );
            $insert->execute([
                ':loyalty_id' => (int) $account['id'],
                ':points' => $points,
                ':description' => trim($description),
            ]);

            $update = $this->db->prepare(
                'UPDATE loyalty_account SET points_total = points_total + :points, derniere_activite = NOW() WHERE id = :id'
            );
            $update->execute([':points' => $points, ':id' => (int) $account['id']]);
            $this->recalculerPalier((int) $account['id']);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->log('Erreur bonus manuel fidelite: ' . $e->getMessage());
            return false;
        }
    }

    private function findRdv(int $idRdv): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, c.date_heure, c.est_heure_creuse
             FROM rendezvous_digital r
             LEFT JOIN creneau_atelier c ON c.id_creneau = r.id_creneau
             WHERE r.id_rdv = :id_rdv
             LIMIT 1'
        );
        $stmt->execute([':id_rdv' => $idRdv]);
        $rdv = $stmt->fetch(PDO::FETCH_ASSOC);
        return $rdv ?: null;
    }

    private function pointsForType(string $type): int
    {
        $rules = LOYALTY_RULES['points_par_type'];
        if (isset($rules[$type])) {
            return max(0, (int) $rules[$type]);
        }

        $normalized = $this->normalizeType($type);
        foreach ($rules as $ruleType => $points) {
            if ($this->normalizeType((string) $ruleType) === $normalized) {
                return max(0, (int) $points);
            }
        }

        return 10;
    }

    private function isFirstRewardedRdv(string $email, int $idRdv): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM loyalty_transactions lt
             INNER JOIN loyalty_account la ON la.id = lt.loyalty_id
             WHERE la.client_email = :email
               AND lt.type = 'gain'
               AND (lt.id_rdv IS NULL OR lt.id_rdv <> :id_rdv)"
        );
        $stmt->execute([':email' => $email, ':id_rdv' => $idRdv]);
        return (int) $stmt->fetchColumn() === 0;
    }

    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    private function cleanName(string $value): string
    {
        $value = trim($value);
        return $value !== '' ? $value : 'Client';
    }

    private function normalizeType(string $value): string
    {
        $value = strtolower(trim($value));
        $from = ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ç', 'œ', ' ', '-', '_'];
        $to = ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c', 'oe', '', '', ''];
        return str_replace($from, $to, $value);
    }

    private function log(string $message): void
    {
        $file = __DIR__ . '/../logs/loyalty.log';
        @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
}
