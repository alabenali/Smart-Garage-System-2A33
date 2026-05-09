<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/ValidationHelper.php';

class PieceController
{
    private $conn;
    private $uploadDir;
    private $uploadWebPath;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
        $this->uploadDir = __DIR__ . '/../views/assets/uploads/pieces';
        $this->uploadWebPath = 'views/assets/uploads/pieces';

        $this->ensurePieceImageColumn();
        $this->syncStoredPieceImages();
    }

    public function showCatalogue()
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'categorie' => trim((string) ($_GET['categorie'] ?? '')),
            'stock' => trim((string) ($_GET['stock'] ?? '')),
        ];

        $page = $this->getPageNumber();
        $catalogue = $this->getPaginatedPieces($page, 6, $filters);

        $pieces = $catalogue['items'];
        $pagination = $catalogue['pagination'];
        $paginationQuery = $filters;
        $categories = $this->getPieceCategories();

        require __DIR__ . '/../views/front/piece_catalogue.php';
    }

    public function dashboard()
    {
        $pieces = $this->getAllPieces();
        $commandes = $this->getAllCommandes();

        $totalPieces = count($pieces);
        $totalStock = 0;
        $totalValue = 0.0;
        $alertCount = 0;
        $categoryStats = [];
        $brandStats = [];
        $totalCommandes = count($commandes);

        foreach ($pieces as $piece) {
            $stock = (int) $piece['quantite_stock'];
            $seuil = (int) $piece['seuil_alerte'];
            $prix = (float) $piece['prix_unitaire'];

            $totalStock += $stock;
            $totalValue += $prix * $stock;

            if ($stock <= $seuil) {
                $alertCount++;
            }

            $categorie = $piece['categorie'];
            $marque = $piece['marque'];

            if (!isset($categoryStats[$categorie])) {
                $categoryStats[$categorie] = 0;
            }
            $categoryStats[$categorie]++;

            if (!isset($brandStats[$marque])) {
                $brandStats[$marque] = 0;
            }
            $brandStats[$marque]++;
        }

        require __DIR__ . '/../views/back/dashboard.php';
    }

    public function managePieces()
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        $page = $this->getPageNumber();
        $result = $this->getPaginatedPieces($page, 8, $filters);

        $pieces = $result['items'];
        $pagination = $result['pagination'];
        $paginationQuery = $filters;
        $demandesPiece = $this->getDemandesPiece();
        $success = isset($_GET['success']) ? htmlspecialchars((string) $_GET['success']) : '';
        $error = isset($_GET['error']) ? htmlspecialchars((string) $_GET['error']) : '';

        require __DIR__ . '/../views/back/piece_list.php';
    }

    public function addPiece()
    {
        $errors = [];
        $piece = [
            'reference' => '',
            'nom' => '',
            'description' => '',
            'categorie' => '',
            'marque' => '',
            'prix_unitaire' => '',
            'quantite_stock' => '',
            'seuil_alerte' => '',
            'image' => null,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = ValidationHelper::validatePiece($_POST);
            $errors = $validation['errors'];
            $piece = array_merge($piece, $_POST);

            if (empty($errors)) {
                $sanitized = $validation['sanitized'];
                $sanitized['image'] = $this->handleUploadedPieceImage(
                    $_FILES['image_file'] ?? null,
                    $sanitized['reference'],
                    $errors
                );

                if (empty($errors)) {
                    try {
                        $created = $this->insertPiece($sanitized);

                        if ($created) {
                            if (!empty($sanitized['image'])) {
                                $this->forcePieceImageUpdate((int) $created, $sanitized['image']);
                            }
                            header('Location: index.php?action=managePieces&success=' . rawurlencode('Piece ajoutee avec succes'));
                            exit;
                        }

                        $errors[] = 'Erreur lors de l\'ajout.';
                    } catch (PDOException $e) {
                        if ((string) $e->getCode() === '23000') {
                            $errors[] = 'Cette reference existe deja dans la base de donnees.';
                        } else {
                            $errors[] = 'Erreur lors de l\'ajout : ' . $e->getMessage();
                        }
                    }
                }

                $piece = array_merge($piece, $sanitized);
            }
        }

        require __DIR__ . '/../views/back/piece_add.php';
    }

    public function updatePiece()
    {
        $errors = [];
        $success = '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $piece = $this->getPieceById($id);

        if (!$piece) {
            header('Location: index.php?action=managePieces&error=' . rawurlencode('Piece introuvable'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = ValidationHelper::validatePiece($_POST);
            $errors = $validation['errors'];
            $removeImage = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';

            if (empty($errors)) {
                $sanitized = $validation['sanitized'];

                if ($removeImage) {
                    $this->deleteImageFile(isset($piece['image']) ? $piece['image'] : null);
                    $currentImage = null;
                } else {
                    $currentImage = isset($piece['image']) ? $piece['image'] : null;
                }

                $uploadedImage = $this->handleUploadedPieceImage(
                    $_FILES['image_file'] ?? null,
                    $sanitized['reference'],
                    $errors,
                    $currentImage
                );

                $sanitized['image'] = $uploadedImage;

                if (empty($errors)) {
                    try {
                        $updated = $this->updatePieceById($id, $sanitized);

                        if ($updated) {
                            if (array_key_exists('image', $sanitized)) {
                                $this->forcePieceImageUpdate($id, $sanitized['image']);
                            }
                            $success = 'Piece mise a jour avec succes.';
                            $piece = $this->getPieceById($id);

                            // ── Vérification alerte Telegram ──
                            require_once __DIR__ . '/../services/StockAlertNotifier.php';
                            
                            try {
                                $stockNotifier = new StockAlertNotifier($this->conn);
                                $stockNotifier->notifyIfNeeded($piece);
                            } catch (Throwable $t) {
                                error_log("Erreur Telegram dans updatePiece : " . $t->getMessage());
                            }
                        } else {
                            $errors[] = 'Erreur lors de la mise a jour.';
                            $piece = array_merge($piece, $_POST, ['image' => $sanitized['image']]);
                        }
                    } catch (PDOException $e) {
                        if ((string) $e->getCode() === '23000') {
                            $errors[] = 'Cette reference existe deja dans la base de donnees.';
                        } else {
                            $errors[] = 'Erreur lors de la mise a jour : ' . $e->getMessage();
                        }
                        $piece = array_merge($piece, $_POST, ['image' => $sanitized['image']]);
                    }
                } else {
                    $piece = array_merge($piece, $_POST, ['image' => $sanitized['image']]);
                }
            } else {
                $piece = array_merge($piece, $_POST);
            }
        }

        require __DIR__ . '/../views/back/piece_edit.php';
    }

    public function confirmDeletePiece()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $piece = $this->getPieceById($id);

        if (!$piece) {
            header('Location: index.php?action=managePieces&error=' . rawurlencode('Piece introuvable'));
            exit;
        }

        require __DIR__ . '/../views/back/piece_delete.php';
    }

    public function viewPiece()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $piece = $this->getPieceById($id);

        if (!$piece) {
            header('Location: index.php?action=managePieces&error=' . rawurlencode('Piece introuvable'));
            exit;
        }

        require __DIR__ . '/../views/back/piece_view.php';
    }

    public function deletePiece()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $piece = $this->getPieceById($id);

        if ($id > 0 && $piece && $this->deletePieceById($id)) {
            $this->deleteImageFile(isset($piece['image']) ? $piece['image'] : null);
            header('Location: index.php?action=managePieces&success=' . rawurlencode('Piece supprimee avec succes'));
        } else {
            header('Location: index.php?action=managePieces&error=' . rawurlencode('Erreur lors de la suppression'));
        }
        exit;
    }

    private function getDemandesPiece()
    {
        $filePath = __DIR__ . '/../database/demandes_piece.json';
        if (!is_file($filePath) || !is_readable($filePath)) {
            return [];
        }

        $raw = file_get_contents($filePath);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        usort($decoded, function ($a, $b) {
            $dateA = isset($a['date_demande']) ? strtotime((string) $a['date_demande']) : 0;
            $dateB = isset($b['date_demande']) ? strtotime((string) $b['date_demande']) : 0;
            return $dateB <=> $dateA;
        });

        return $decoded;
    }

    private function getAllPieces()
    {
        $stmt = $this->conn->query('SELECT * FROM pieces ORDER BY date_ajout DESC, id_piece DESC');
        return $stmt->fetchAll();
    }

    private function getPaginatedPieces($page, $perPage, array $filters)
    {
        [$whereSql, $params] = $this->buildPieceFilters($filters);

        $countStmt = $this->conn->prepare('SELECT COUNT(*) FROM pieces' . $whereSql);
        $countStmt->execute($params);
        $totalItems = (int) $countStmt->fetchColumn();

        $pagination = $this->buildPagination($page, $perPage, $totalItems);

        $sql = 'SELECT * FROM pieces'
            . $whereSql
            . ' ORDER BY 
                CASE 
                    WHEN quantite_stock <= 0 THEN 1 
                    WHEN quantite_stock <= seuil_alerte THEN 2 
                    ELSE 3 
                END ASC, 
                date_ajout DESC, id_piece DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'];

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'pagination' => $pagination,
        ];
    }

    private function buildPieceFilters(array $filters)
    {
        $conditions = [];
        $params = [];
        $search = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $categorie = isset($filters['categorie']) ? trim((string) $filters['categorie']) : '';
        $stock = isset($filters['stock']) ? trim((string) $filters['stock']) : '';

        if ($search !== '') {
            $conditions[] = '(nom LIKE :q OR marque LIKE :q OR reference LIKE :q OR description LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }

        if ($categorie !== '') {
            $conditions[] = 'categorie = :categorie';
            $params[':categorie'] = $categorie;
        }

        if ($stock === 'in-stock') {
            $conditions[] = 'quantite_stock > seuil_alerte';
        } elseif ($stock === 'low-stock') {
            $conditions[] = 'quantite_stock > 0 AND quantite_stock <= seuil_alerte';
        } elseif ($stock === 'out-of-stock') {
            $conditions[] = 'quantite_stock <= 0';
        }

        $whereSql = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$whereSql, $params];
    }

    private function getPieceCategories()
    {
        $stmt = $this->conn->query('SELECT DISTINCT categorie FROM pieces WHERE categorie IS NOT NULL AND categorie <> \'\' ORDER BY categorie ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getPieceById($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM pieces WHERE id_piece = :id_piece');
        $stmt->execute([':id_piece' => (int) $id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    private function insertPiece(array $data)
    {
        $sql = 'INSERT INTO pieces (reference, nom, description, categorie, marque, prix_unitaire, quantite_stock, seuil_alerte, image)
                VALUES (:reference, :nom, :description, :categorie, :marque, :prix_unitaire, :quantite_stock, :seuil_alerte, :image)';

        $stmt = $this->conn->prepare($sql);
        $saved = $stmt->execute([
            ':reference' => $data['reference'],
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':categorie' => $data['categorie'],
            ':marque' => $data['marque'],
            ':prix_unitaire' => $data['prix_unitaire'],
            ':quantite_stock' => $data['quantite_stock'],
            ':seuil_alerte' => $data['seuil_alerte'],
            ':image' => isset($data['image']) ? $data['image'] : null,
        ]);

        if (!$saved) {
            return false;
        }

        return (int) $this->conn->lastInsertId();
    }

    private function updatePieceById($id, array $data)
    {
        $sql = 'UPDATE pieces
                SET reference = :reference,
                    nom = :nom,
                    description = :description,
                    categorie = :categorie,
                    marque = :marque,
                    prix_unitaire = :prix_unitaire,
                    quantite_stock = :quantite_stock,
                    seuil_alerte = :seuil_alerte,
                    image = :image
                WHERE id_piece = :id_piece';

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':reference' => $data['reference'],
            ':nom' => $data['nom'],
            ':description' => $data['description'],
            ':categorie' => $data['categorie'],
            ':marque' => $data['marque'],
            ':prix_unitaire' => $data['prix_unitaire'],
            ':quantite_stock' => $data['quantite_stock'],
            ':seuil_alerte' => $data['seuil_alerte'],
            ':image' => isset($data['image']) ? $data['image'] : null,
            ':id_piece' => (int) $id,
        ]);
    }

    private function deletePieceById($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM pieces WHERE id_piece = :id_piece');
        return $stmt->execute([':id_piece' => (int) $id]);
    }

    private function getAllCommandes()
    {
        $sql = 'SELECT c.*, p.nom AS piece_nom, p.reference AS piece_reference
                FROM commandes c
                INNER JOIN pieces p ON p.id_piece = c.id_piece
                ORDER BY c.date_commande DESC, c.id_commande DESC';

        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    private function buildPagination($page, $perPage, $totalItems)
    {
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $page), $totalPages);

        return [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'offset' => ($currentPage - 1) * $perPage,
            'from' => $totalItems === 0 ? 0 : (($currentPage - 1) * $perPage) + 1,
            'to' => min($totalItems, $currentPage * $perPage),
        ];
    }

    private function getPageNumber()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        return $page > 0 ? $page : 1;
    }

    private function handleUploadedPieceImage($file, $reference, array &$errors, $existingImage = null)
    {
        if (!is_array($file) || !isset($file['error'])) {
            return $existingImage;
        }

        if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return $existingImage;
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Impossible de televerser l\'image selectionnee.';
            return $existingImage;
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Le fichier image recu est invalide.';
            return $existingImage;
        }

        if ((int) $file['size'] > 4 * 1024 * 1024) {
            $errors[] = 'L\'image ne doit pas depasser 4 Mo.';
            return $existingImage;
        }

        $mimeType = $this->detectMimeType($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            $errors[] = 'Formats autorises: JPG, PNG, GIF ou WEBP.';
            return $existingImage;
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0775, true) && !is_dir($this->uploadDir)) {
            $errors[] = 'Le dossier de destination des images est inaccessible.';
            return $existingImage;
        }

        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $reference));
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'piece';
        }

        $filename = $slug . '-' . uniqid('', true) . '.' . $extensions[$mimeType];
        $targetPath = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = 'Le stockage de l\'image a echoue.';
            return $existingImage;
        }

        if ($existingImage !== null && $existingImage !== '') {
            $this->deleteImageFile($existingImage);
        }

        return $this->uploadWebPath . '/' . $filename;
    }

    private function detectMimeType($filePath)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if (is_string($mimeType) && $mimeType !== '') {
                    return $mimeType;
                }
            }
        }

        $imageInfo = @getimagesize($filePath);
        return isset($imageInfo['mime']) ? $imageInfo['mime'] : '';
    }

    private function deleteImageFile($imagePath)
    {
        if (!is_string($imagePath) || $imagePath === '') {
            return;
        }

        $basename = basename($imagePath);
        $absolutePath = $this->uploadDir . '/' . $basename;

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function ensurePieceImageColumn()
    {
        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM pieces LIKE 'image'");
            $column = $stmt->fetch();

            if (!$column) {
                $this->conn->exec('ALTER TABLE pieces ADD COLUMN image VARCHAR(255) NULL AFTER seuil_alerte');
            }
        } catch (Throwable $e) {
            // Ignore schema checks on environments where ALTER is not allowed.
        }
    }

    private function forcePieceImageUpdate($pieceId, $imagePath)
    {
        $stmt = $this->conn->prepare('UPDATE pieces SET image = :image WHERE id_piece = :id_piece');
        $stmt->execute([
            ':image' => $imagePath !== null ? (string) $imagePath : null,
            ':id_piece' => (int) $pieceId,
        ]);
    }

    private function syncStoredPieceImages()
    {
        if (!is_dir($this->uploadDir)) {
            return;
        }

        try {
            $stmt = $this->conn->query('SELECT id_piece, reference, image FROM pieces');
            $pieces = $stmt->fetchAll();

            foreach ($pieces as $piece) {
                $imagePath = isset($piece['image']) ? (string) $piece['image'] : '';
                if ($imagePath !== '') {
                    continue;
                }

                $matchedImage = $this->findUploadedImageForReference((string) $piece['reference']);
                if ($matchedImage !== null) {
                    $this->forcePieceImageUpdate((int) $piece['id_piece'], $matchedImage);
                }
            }
        } catch (Throwable $e) {
            // Ignore sync issues to keep the application available.
        }
    }

    private function findUploadedImageForReference($reference)
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $reference));
        $slug = trim($slug, '-');
        if ($slug === '') {
            return null;
        }

        $matches = glob($this->uploadDir . '/' . $slug . '-*.*');
        if (!is_array($matches) || empty($matches)) {
            return null;
        }

        usort($matches, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        return $this->uploadWebPath . '/' . basename($matches[0]);
    }
}
