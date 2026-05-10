<?php

declare(strict_types=1);

$innerDirName = 'samrt nour';
$innerIndexRel = rawurlencode($innerDirName) . '/index.php';

$go = isset($_GET['go']) ? (string) $_GET['go'] : '';
if ($go === 'samrt_nour') {
    header('Location: ' . $innerIndexRel);
    exit;
}

$links = [
    'Pièces & commandes (FrontOffice)' => 'frontoffice.php',
    'Pièces & commandes (BackOffice)' => 'backoffice.php',
    'Ouvrir Samrt Nour (chemin interne)' => $innerIndexRel,
    'Client (frontoffice)' => '../client/controllers/UserController.php?action=showLogin',
    'Client (backoffice)' => '../client/controllers/AdminController.php?action=showLogin',
    'Véhicule & RDV' => '../vehicule%20et%20rdv/index.php',
    'Diagnostic (frontoffice)' => '../diagnostic/frontoffice.php',
    'Diagnostic (backoffice)'  => '../diagnostic/backoffice.php',
    'Status (JSON)' => 'api/status.php',
];

?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>samrtnour - intégration</title>
</head>
<body>
    <h1>samrtnour</h1>
    <p>Point d’entrée “sans risque” vers les modules existants.</p>
    <ul>
        <?php foreach ($links as $label => $href): ?>
            <li><a href="<?php echo htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
