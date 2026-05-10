<?php

class ValidationHelper
{
    private static function cleanText($value)
    {
        return htmlspecialchars(strip_tags(trim((string) $value)));
    }

    public static function validatePiece($data)
    {
        $errors = [];

        $reference = isset($data['reference']) ? self::cleanText($data['reference']) : '';
        $nom = isset($data['nom']) ? self::cleanText($data['nom']) : '';
        $description = isset($data['description']) ? self::cleanText($data['description']) : '';
        $categorie = isset($data['categorie']) ? self::cleanText($data['categorie']) : '';
        $marque = isset($data['marque']) ? self::cleanText($data['marque']) : '';
        $prixUnitaireRaw = isset($data['prix_unitaire']) ? trim((string) $data['prix_unitaire']) : '';
        $quantiteStockRaw = isset($data['quantite_stock']) ? trim((string) $data['quantite_stock']) : '';
        $seuilAlerteRaw = isset($data['seuil_alerte']) ? trim((string) $data['seuil_alerte']) : '';

        if ($reference === '') {
            $errors[] = 'La référence est obligatoire.';
        } elseif (!preg_match('/^[A-Za-z0-9\-]{3,50}$/', $reference)) {
            $errors[] = 'La référence doit contenir 3 à 50 caractères (lettres, chiffres, tirets).';
        }

        if ($nom === '') {
            $errors[] = 'Le nom de la pièce est obligatoire.';
        } elseif (mb_strlen($nom) < 2 || mb_strlen($nom) > 150) {
            $errors[] = 'Le nom doit contenir entre 2 et 150 caractères.';
        }

        if ($categorie === '') {
            $errors[] = 'La catégorie est obligatoire.';
        }

        if ($marque === '') {
            $errors[] = 'La marque est obligatoire.';
        }

        if ($prixUnitaireRaw === '') {
            $errors[] = 'Le prix unitaire est obligatoire.';
        } elseif (!is_numeric($prixUnitaireRaw)) {
            $errors[] = 'Le prix unitaire doit être un nombre.';
        } else {
            $prix = (float) $prixUnitaireRaw;
            if ($prix <= 0) {
                $errors[] = 'Le prix unitaire doit être supérieur à 0.';
            }
            if ($prix > 99999.99) {
                $errors[] = 'Le prix unitaire ne peut pas dépasser 99 999,99 DT.';
            }
        }

        if ($quantiteStockRaw === '') {
            $errors[] = 'La quantité en stock est obligatoire.';
        } elseif (!ctype_digit($quantiteStockRaw)) {
            $errors[] = 'La quantité en stock doit être un entier positif.';
        }

        if ($seuilAlerteRaw === '') {
            $errors[] = 'Le seuil d\'alerte est obligatoire.';
        } elseif (!ctype_digit($seuilAlerteRaw)) {
            $errors[] = 'Le seuil d\'alerte doit être un entier positif.';
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'reference' => $reference,
                'nom' => $nom,
                'description' => $description,
                'categorie' => $categorie,
                'marque' => $marque,
                'prix_unitaire' => $prixUnitaireRaw === '' ? 0 : (float) $prixUnitaireRaw,
                'quantite_stock' => $quantiteStockRaw === '' ? 0 : (int) $quantiteStockRaw,
                'seuil_alerte' => $seuilAlerteRaw === '' ? 0 : (int) $seuilAlerteRaw,
                'image' => isset($data['image']) ? self::cleanText($data['image']) : null,
            ],
        ];
    }

    public static function validateCommande($data)
    {
        $errors = [];

        $nomClient = isset($data['nom_client']) ? self::cleanText($data['nom_client']) : '';
        $prenomClient = isset($data['prenom_client']) ? self::cleanText($data['prenom_client']) : '';
        $telephone = isset($data['telephone']) ? self::cleanText($data['telephone']) : '';
        $idPieceRaw = isset($data['id_piece']) ? trim((string) $data['id_piece']) : '';
        $quantiteRaw = isset($data['quantite']) ? trim((string) $data['quantite']) : '';

        if ($nomClient === '') {
            $errors[] = 'Le nom est obligatoire.';
        } elseif (mb_strlen($nomClient) < 2 || mb_strlen($nomClient) > 150) {
            $errors[] = 'Le nom doit contenir entre 2 et 150 caractères.';
        }

        if ($prenomClient === '') {
            $errors[] = 'Le prénom est obligatoire.';
        } elseif (mb_strlen($prenomClient) < 2 || mb_strlen($prenomClient) > 150) {
            $errors[] = 'Le prénom doit contenir entre 2 et 150 caractères.';
        }

        if ($telephone === '') {
            $errors[] = 'Le téléphone est obligatoire.';
        } elseif (!preg_match('/^[\d\s\+\-]{8,20}$/', $telephone)) {
            $errors[] = 'Le numéro de téléphone doit contenir 8 à 20 caractères (chiffres, espaces, +, -).';
        }

        if ($idPieceRaw === '') {
            $errors[] = 'Veuillez sélectionner une pièce.';
        } elseif (!ctype_digit($idPieceRaw)) {
            $errors[] = 'La pièce sélectionnée est invalide.';
        }

        if ($quantiteRaw === '') {
            $errors[] = 'La quantité est obligatoire.';
        } elseif (!ctype_digit($quantiteRaw)) {
            $errors[] = 'La quantité doit être un nombre entier positif.';
        } else {
            $quantite = (int) $quantiteRaw;
            if ($quantite < 1) {
                $errors[] = 'La quantité doit être au moins 1.';
            }
            if ($quantite > 999) {
                $errors[] = 'La quantité ne peut pas dépasser 999.';
            }
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'id_piece' => ctype_digit($idPieceRaw) ? (int) $idPieceRaw : 0,
                'nom_client' => $nomClient,
                'prenom_client' => $prenomClient,
                'telephone' => $telephone,
                'quantite' => ctype_digit($quantiteRaw) ? (int) $quantiteRaw : 0,
            ],
        ];
    }

    public static function validateDemandePiece($data)
    {
        $errors = [];

        $nomClient = isset($data['nom_client']) ? self::cleanText($data['nom_client']) : '';
        $prenomClient = isset($data['prenom_client']) ? self::cleanText($data['prenom_client']) : '';
        $telephone = isset($data['telephone']) ? self::cleanText($data['telephone']) : '';
        $nomPiece = isset($data['nom_piece']) ? self::cleanText($data['nom_piece']) : '';
        $marque = isset($data['marque']) ? self::cleanText($data['marque']) : '';
        $description = isset($data['description']) ? self::cleanText($data['description']) : '';
        $quantiteRaw = isset($data['quantite']) ? trim((string) $data['quantite']) : '1';

        if ($nomClient === '') {
            $errors[] = 'Le nom est obligatoire.';
        } elseif (mb_strlen($nomClient) < 2 || mb_strlen($nomClient) > 150) {
            $errors[] = 'Le nom doit contenir entre 2 et 150 caractères.';
        }

        if ($prenomClient === '') {
            $errors[] = 'Le prénom est obligatoire.';
        } elseif (mb_strlen($prenomClient) < 2 || mb_strlen($prenomClient) > 150) {
            $errors[] = 'Le prénom doit contenir entre 2 et 150 caractères.';
        }

        if ($telephone === '') {
            $errors[] = 'Le téléphone est obligatoire.';
        } elseif (!preg_match('/^[\d\s\+\-]{8,20}$/', $telephone)) {
            $errors[] = 'Le numéro de téléphone doit contenir 8 à 20 caractères (chiffres, espaces, +, -).';
        }

        if ($nomPiece === '') {
            $errors[] = 'Le nom de la pièce demandée est obligatoire.';
        } elseif (mb_strlen($nomPiece) < 2 || mb_strlen($nomPiece) > 180) {
            $errors[] = 'Le nom de la pièce doit contenir entre 2 et 180 caractères.';
        }

        if ($quantiteRaw === '') {
            $errors[] = 'La quantité est obligatoire.';
        } elseif (!ctype_digit($quantiteRaw)) {
            $errors[] = 'La quantité doit être un nombre entier positif.';
        } else {
            $quantite = (int) $quantiteRaw;
            if ($quantite < 1 || $quantite > 999) {
                $errors[] = 'La quantité doit être comprise entre 1 et 999.';
            }
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'nom_client' => $nomClient,
                'prenom_client' => $prenomClient,
                'telephone' => $telephone,
                'nom_piece' => $nomPiece,
                'marque' => $marque,
                'description' => $description,
                'quantite' => ctype_digit($quantiteRaw) ? (int) $quantiteRaw : 1,
            ],
        ];
    }
}
