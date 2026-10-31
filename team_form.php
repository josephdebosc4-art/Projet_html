<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';
require_admin();

$teamId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if (!$teamId && is_post()) {
    $teamId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
}
$team = null;
if ($teamId) {
    $stmt = $pdo->prepare('SELECT * FROM teams WHERE id = ?');
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();
    if (!$team) {
        http_response_code(404);
        exit('Équipe introuvable.');
    }
}

$currentApprenticeId = (int) ($team['apprentice_id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.email FROM users u
     WHERE u.role = 'apprentice'
       AND (u.id = ? OR NOT EXISTS (SELECT 1 FROM teams t WHERE t.apprentice_id = u.id))
     ORDER BY u.name"
);
$stmt->execute([$currentApprenticeId]);
$apprentices = $stmt->fetchAll();
$schoolTutors = $pdo->query("SELECT id, name, email FROM users WHERE role = 'school_tutor' ORDER BY name")->fetchAll();
$companyTutors = $pdo->query("SELECT id, name, email FROM users WHERE role = 'company_tutor' ORDER BY name")->fetchAll();

$apprenticeId = $currentApprenticeId;
$schoolTutorId = (int) ($team['school_tutor_id'] ?? 0);
$companyTutorId = (int) ($team['company_tutor_id'] ?? 0);
$errors = [];

if (is_post()) {
    verify_csrf();
    $apprenticeId = $team ? $currentApprenticeId : (filter_var($_POST['apprentice_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0);
    $schoolTutorId = filter_var($_POST['school_tutor_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $companyTutorId = filter_var($_POST['company_tutor_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

    $validApprentices = array_map('intval', array_column($apprentices, 'id'));
    $validSchoolTutors = array_map('intval', array_column($schoolTutors, 'id'));
    $validCompanyTutors = array_map('intval', array_column($companyTutors, 'id'));
    if (!in_array($apprenticeId, $validApprentices, true)) $errors[] = 'Choisissez un apprenti disponible.';
    if (!in_array($schoolTutorId, $validSchoolTutors, true)) $errors[] = 'Choisissez un tuteur école.';
    if (!in_array($companyTutorId, $validCompanyTutors, true)) $errors[] = 'Choisissez un tuteur entreprise.';

    if (!$errors) {
        if ($team) {
            $pdo->beginTransaction();
            try {
                foreach ([[(int) $team['school_tutor_id'], $schoolTutorId], [(int) $team['company_tutor_id'], $companyTutorId]] as [$oldId, $newId]) {
                    if ($oldId === $newId) continue;
                    $stmt = $pdo->prepare('INSERT IGNORE INTO conversation_members (conversation_id, user_id)
                        SELECT c.id, ? FROM conversations c JOIN conversation_members cm ON cm.conversation_id = c.id
                        WHERE c.team_id = ? AND cm.user_id = ?');
                    $stmt->execute([$newId, $teamId, $oldId]);
                    $stmt = $pdo->prepare('DELETE cm FROM conversation_members cm JOIN conversations c ON c.id = cm.conversation_id
                        WHERE c.team_id = ? AND cm.user_id = ?');
                    $stmt->execute([$teamId, $oldId]);
                }
                $stmt = $pdo->prepare('UPDATE teams SET school_tutor_id = ?, company_tutor_id = ? WHERE id = ?');
                $stmt->execute([$schoolTutorId, $companyTutorId, $teamId]);
                $pdo->commit();
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
            flash('success', 'Les tuteurs de l’équipe ont été modifiés.');
        } else {
