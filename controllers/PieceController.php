<?php
// ============================================
// Piece Controller
// Module: Pièces & Commandes
// ============================================

require_once __DIR__ . '/../models/Piece.php';

class PieceController {

    private $pieceModel;

    public function __construct() {
        $this->pieceModel = new Piece();
    }

    // -------------------------------------------------------
    // PHP-side validation for PIECE (BackOffice add/edit)
    // -------------------------------------------------------
    private function validateInput($data) {
        $errors = [];

        // Sanitize all inputs
        $reference      = htmlspecialchars(strip_tags(trim($data['reference'] ?? '')));
        $nom            = htmlspecialchars(strip_tags(trim($data['nom'] ?? '')));
        $description    = htmlspecialchars(strip_tags(trim($data['description'] ?? '')));
        $categorie      = htmlspecialchars(strip_tags(trim($data['categorie'] ?? '')));
        $marque         = htmlspecialchars(strip_tags(trim($data['marque'] ?? '')));
        $prix_unitaire  = trim($data['prix_unitaire'] ?? '');
        $quantite_stock = trim($data['quantite_stock'] ?? '');
        $seuil_alerte   = trim($data['seuil_alerte'] ?? '');

        // Check empty values
        if (empty($reference))      $errors[] = "La référence est obligatoire.";
        if (empty($nom))            $errors[] = "Le nom de la pièce est obligatoire.";
        if (empty($categorie))      $errors[] = "La catégorie est obligatoire.";
        if (empty($marque))         $errors[] = "La marque est obligatoire.";
        if ($prix_unitaire === '')   $errors[] = "Le prix unitaire est obligatoire.";
        if ($quantite_stock === '')  $errors[] = "La quantité en stock est obligatoire.";
        if ($seuil_alerte === '')    $errors[] = "Le seuil d'alerte est obligatoire.";

        // Validate reference format (letters, numbers, hyphens – 3 to 50 chars)
        if (!empty($reference) && !preg_match('/^[A-Za-z0-9\-]{3,50}$/', $reference)) {
            $errors[] = "La référence doit contenir 3 à 50 caractères (lettres, chiffres, tirets).";
        }

        // Validate nom length (2-150 chars)
        if (!empty($nom) && (strlen($nom) < 2 || strlen($nom) > 150)) {
            $errors[] = "Le nom doit contenir entre 2 et 150 caractères.";
        }

        // Validate prix_unitaire (positive decimal)
        if ($prix_unitaire !== '') {
            if (!is_numeric($prix_unitaire)) {
                $errors[] = "Le prix unitaire doit être un nombre.";
            } elseif ((float)$prix_unitaire <= 0) {
                $errors[] = "Le prix unitaire doit être supérieur à 0.";
            } elseif ((float)$prix_unitaire > 99999.99) {
                $errors[] = "Le prix unitaire ne peut pas dépasser 99 999,99 DT.";
            }
        }

        // Validate quantite_stock (positive integer)
        if ($quantite_stock !== '') {
            if (!ctype_digit($quantite_stock) && $quantite_stock !== '0') {
                $errors[] = "La quantité en stock doit être un nombre entier positif.";
            } elseif ((int)$quantite_stock < 0) {
                $errors[] = "La quantité en stock ne peut pas être négative.";
            }
        }

        // Validate seuil_alerte (positive integer)
        if ($seuil_alerte !== '') {
            if (!ctype_digit($seuil_alerte) && $seuil_alerte !== '0') {
                $errors[] = "Le seuil d'alerte doit être un nombre entier positif.";
            } elseif ((int)$seuil_alerte < 0) {
                $errors[] = "Le seuil d'alerte ne peut pas être négatif.";
            }
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'reference'      => $reference,
                'nom'            => $nom,
                'description'    => $description,
                'categorie'      => $categorie,
                'marque'         => $marque,
                'prix_unitaire'  => (float)$prix_unitaire,
                'quantite_stock' => (int)$quantite_stock,
                'seuil_alerte'   => (int)$seuil_alerte,
            ]
        ];
    }

    // -------------------------------------------------------
    // PHP-side validation for ORDER (FrontOffice commande)
    // -------------------------------------------------------
    private function validateOrderInput($data) {
        $errors = [];

        $nom_client     = htmlspecialchars(strip_tags(trim($data['nom_client'] ?? '')));
        $prenom_client  = htmlspecialchars(strip_tags(trim($data['prenom_client'] ?? '')));
        $telephone      = htmlspecialchars(strip_tags(trim($data['telephone'] ?? '')));
        $id_piece       = trim($data['id_piece'] ?? '');
        $quantite       = trim($data['quantite'] ?? '');

        // Required fields
        if (empty($nom_client))     $errors[] = "Le nom est obligatoire.";
        if (empty($prenom_client))  $errors[] = "Le prénom est obligatoire.";
        if (empty($telephone))      $errors[] = "Le téléphone est obligatoire.";
        if ($id_piece === '')       $errors[] = "Veuillez sélectionner une pièce.";
        if ($quantite === '')       $errors[] = "La quantité est obligatoire.";

        // Validate nom length (2-150 chars)
        if (!empty($nom_client) && (strlen($nom_client) < 2 || strlen($nom_client) > 150)) {
            $errors[] = "Le nom doit contenir entre 2 et 150 caractères.";
        }

        // Validate prenom length (2-150 chars)
        if (!empty($prenom_client) && (strlen($prenom_client) < 2 || strlen($prenom_client) > 150)) {
            $errors[] = "Le prénom doit contenir entre 2 et 150 caractères.";
        }

        // Validate telephone format (digits, spaces, +, -, min 8 chars)
        if (!empty($telephone) && !preg_match('/^[\d\s\+\-]{8,20}$/', $telephone)) {
            $errors[] = "Le numéro de téléphone doit contenir 8 à 20 caractères (chiffres, espaces, +, -).";
        }

        // Validate quantite (positive integer >= 1)
        if ($quantite !== '') {
            if (!ctype_digit($quantite)) {
                $errors[] = "La quantité doit être un nombre entier positif.";
            } elseif ((int)$quantite < 1) {
                $errors[] = "La quantité doit être au moins 1.";
            } elseif ((int)$quantite > 999) {
                $errors[] = "La quantité ne peut pas dépasser 999.";
            }
        }

        // Validate that the piece exists and has enough stock
        if ($id_piece !== '' && ctype_digit($id_piece)) {
            $piece = $this->pieceModel->getById((int)$id_piece);
            if (!$piece) {
                $errors[] = "La pièce sélectionnée n'existe pas.";
            } elseif ($quantite !== '' && ctype_digit($quantite) && (int)$quantite > $piece['quantite_stock']) {
                $errors[] = "Stock insuffisant. Seulement " . $piece['quantite_stock'] . " unité(s) disponible(s).";
            }
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'id_piece'       => (int)$id_piece,
                'nom_client'     => $nom_client,
                'prenom_client'  => $prenom_client,
                'telephone'      => $telephone,
                'quantite'       => (int)$quantite,
            ]
        ];
    }

    // -------------------------------------------------------
    // FrontOffice – Catalogue des pièces disponibles
    // -------------------------------------------------------
    public function showCatalogue() {
        $pieces = $this->pieceModel->getAll();
        require __DIR__ . '/../views/front/piece_catalogue.php';
    }

    // -------------------------------------------------------
    // FrontOffice – Commander une pièce
    // -------------------------------------------------------
    public function orderPiece() {
        $errors = [];
        $success = '';
        $old = [];
        $pieces = $this->pieceModel->getAll();

        // Pre-select piece if coming from catalogue
        if (isset($_GET['id_piece'])) {
            $old['id_piece'] = (int)$_GET['id_piece'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateOrderInput($_POST);
            $errors = $validation['errors'];
            $old = $_POST;

            if (empty($errors)) {
                $d = $validation['sanitized'];

                // Get the piece to calculate total
                $piece = $this->pieceModel->getById($d['id_piece']);
                $montant_total = $piece['prix_unitaire'] * $d['quantite'];

                $orderData = [
                    'id_piece'       => $d['id_piece'],
                    'nom_client'     => $d['nom_client'],
                    'prenom_client'  => $d['prenom_client'],
                    'telephone'      => $d['telephone'],
                    'quantite'       => $d['quantite'],
                    'montant_total'  => $montant_total,
                ];

                try {
                    if ($this->pieceModel->createCommande($orderData)) {
                        $success = "Commande passée avec succès ! Montant total : " . number_format($montant_total, 2, ',', ' ') . " DT";
                        $old = []; // clear form
                    } else {
                        $errors[] = "Erreur lors de la commande.";
                    }
                } catch (PDOException $e) {
                    $errors[] = "Erreur lors de la commande : " . $e->getMessage();
                }
            }
        }

        require __DIR__ . '/../views/front/piece_order.php';
    }

    // -------------------------------------------------------
    // BackOffice – Dashboard Pièces
    // -------------------------------------------------------
    public function dashboard() {
        $pieces = $this->pieceModel->getAll();
        $totalPieces = count($pieces);

        // Stats for dashboard cards
        $totalStock = 0;
        $totalValue = 0;
        $alertCount = 0;
        $categoryStats = [];
        $brandStats = [];

        foreach ($pieces as $p) {
            $totalStock += $p['quantite_stock'];
            $totalValue += $p['prix_unitaire'] * $p['quantite_stock'];

            if ($p['quantite_stock'] <= $p['seuil_alerte']) {
                $alertCount++;
            }

            $cat = $p['categorie'];
            $brand = $p['marque'];
            $categoryStats[$cat] = ($categoryStats[$cat] ?? 0) + 1;
            $brandStats[$brand]  = ($brandStats[$brand] ?? 0) + 1;
        }

        // Commandes stats
        $commandes = $this->pieceModel->getAllCommandes();
        $totalCommandes = count($commandes);

        require __DIR__ . '/../views/back/dashboard.php';
    }

    // -------------------------------------------------------
    // BackOffice – Ajouter une pièce
    // -------------------------------------------------------
    public function addPiece() {
        $errors = [];
        $piece = [
            'reference'      => '',
            'nom'            => '',
            'description'    => '',
            'categorie'      => '',
            'marque'         => '',
            'prix_unitaire'  => '',
            'quantite_stock' => '',
            'seuil_alerte'   => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];

            if (empty($errors)) {
                $d = $validation['sanitized'];
                $this->pieceModel->setReference($d['reference']);
                $this->pieceModel->setNom($d['nom']);
                $this->pieceModel->setDescription($d['description']);
                $this->pieceModel->setCategorie($d['categorie']);
                $this->pieceModel->setMarque($d['marque']);
                $this->pieceModel->setPrixUnitaire($d['prix_unitaire']);
                $this->pieceModel->setQuantiteStock($d['quantite_stock']);
                $this->pieceModel->setSeuilAlerte($d['seuil_alerte']);

                try {
                    if ($this->pieceModel->insert()) {
                        header('Location: index.php?action=managePieces&success=' . rawurlencode('Pièce ajoutée avec succès'));
                        exit;
                    }
                    $errors[] = "Erreur lors de l'ajout.";
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors[] = "Cette référence existe déjà dans la base de données.";
                    } else {
                        $errors[] = "Erreur lors de l'ajout : " . $e->getMessage();
                    }
                }
            } else {
                $piece = array_merge($piece, $_POST);
            }
        }

        require __DIR__ . '/../views/back/piece_add.php';
    }

    // -------------------------------------------------------
    // BackOffice – Gestion des pièces (liste)
    // -------------------------------------------------------
    public function managePieces() {
        $pieces = $this->pieceModel->getAll();
        $success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
        $error   = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
        require __DIR__ . '/../views/back/piece_list.php';
    }

    // -------------------------------------------------------
    // BackOffice – Modifier une pièce
    // -------------------------------------------------------
    public function updatePiece() {
        $errors = [];
        $success = '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $piece = $this->pieceModel->getById($id);

        if (!$piece) {
            header('Location: index.php?action=managePieces&error=Pièce introuvable');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];

            if (empty($errors)) {
                $d = $validation['sanitized'];
                $this->pieceModel->setIdPiece($id);
                $this->pieceModel->setReference($d['reference']);
                $this->pieceModel->setNom($d['nom']);
                $this->pieceModel->setDescription($d['description']);
                $this->pieceModel->setCategorie($d['categorie']);
                $this->pieceModel->setMarque($d['marque']);
                $this->pieceModel->setPrixUnitaire($d['prix_unitaire']);
                $this->pieceModel->setQuantiteStock($d['quantite_stock']);
                $this->pieceModel->setSeuilAlerte($d['seuil_alerte']);

                try {
                    if ($this->pieceModel->update()) {
                        $success = "Pièce mise à jour avec succès !";
                        $piece = $this->pieceModel->getById($id); // refresh
                    } else {
                        $errors[] = "Erreur lors de la mise à jour.";
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors[] = "Cette référence existe déjà dans la base de données.";
                    } else {
                        $errors[] = "Erreur lors de la mise à jour : " . $e->getMessage();
                    }
                }
            } else {
                // Keep POST data on the form when validation failed
                $piece = array_merge($piece, $_POST);
            }
        }

        require __DIR__ . '/../views/back/piece_edit.php';
    }

    // -------------------------------------------------------
    // BackOffice – Supprimer une pièce (confirmation page)
    // -------------------------------------------------------
    public function confirmDeletePiece() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $piece = $this->pieceModel->getById($id);

        if (!$piece) {
            header('Location: index.php?action=managePieces&error=Pièce introuvable');
            exit;
        }

        require __DIR__ . '/../views/back/piece_delete.php';
    }

    // -------------------------------------------------------
    // BackOffice – Exécuter la suppression
    // -------------------------------------------------------
    public function deletePiece() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0 && $this->pieceModel->delete($id)) {
            header('Location: index.php?action=managePieces&success=Pièce supprimée avec succès');
        } else {
            header('Location: index.php?action=managePieces&error=Erreur lors de la suppression');
        }
        exit;
    }

    // -------------------------------------------------------
    // BackOffice – Gestion des commandes (liste)
    // -------------------------------------------------------
    public function manageCommandes() {
        $commandes = $this->pieceModel->getAllCommandes();
        $success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
        $error   = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
        require __DIR__ . '/../views/back/commande_list.php';
    }

    // -------------------------------------------------------
    // BackOffice – Supprimer une commande
    // -------------------------------------------------------
    public function deleteCommande() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0 && $this->pieceModel->deleteCommande($id)) {
            header('Location: index.php?action=manageCommandes&success=Commande supprimée avec succès');
        } else {
            header('Location: index.php?action=manageCommandes&error=Erreur lors de la suppression');
        }
        exit;
    }
}
