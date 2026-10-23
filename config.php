<?php
declare(strict_types=1);

// Les valeurs par défaut conviennent à une installation locale classique.
// Sur le serveur, il suffit de définir les variables APP_DB_* et APP_BASE_URL.
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$sessionPath = __DIR__ . '/runtime/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
if (is_dir($sessionPath)) {
    session_save_path($sessionPath);
}
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secureCookie,
    'samesite' => 'Lax',
]);
session_start();

const MAX_PDF_SIZE = 5 * 1024 * 1024;
const MAX_PDF_FILES = 5;
const UPLOAD_DIR = __DIR__ . '/uploads';

$baseUrl = getenv('APP_BASE_URL') ?: '';
define('BASE_URL', rtrim($baseUrl, '/'));

$dbHost = getenv('APP_DB_HOST') ?: 'localhost';
$dbPort = getenv('APP_DB_PORT') ?: '3306';
$dbName = getenv('APP_DB_NAME') ?: 'suivi_apprentissage';
$dbUser = getenv('APP_DB_USER') ?: 'root';
$dbPassword = getenv('APP_DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Connexion à la base de données impossible. Vérifiez la configuration.');
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}
