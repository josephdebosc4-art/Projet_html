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
    http_response_code(403);
    exit('Vous ne pouvez supprimer que vos propres messages.');
}
$paths = array_column(message_attachments($messageId), 'stored_name');
if ($message['attachment_path']) $paths[] = $message['attachment_path'];

$stmt = $pdo->prepare('DELETE FROM messages WHERE id = ? AND sender_id = ?');
$stmt->execute([$messageId, (int) $user['id']]);
foreach (array_unique($paths) as $storedName) {
    $path = UPLOAD_DIR . '/' . basename($storedName);
    if (is_file($path)) {
        unlink($path);
    }
}
flash('success', 'Le message a été supprimé.');
redirect('conversation.php?id=' . (int) $message['conversation_id']);
