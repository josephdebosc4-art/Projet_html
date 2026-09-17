<?php
$informations = isset($_GET['informations']) ? $_GET['informations'] : '';

switch ($informations) {
    case '1':
        header('Location: apprenti.html');
        exit;

    case '2':
        header('Location: tuteur_ecole.html');
        exit;

    case '3':
        header('Location: tuteur_entreprise.html');
        exit;

    default:
        echo '<p>Veuillez sélectionner un rôle valide pour continuer.</p>';
        echo '<p><a href="index.html">Retour</a></p>';
        exit;
}
