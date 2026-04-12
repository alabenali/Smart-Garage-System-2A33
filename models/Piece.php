<?php
// ============================================
// Piece Model (PDO)
// ============================================

require_once __DIR__ . '/../config/Database.php';

class Piece {

    private $conn;

    private $id_piece;
    private $reference;
    private $nom;
    private $description;
    private $categorie;
    private $marque;
    private $prix_unitaire;
    private $quantite_stock;
    private $seuil_alerte;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function setIdPiece($id) {
        $this->id_piece = (int)$id;
    }

    public function setReference($v) {
        $this->reference = $v;
    }

    public function setNom($v) {
        $this->nom = $v;
    }

    public function setDescription($v) {
        $this->description = $v;
    }

    public function setCategorie($v) {
        $this->categorie = $v;
    }

    public function setMarque($v) {
        $this->marque = $v;
    }

    public function setPrixUnitaire($v) {
        $this->prix_unitaire = $v;
    }

    public function setQuantiteStock($v) {
        $this->quantite_stock = (int)$v;
    }

    public function setSeuilAlerte($v) {
        $this->seuil_alerte = (int)$v;
    }

    public function getAll() {
        $stmt = $this->conn->query(
            'SELECT * FROM pieces ORDER BY date_ajout DESC, id_piece DESC'
        );
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare('SELECT * FROM pieces WHERE id_piece = ?');
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch();
        return $row ?: false;
    }

    public function insert() {
        $sql = 'INSERT INTO pieces (reference, nom, description, categorie, marque, prix_unitaire, quantite_stock, seuil_alerte)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $this->reference,
            $this->nom,
            $this->description,
            $this->categorie,
            $this->marque,
            $this->prix_unitaire,
            $this->quantite_stock,
            $this->seuil_alerte,
        ]);
    }

    public function update() {
        $sql = 'UPDATE pieces SET reference = ?, nom = ?, description = ?, categorie = ?, marque = ?,
                prix_unitaire = ?, quantite_stock = ?, seuil_alerte = ?
                WHERE id_piece = ?';
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $this->reference,
            $this->nom,
            $this->description,
            $this->categorie,
            $this->marque,
            $this->prix_unitaire,
            $this->quantite_stock,
            $this->seuil_alerte,
            $this->id_piece,
        ]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare('DELETE FROM pieces WHERE id_piece = ?');
        return $stmt->execute([(int)$id]);
    }

    /**
     * Creates an order and decrements stock in a transaction.
     */
    public function createCommande(array $data) {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare(
                'SELECT quantite_stock FROM pieces WHERE id_piece = ? FOR UPDATE'
            );
            $stmt->execute([(int)$data['id_piece']]);
            $row = $stmt->fetch();
            if (!$row || (int)$row['quantite_stock'] < (int)$data['quantite']) {
                $this->conn->rollBack();
                return false;
            }

            $ins = $this->conn->prepare(
                'INSERT INTO commandes (id_piece, nom_client, prenom_client, telephone, quantite, montant_total)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                (int)$data['id_piece'],
                $data['nom_client'],
                $data['prenom_client'],
                $data['telephone'],
                (int)$data['quantite'],
                $data['montant_total'],
            ]);

            $upd = $this->conn->prepare(
                'UPDATE pieces SET quantite_stock = quantite_stock - ? WHERE id_piece = ?'
            );
            $upd->execute([(int)$data['quantite'], (int)$data['id_piece']]);

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function getAllCommandes() {
        $sql = 'SELECT c.*, p.nom AS piece_nom, p.reference AS piece_reference
                FROM commandes c
                INNER JOIN pieces p ON c.id_piece = p.id_piece
                ORDER BY c.date_commande DESC, c.id_commande DESC';
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function deleteCommande($id) {
        $stmt = $this->conn->prepare('DELETE FROM commandes WHERE id_commande = ?');
        return $stmt->execute([(int)$id]);
    }
}
