<?php
// ============================================
// Vehicle Controller
// ============================================

require_once __DIR__ . '/../models/Vehicle.php';

class VehicleController {

    private $vehicleModel;

    public function __construct() {
        $this->vehicleModel = new Vehicle();
    }

    // -------------------------------------------------------
    // PHP-side validation (sanitize + check empty + numeric)
    // -------------------------------------------------------
    private function validateInput($data) {
        $errors = [];

        // Sanitize all inputs
        $marque         = htmlspecialchars(strip_tags(trim($data['marque'] ?? '')));
        $modele         = htmlspecialchars(strip_tags(trim($data['modele'] ?? '')));
        $immatriculation = htmlspecialchars(strip_tags(trim($data['immatriculation'] ?? '')));
        $couleur        = htmlspecialchars(strip_tags(trim($data['couleur'] ?? '')));
        $annee          = trim($data['annee'] ?? '');
        $kilometrage    = trim($data['kilometrage'] ?? '');
        $carburant      = htmlspecialchars(strip_tags(trim($data['carburant'] ?? '')));

        // Check empty values
        if (empty($marque))         $errors[] = "La marque est obligatoire.";
        if (empty($modele))         $errors[] = "Le modèle est obligatoire.";
        if (empty($immatriculation)) $errors[] = "L'immatriculation est obligatoire.";
        if (empty($couleur))        $errors[] = "La couleur est obligatoire.";
        if (empty($annee))          $errors[] = "L'année est obligatoire.";
        if (empty($kilometrage) && $kilometrage !== '0') $errors[] = "Le kilométrage est obligatoire.";
        if (empty($carburant))      $errors[] = "Le type de carburant est obligatoire.";

        // Validate numeric fields
        if (!empty($annee)) {
            if (!is_numeric($annee)) {
                $errors[] = "L'année doit être un nombre.";
            } else {
                $annee = (int)$annee;
                $currentYear = (int)date('Y');
                if ($annee < 1990 || $annee > $currentYear) {
                    $errors[] = "L'année doit être entre 1990 et {$currentYear}.";
                }
            }
        }

        if ($kilometrage !== '' && !is_numeric($kilometrage)) {
            $errors[] = "Le kilométrage doit être un nombre.";
        } elseif ($kilometrage !== '' && (int)$kilometrage < 0) {
            $errors[] = "Le kilométrage doit être positif.";
        }

        // Validate immatriculation format (Tunisian format: 123 TU 4567)
        if (!empty($immatriculation) && !preg_match('/^\d{1,4}\s?[A-Za-z]{1,4}\s?\d{1,4}$/', $immatriculation)) {
            $errors[] = "Le format de l'immatriculation est invalide (ex: 123 TU 4567).";
        }

        return [
            'errors' => $errors,
            'sanitized' => [
                'marque'         => $marque,
                'modele'         => $modele,
                'immatriculation' => $immatriculation,
                'couleur'        => $couleur,
                'annee'          => (int)$annee,
                'kilometrage'    => (int)$kilometrage,
                'carburant'      => $carburant,
            ]
        ];
    }

    // -------------------------------------------------------
    // Add Vehicle (Front Office)
    // -------------------------------------------------------
    public function addVehicle() {
        $errors = [];
        $success = '';
        $old = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];
            $old = $_POST;

            if (empty($errors)) {
                $d = $validation['sanitized'];
                $this->vehicleModel->setMarque($d['marque']);
                $this->vehicleModel->setModele($d['modele']);
                $this->vehicleModel->setImmatriculation($d['immatriculation']);
                $this->vehicleModel->setCouleur($d['couleur']);
                $this->vehicleModel->setAnnee($d['annee']);
                $this->vehicleModel->setKilometrage($d['kilometrage']);
                $this->vehicleModel->setCarburant($d['carburant']);

                if ($this->vehicleModel->add()) {
                    $success = "Véhicule ajouté avec succès !";
                    $old = []; // clear form
                } else {
                    $errors[] = "Erreur lors de l'ajout du véhicule.";
                }
            }
        }

        require __DIR__ . '/../views/front/vehicle_add.php';
    }

    // -------------------------------------------------------
    // Show all vehicles (Front Office list)
    // -------------------------------------------------------
    public function showVehicles() {
        $vehicles = $this->vehicleModel->list();
        require __DIR__ . '/../views/front/vehicle_list.php';
    }

    // -------------------------------------------------------
    // Show single vehicle (Back Office – for edit form)
    // -------------------------------------------------------
    public function showVehicle() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $vehicle = $this->vehicleModel->find($id);

        if (!$vehicle) {
            header('Location: index.php?action=manageVehicles&error=Véhicule introuvable');
            exit;
        }

        return $vehicle;
    }

    // -------------------------------------------------------
    // Update Vehicle (Back Office)
    // -------------------------------------------------------
    public function updateVehicle() {
        $errors = [];
        $success = '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $vehicle = $this->vehicleModel->find($id);

        if (!$vehicle) {
            header('Location: index.php?action=manageVehicles&error=Véhicule introuvable');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validation = $this->validateInput($_POST);
            $errors = $validation['errors'];

            if (empty($errors)) {
                $d = $validation['sanitized'];
                $this->vehicleModel->setId($id);
                $this->vehicleModel->setMarque($d['marque']);
                $this->vehicleModel->setModele($d['modele']);
                $this->vehicleModel->setImmatriculation($d['immatriculation']);
                $this->vehicleModel->setCouleur($d['couleur']);
                $this->vehicleModel->setAnnee($d['annee']);
                $this->vehicleModel->setKilometrage($d['kilometrage']);
                $this->vehicleModel->setCarburant($d['carburant']);

                if ($this->vehicleModel->update()) {
                    $success = "Véhicule mis à jour avec succès !";
                    $vehicle = $this->vehicleModel->find($id); // refresh data
                } else {
                    $errors[] = "Erreur lors de la mise à jour.";
                }
            } else {
                // Keep POST data on the form when validation failed
                $vehicle = array_merge($vehicle, $_POST);
            }
        }

        require __DIR__ . '/../views/back/vehicle_edit.php';
    }

    // -------------------------------------------------------
    // Delete Vehicle (Back Office)
    // -------------------------------------------------------
    public function deleteVehicle() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0 && $this->vehicleModel->delete($id)) {
            header('Location: index.php?action=manageVehicles&success=Véhicule supprimé avec succès');
        } else {
            header('Location: index.php?action=manageVehicles&error=Erreur lors de la suppression');
        }
        exit;
    }

    // -------------------------------------------------------
    // Back Office – vehicle management list
    // -------------------------------------------------------
    public function manageVehicles() {
        $vehicles = $this->vehicleModel->list();
        $success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
        $error   = isset($_GET['error'])   ? htmlspecialchars($_GET['error'])   : '';
        require __DIR__ . '/../views/back/vehicle_list.php';
    }

    // -------------------------------------------------------
    // Dashboard (Back Office)
    // -------------------------------------------------------
    public function dashboard() {
        $vehicles = $this->vehicleModel->list();
        $totalVehicles = count($vehicles);

        // Stats for dashboard cards
        $totalKm = 0;
        $fuelStats = [];
        $brandStats = [];
        foreach ($vehicles as $v) {
            $totalKm += $v['kilometrage'];
            $fuel = $v['carburant'];
            $brand = $v['marque'];
            $fuelStats[$fuel]  = ($fuelStats[$fuel] ?? 0) + 1;
            $brandStats[$brand] = ($brandStats[$brand] ?? 0) + 1;
        }
        $avgKm = $totalVehicles > 0 ? round($totalKm / $totalVehicles) : 0;

        require __DIR__ . '/../views/back/dashboard.php';
    }
}
