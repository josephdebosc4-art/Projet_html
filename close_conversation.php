<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();
if (!is_post()) {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
verify_csrf();
$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare(
    'UPDATE conversations c JOIN teams t ON t.id = c.team_id
     SET c.closed_at = NOW()
     WHERE c.id = ? AND c.closed_at IS NULL
       AND EXISTS (SELECT 1 FROM conversation_members cm WHERE cm.conversation_id = c.id AND cm.user_id = ?)
       AND (t.apprentice_id = ? OR t.school_tutor_id = ? OR t.company_tutor_id = ?)'
);
$stmt->execute([$id, (int) $user['id'], (int) $user['id'], (int) $user['id'], (int) $user['id']]);
if (!$stmt->rowCount()) {
    http_response_code(403);
    exit('Cette discussion ne peut pas être close.');
}
redirect('conversation.php?id=' . $id);
