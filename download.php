<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();
$messageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if (!$messageId || !user_can_access_message($messageId, (int) $user['id'])) {
    http_response_code(404);
    exit('Document introuvable.');
}

$fileId = filter_input(INPUT_GET, 'file', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare('SELECT stored_name, original_name FROM message_attachments WHERE message_id = ? AND (? = 0 OR id = ?) ORDER BY id LIMIT 1');
$stmt->execute([$messageId, $fileId, $fileId]);
$attachment = $stmt->fetch();
