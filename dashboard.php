<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_login();
if ($user['role'] === 'admin') redirect('admin/users.php');

$teams = [];
if ($user['role'] !== 'pending') {
    $stmt = $pdo->prepare(
        'SELECT t.id, apprentice.name AS apprentice_name,
                school.name AS school_name, company.name AS company_name
         FROM teams t
         JOIN users apprentice ON apprentice.id = t.apprentice_id
         JOIN users school ON school.id = t.school_tutor_id
         JOIN users company ON company.id = t.company_tutor_id
         WHERE t.apprentice_id = ? OR t.school_tutor_id = ? OR t.company_tutor_id = ?
         ORDER BY apprentice.name'
    );
    $stmt->execute([(int) $user['id'], (int) $user['id'], (int) $user['id']]);
    $teams = $stmt->fetchAll();
    $topics = $pdo->prepare(
        'SELECT c.id, c.subject, c.category, c.closed_at,
                (SELECT COUNT(*) FROM message_recipients mr
                 JOIN messages m ON m.id = mr.message_id
                 WHERE m.conversation_id = c.id AND mr.recipient_id = ? AND mr.read_at IS NULL) AS unread_count
         FROM conversations c
         WHERE c.team_id = ?
           AND EXISTS (SELECT 1 FROM conversation_members cm WHERE cm.conversation_id = c.id AND cm.user_id = ?)
           AND (c.closed_at IS NULL
                OR NOT EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id)
                OR EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id
                           AND (m.sender_id = ? OR EXISTS (SELECT 1 FROM message_recipients mr
                               WHERE mr.message_id = m.id AND mr.recipient_id = ?))))
         ORDER BY c.closed_at IS NOT NULL, c.id DESC'
    );
    foreach ($teams as &$team) {
        $topics->execute([(int) $user['id'], (int) $team['id'], (int) $user['id'], (int) $user['id'], (int) $user['id']]);
        $team['conversations'] = $topics->fetchAll();
    }
    unset($team);
}

