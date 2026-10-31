<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();
$messageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if (!$messageId || !user_can_access_message($messageId, (int) $user['id'])) {
    http_response_code(404);
    exit('Message introuvable.');
}

$stmt = $pdo->prepare(
    'SELECT m.*, sender.name AS sender_name, apprentice.name AS apprentice_name
     FROM messages m
     JOIN users sender ON sender.id = m.sender_id
     JOIN teams t ON t.id = m.team_id
     JOIN users apprentice ON apprentice.id = t.apprentice_id
     WHERE m.id = ?'
);
$stmt->execute([$messageId]);
$message = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT u.id, u.name, u.role, mr.read_at
     FROM message_recipients mr JOIN users u ON u.id = mr.recipient_id
     WHERE mr.message_id = ? ORDER BY u.name'
);
$stmt->execute([$messageId]);
$recipients = $stmt->fetchAll();

$stmt = $pdo->prepare('UPDATE message_recipients SET read_at = COALESCE(read_at, NOW()) WHERE message_id = ? AND recipient_id = ?');
$stmt->execute([$messageId, (int) $user['id']]);
foreach ($recipients as &$recipient) {
    if ((int) $recipient['id'] === (int) $user['id'] && $recipient['read_at'] === null) {
        $recipient['read_at'] = date('Y-m-d H:i:s');
    }
}
unset($recipient);

$pageTitle = $message['subject'];
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="panel-card">
                <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 mb-4">
                    <div>
                        <span class="badge text-bg-info mb-2"><?= e(category_label($message['category'])) ?></span>
                        <h1 class="h2 mb-0"><?= e($message['subject']) ?></h1>
                    </div>
                    <a href="<?= e(url('dashboard.php')) ?>">Retour aux messages</a>
