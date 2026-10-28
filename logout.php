<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!is_post()) {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
verify_csrf();
$_SESSION = [];
session_destroy();
session_start();
session_regenerate_id(true);
flash('success', 'Vous êtes déconnecté.');
redirect('index.php');
