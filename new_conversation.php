<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();
$teamId = filter_input(INPUT_GET, 'team_id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare(
    'SELECT t.*, apprentice.name AS apprentice_name FROM teams t
     JOIN users apprentice ON apprentice.id = t.apprentice_id
     WHERE t.id = ? AND (t.apprentice_id = ? OR t.school_tutor_id = ? OR t.company_tutor_id = ?)'
);
$stmt->execute([$teamId, (int) $user['id'], (int) $user['id'], (int) $user['id']]);
$team = $stmt->fetch();
if (!$team) {
    http_response_code(404);
    exit('Équipe introuvable.');
}

$subject = '';
$category = 'suivi';
$errors = [];
$memberIds = [(int) $team['apprentice_id'], (int) $team['school_tutor_id'], (int) $team['company_tutor_id']];
$stmt = $pdo->prepare('SELECT id, name, role FROM users WHERE id IN (?, ?, ?) AND id <> ? ORDER BY name');
$stmt->execute([...$memberIds, (int) $user['id']]);
$contacts = $stmt->fetchAll();
$chosenRecipients = array_map('intval', array_column($contacts, 'id'));
if (is_post()) {
    verify_csrf();
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $category = (string) ($_POST['category'] ?? '');
    $chosenRecipients = array_values(array_unique(array_map('intval', (array) ($_POST['recipients'] ?? []))));
    if (!$chosenRecipients || count($chosenRecipients) > 2 || array_diff($chosenRecipients, array_column($contacts, 'id'))) {
        $errors[] = 'Choisissez un ou deux destinataires de cette équipe.';
    }
    if (mb_strlen($subject) < 2 || mb_strlen($subject) > 120) $errors[] = 'L’objet doit contenir entre 2 et 120 caractères.';
    if (!in_array($category, ['suivi', 'information', 'rendez-vous', 'autre'], true)) $errors[] = 'Catégorie invalide.';
    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO conversations (team_id, subject, category) VALUES (?, ?, ?)');
            $stmt->execute([$teamId, $subject, $category]);
            $conversationId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?)');
            foreach ([(int) $user['id'], ...$chosenRecipients] as $memberId) $stmt->execute([$conversationId, $memberId]);
            $pdo->commit();
            redirect('conversation.php?id=' . $conversationId);
