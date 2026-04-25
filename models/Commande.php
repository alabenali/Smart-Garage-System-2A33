<?php

class Commande
{
    public $id_commande;
    public $id_piece;
    public $nom_client;
    public $prenom_client;
    public $telephone;
    public $quantite;
    public $montant_total;
    public $statut;
    public $date_commande;
    public $piece_nom;
    public $piece_reference;

    public function __construct(array $data = [])
    {
        $this->id_commande = $data['id_commande'] ?? null;
        $this->id_piece = $data['id_piece'] ?? null;
        $this->nom_client = $data['nom_client'] ?? '';
        $this->prenom_client = $data['prenom_client'] ?? '';
        $this->telephone = $data['telephone'] ?? '';
        $this->quantite = $data['quantite'] ?? 0;
        $this->montant_total = $data['montant_total'] ?? 0;
        $this->statut = $data['statut'] ?? '';
        $this->date_commande = $data['date_commande'] ?? null;
        $this->piece_nom = $data['piece_nom'] ?? null;
        $this->piece_reference = $data['piece_reference'] ?? null;
    }

    public function toArray()
    {
        return [
            'id_commande' => $this->id_commande,
            'id_piece' => $this->id_piece,
            'nom_client' => $this->nom_client,
            'prenom_client' => $this->prenom_client,
            'telephone' => $this->telephone,
            'quantite' => $this->quantite,
            'montant_total' => $this->montant_total,
            'statut' => $this->statut,
            'date_commande' => $this->date_commande,
            'piece_nom' => $this->piece_nom,
            'piece_reference' => $this->piece_reference,
        ];
    }
}
