<?php
$pays = isset($_GET['pays']) ? $_GET['pays'] : '';
if ($pays === '1) {
  header('Location: apprenti.html');
  exit;
} else {
  echo '<p>Vous devez sélectionner "Apprenti" pour continuer.</p>';
  echo '<p><a href="index.html">Retour</a></p>';
}

if ($pays === '2') {
  header('Location: tuteur_ecole.html');
  exit;
} else {
  echo '<p>Vous devez sélectionner "Tuteur école" pour continuer.</p>';
  echo '<p><a href="index.html">Retour</a></p>';
}

if ($pays === '3') {
  header('Location: tuteur_entreprise.html');
  exit;
} else {
  echo '<p>Vous devez sélectionner "Tuteur entreprise" pour continuer.</p>';
  echo '<p><a href="index.html">Retour</a></p>';
}