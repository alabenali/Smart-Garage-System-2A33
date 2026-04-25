<?php

class Piece
{
    public $id_piece;
    public $reference;
    public $nom;
    public $description;
    public $categorie;
    public $marque;
    public $prix_unitaire;
    public $quantite_stock;
    public $seuil_alerte;
    public $image;
    public $date_ajout;

    public function __construct(array $data = [])
    {
        $this->id_piece = $data['id_piece'] ?? null;
        $this->reference = $data['reference'] ?? '';
        $this->nom = $data['nom'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->categorie = $data['categorie'] ?? '';
        $this->marque = $data['marque'] ?? '';
        $this->prix_unitaire = $data['prix_unitaire'] ?? 0;
        $this->quantite_stock = $data['quantite_stock'] ?? 0;
        $this->seuil_alerte = $data['seuil_alerte'] ?? 0;
        $this->image = $data['image'] ?? null;
        $this->date_ajout = $data['date_ajout'] ?? null;
    }

    public function toArray()
    {
        return [
            'id_piece' => $this->id_piece,
            'reference' => $this->reference,
            'nom' => $this->nom,
            'description' => $this->description,
            'categorie' => $this->categorie,
            'marque' => $this->marque,
            'prix_unitaire' => $this->prix_unitaire,
            'quantite_stock' => $this->quantite_stock,
            'seuil_alerte' => $this->seuil_alerte,
            'image' => $this->image,
            'date_ajout' => $this->date_ajout,
        ];
    }
}
