<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';
require_admin();

if (!is_post()) {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
verify_csrf();
$teamId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT apprentice_id FROM teams WHERE id = ?');
$stmt->execute([$teamId]);
$team = $stmt->fetch();
if (!$team) {
    http_response_code(404);
    exit('Équipe introuvable.');
}

$stmt = $pdo->prepare('SELECT attachment_path FROM messages WHERE team_id = ? AND attachment_path IS NOT NULL');
$stmt->execute([$teamId]);
$attachments = $stmt->fetchAll(PDO::FETCH_COLUMN);
