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
$stmt = $pdo->prepare('SELECT ma.stored_name FROM message_attachments ma JOIN messages m ON m.id = ma.message_id WHERE m.team_id = ?');
$stmt->execute([$teamId]);
$attachments = array_unique([...$attachments, ...$stmt->fetchAll(PDO::FETCH_COLUMN)]);

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('DELETE FROM teams WHERE id = ?');
    $stmt->execute([$teamId]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'apprentice'");
    $stmt->execute([(int) $team['apprentice_id']]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}

foreach ($attachments as $attachment) {
    $path = UPLOAD_DIR . '/' . basename((string) $attachment);
    if (is_file($path)) unlink($path);
}
flash('success', 'L’équipe, son apprenti et ses messages ont été supprimés.');
redirect('admin/teams.php');
