<?php

class IntegrationBridge
{
    private ?PDO $clientDb = null;
    private ?PDO $garageDb = null;

    public function getCurrentClientContext(): array
    {
        $context = [
            'id_client' => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
            'nom_client' => (string) ($_SESSION['user_nom'] ?? ''),
            'prenom_client' => (string) ($_SESSION['user_prenom'] ?? ''),
            'email_client' => (string) ($_SESSION['user_email'] ?? ''),
            'telephone' => '',
            'id_vehicle' => null,
            'id_rdv' => null,
        ];

        if (!empty($context['id_client'])) {
            $client = $this->getClientById((int) $context['id_client']);
            if ($client) {
                $context['nom_client'] = (string) ($client['nom'] ?? $context['nom_client']);
                $context['prenom_client'] = (string) ($client['prenom'] ?? $context['prenom_client']);
                $context['email_client'] = (string) ($client['email'] ?? $context['email_client']);
                $context['telephone'] = (string) ($client['telephone'] ?? '');
            }
        }

        $latestRdv = !empty($context['id_client']) ? $this->getLatestRdvForClient((int) $context['id_client']) : null;
        if ($latestRdv) {
            $context['id_rdv'] = isset($latestRdv['id_rdv']) ? (int) $latestRdv['id_rdv'] : null;
            $context['id_vehicle'] = isset($latestRdv['id_vehicle']) ? (int) $latestRdv['id_vehicle'] : null;
        }

        return $context;
    }

    public function enrichOrderData(array $data): array
    {
        $context = $this->getCurrentClientContext();

        if (empty($data['id_client'])) {
            $matchedClient = $this->findClientByContact(
                (string) ($data['nom_client'] ?? ''),
                (string) ($data['prenom_client'] ?? ''),
                (string) ($data['telephone'] ?? ''),
                (string) ($data['email_client'] ?? $data['email'] ?? '')
            );
            if ($matchedClient) {
                $context['id_client'] = (int) $matchedClient['id'];
                $context['nom_client'] = (string) ($matchedClient['nom'] ?? $context['nom_client']);
                $context['prenom_client'] = (string) ($matchedClient['prenom'] ?? $context['prenom_client']);
                $context['email_client'] = (string) ($matchedClient['email'] ?? $context['email_client']);
                $context['telephone'] = (string) ($matchedClient['telephone'] ?? $context['telephone']);
            }
        }

        $clientId = (int) ($data['id_client'] ?? $context['id_client'] ?? 0);
        if ($clientId > 0 && (empty($data['id_rdv']) || empty($data['id_vehicle']))) {
            $latestRdv = $this->getLatestRdvForClient($clientId);
            if ($latestRdv) {
                $data['id_rdv'] = $data['id_rdv'] ?? (int) ($latestRdv['id_rdv'] ?? 0);
                $data['id_vehicle'] = $data['id_vehicle'] ?? (int) ($latestRdv['id_vehicle'] ?? 0);
            }
        }

        $data['id_client'] = $clientId > 0 ? $clientId : null;
        $data['email_client'] = $data['email_client'] ?? $data['email'] ?? $context['email_client'] ?? null;
        $data['id_vehicle'] = !empty($data['id_vehicle']) ? (int) $data['id_vehicle'] : ($context['id_vehicle'] ?? null);
        $data['id_rdv'] = !empty($data['id_rdv']) ? (int) $data['id_rdv'] : ($context['id_rdv'] ?? null);

        if (!empty($data['id_client']) && empty($data['id_diagnostic'])) {
            $diag = $this->getLatestDiagnosticForClient((int)$data['id_client']);
            if ($diag) $data['id_diagnostic'] = (int)$diag['id_diagnostic'];
        }

        return $data;
    }

    public function ensureCommandesIntegrationSchema(PDO $partsDb): void
    {
        $columns = [
            'id_client' => 'ALTER TABLE commandes ADD COLUMN id_client INT NULL AFTER id_piece',
            'email_client' => 'ALTER TABLE commandes ADD COLUMN email_client VARCHAR(255) NULL AFTER telephone',
            'id_vehicle' => 'ALTER TABLE commandes ADD COLUMN id_vehicle INT NULL AFTER email_client',
            'id_rdv' => 'ALTER TABLE commandes ADD COLUMN id_rdv INT NULL AFTER id_vehicle',
        ];

        foreach ($columns as $column => $sql) {
            try {
                $stmt = $partsDb->query("SHOW COLUMNS FROM commandes LIKE '" . $column . "'");
                if (!$stmt->fetch()) {
                    $partsDb->exec($sql);
                }
            } catch (Throwable $e) {
            }
        }

        $indexes = [
            'idx_commandes_client' => 'ALTER TABLE commandes ADD INDEX idx_commandes_client (id_client)',
            'idx_commandes_vehicle' => 'ALTER TABLE commandes ADD INDEX idx_commandes_vehicle (id_vehicle)',
            'idx_commandes_rdv' => 'ALTER TABLE commandes ADD INDEX idx_commandes_rdv (id_rdv)',
            'idx_commandes_email' => 'ALTER TABLE commandes ADD INDEX idx_commandes_email (email_client)',
        ];

        foreach ($indexes as $index => $sql) {
            try {
                $stmt = $partsDb->query("SHOW INDEX FROM commandes WHERE Key_name = '" . $index . "'");
                if (!$stmt->fetch()) {
                    $partsDb->exec($sql);
                }
            } catch (Throwable $e) {
            }
        }
    }

    public function backfillCommandes(PDO $partsDb): void
    {
        $phoneExprC = $this->normalizedPhoneSql('c.telephone');
        $phoneExprU = $this->normalizedPhoneSql('u.telephone');

        try {
            $partsDb->exec(
                "UPDATE commandes c
                 INNER JOIN garage1.user u
                    ON u.post = 'client'
                   AND {$phoneExprC} <> ''
                   AND {$phoneExprC} = {$phoneExprU}
                 SET c.id_client = COALESCE(c.id_client, u.id),
                     c.email_client = COALESCE(c.email_client, u.email)
                 WHERE c.id_client IS NULL"
            );
        } catch (Throwable $e) {
        }

        try {
            $partsDb->exec(
                "UPDATE commandes c
                 INNER JOIN garage1.user u
                    ON u.post = 'client'
                   AND c.id_client IS NULL
                   AND LOWER(TRIM(c.nom_client)) IN (
                        LOWER(TRIM(u.nom)),
                        LOWER(TRIM(CONCAT(u.prenom, ' ', u.nom))),
                        LOWER(TRIM(CONCAT(u.nom, ' ', u.prenom)))
                   )
                 SET c.id_client = u.id,
                     c.email_client = COALESCE(c.email_client, u.email)"
            );
        } catch (Throwable $e) {
        }

        try {
            $clientCount = (int) $partsDb->query("SELECT COUNT(*) FROM garage1.user WHERE post = 'client'")->fetchColumn();
            if ($clientCount === 1) {
                $partsDb->exec(
                    "UPDATE commandes c
                     CROSS JOIN (
                        SELECT id, email
                        FROM garage1.user
                        WHERE post = 'client'
                        LIMIT 1
                     ) u
                     SET c.id_client = COALESCE(c.id_client, u.id),
                         c.email_client = COALESCE(c.email_client, u.email)
                     WHERE c.id_client IS NULL"
                );
            }
        } catch (Throwable $e) {
        }

        try {
            $partsDb->exec(
                "UPDATE commandes c
                 SET c.id_rdv = (
                        SELECT r.id_rdv
                        FROM smart_garage.rendezvous_digital r
                        WHERE r.id_client = c.id_client
                        ORDER BY COALESCE(r.date_modification, r.date_creation) DESC, r.id_rdv DESC
                        LIMIT 1
                     ),
                     c.id_vehicle = (
                        SELECT r.id_vehicle
                        FROM smart_garage.rendezvous_digital r
                        WHERE r.id_client = c.id_client
                        ORDER BY COALESCE(r.date_modification, r.date_creation) DESC, r.id_rdv DESC
                        LIMIT 1
                     )
                 WHERE c.id_client IS NOT NULL
                   AND (c.id_rdv IS NULL OR c.id_vehicle IS NULL)"
            );
        } catch (Throwable $e) {
        }

        try {
            $partsDb->exec(
                "UPDATE garanties g
                 INNER JOIN commandes c ON c.id_commande = g.id_commande
                 SET g.id_client = c.id_client
                 WHERE (g.id_client IS NULL OR g.id_client = 0)
                   AND c.id_client IS NOT NULL"
            );
        } catch (Throwable $e) {
        }
    }

    public function createIntegrationViews(PDO $partsDb): void
    {
        try {
            $partsDb->exec(
                "CREATE OR REPLACE VIEW vue_commandes_integrees AS
                 SELECT
                    c.*,
                    u.email AS client_email_compte,
                    u.statut AS client_statut_compte,
                    v.marque AS vehicule_marque,
                    v.modele AS vehicule_modele,
                    v.immatriculation AS vehicule_immatriculation,
                    r.type_intervention AS rdv_type_intervention,
                    r.statut AS rdv_statut,
                    p.nom AS piece_nom,
                    p.reference AS piece_reference
                 FROM commandes c
                 LEFT JOIN garage1.user u ON u.id = c.id_client
                 LEFT JOIN smart_garage.vehicle v ON v.id = c.id_vehicle
                 LEFT JOIN smart_garage.rendezvous_digital r ON r.id_rdv = c.id_rdv
                 LEFT JOIN pieces p ON p.id_piece = c.id_piece"
            );
        } catch (Throwable $e) {
        }
    }

    public function getLatestDiagnosticForClient(int $clientId): ?array
    {
        $db = $this->getGarageDb();
        if (!$db) return null;
        try {
            $stmt = $db->prepare(
                'SELECT id_diagnostic, id_vehicle, id_rdv, type_diagnostic, statut, date_diagnostic
                 FROM diagnostic WHERE id_client = :id
                 ORDER BY date_diagnostic DESC, id_diagnostic DESC LIMIT 1'
            );
            $stmt->execute([':id' => $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) { return null; }
    }

    public function getClientById(int $id): ?array
    {
        $db = $this->getClientDb();
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare('SELECT id, nom, prenom, email, telephone, adresse, statut FROM user WHERE id = :id AND post = :post LIMIT 1');
            $stmt->execute([':id' => $id, ':post' => 'client']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function findClientByContact(string $nom, string $prenom, string $telephone, string $email = ''): ?array
    {
        $db = $this->getClientDb();
        if (!$db) {
            return null;
        }

        try {
            if ($email !== '') {
                $stmt = $db->prepare('SELECT id, nom, prenom, email, telephone, adresse, statut FROM user WHERE post = :post AND email = :email LIMIT 1');
                $stmt->execute([':post' => 'client', ':email' => $email]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return $row;
                }
            }

            $phone = $this->normalizePhone($telephone);
            if ($phone !== '') {
                $stmt = $db->query("SELECT id, nom, prenom, email, telephone, adresse, statut FROM user WHERE post = 'client'");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($this->normalizePhone((string) ($row['telephone'] ?? '')) === $phone) {
                        return $row;
                    }
                }
            }

            $fullName = strtolower(trim($prenom . ' ' . $nom));
            $nameOnly = strtolower(trim($nom));
            if ($fullName !== '' || $nameOnly !== '') {
                $stmt = $db->query("SELECT id, nom, prenom, email, telephone, adresse, statut FROM user WHERE post = 'client'");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rowFullA = strtolower(trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? '')));
                    $rowFullB = strtolower(trim((string) ($row['nom'] ?? '') . ' ' . (string) ($row['prenom'] ?? '')));
                    if ($fullName !== '' && ($fullName === $rowFullA || $fullName === $rowFullB)) {
                        return $row;
                    }
                    if ($nameOnly !== '' && $nameOnly === strtolower(trim((string) ($row['nom'] ?? '')))) {
                        return $row;
                    }
                }
            }
        } catch (Throwable $e) {
        }

        return null;
    }

    private function getLatestRdvForClient(int $clientId): ?array
    {
        $db = $this->getGarageDb();
        if (!$db) {
            return null;
        }

        try {
            $stmt = $db->prepare(
                'SELECT id_rdv, id_vehicle
                 FROM rendezvous_digital
                 WHERE id_client = :id_client
                 ORDER BY COALESCE(date_modification, date_creation) DESC, id_rdv DESC
                 LIMIT 1'
            );
            $stmt->execute([':id_client' => $clientId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function getClientDb(): ?PDO
    {
        if ($this->clientDb instanceof PDO) {
            return $this->clientDb;
        }

        try {
            $this->clientDb = new PDO(
                'mysql:host=localhost;dbname=garage1;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Throwable $e) {
            $this->clientDb = null;
        }

        return $this->clientDb;
    }

    private function getGarageDb(): ?PDO
    {
        if ($this->garageDb instanceof PDO) {
            return $this->garageDb;
        }

        try {
            $this->garageDb = new PDO(
                'mysql:host=localhost;dbname=smart_garage;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (Throwable $e) {
            $this->garageDb = null;
        }

        return $this->garageDb;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null) {
            return '';
        }
        if (str_starts_with($digits, '216') && strlen($digits) > 8) {
            $digits = substr($digits, 3);
        }
        return $digits;
    }

    private function normalizedPhoneSql(string $column): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ' ', ''), '-', ''), '+216', ''), '+', ''), '.', '')";
    }
}
