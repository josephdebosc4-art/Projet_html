<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();

if (!is_post()) {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
verify_csrf();
$messageId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT conversation_id, attachment_path FROM messages WHERE id = ? AND sender_id = ?');
$stmt->execute([$messageId, (int) $user['id']]);
$message = $stmt->fetch();
if (!$message) {
