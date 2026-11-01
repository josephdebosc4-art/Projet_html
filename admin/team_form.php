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
            $stmt = $pdo->prepare('INSERT INTO teams (apprentice_id, school_tutor_id, company_tutor_id) VALUES (?, ?, ?)');
            $stmt->execute([$apprenticeId, $schoolTutorId, $companyTutorId]);
            flash('success', 'L’équipe a été créée.');
        }
        redirect('admin/teams.php');
    }
}

$pageTitle = $team ? 'Modifier une équipe' : 'Créer une équipe';
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="panel-card">
                <div class="d-flex justify-content-between gap-3 mb-4">
                    <h1 class="h2 mb-0"><?= $team ? 'Modifier les tuteurs' : 'Créer une équipe' ?></h1>
                    <a href="<?= e(url('admin/teams.php')) ?>">Retour</a>
                </div>
                <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <?php if (!$apprentices || !$schoolTutors || !$companyTutors): ?>
                    <div class="alert alert-warning">Il faut au moins un apprenti disponible, un tuteur école et un tuteur entreprise. Vérifiez d’abord les rôles des utilisateurs.</div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $teamId ?>">
                    <div class="mb-3">
                        <label for="apprentice" class="form-label required">Apprenti</label>
                        <?php if ($team): ?>
                            <?php
                            $apprenticeName = 'Apprenti';
                            foreach ($apprentices as $apprentice) {
                                if ((int) $apprentice['id'] === $currentApprenticeId) {
                                    $apprenticeName = $apprentice['name'];
                                    break;
                                }
                            }
                            ?>
                            <input id="apprentice" class="form-control" value="<?= e($apprenticeName) ?>" disabled>
                        <?php else: ?>
                            <select id="apprentice" name="apprentice_id" class="form-select" required>
                                <option value="">Choisir...</option>
                                <?php foreach ($apprentices as $apprentice): ?>
                                    <option value="<?= (int) $apprentice['id'] ?>" <?= (int) $apprentice['id'] === $apprenticeId ? 'selected' : '' ?>><?= e($apprentice['name']) ?> - <?= e($apprentice['email']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="school-tutor" class="form-label required">Tuteur école</label>
                        <select id="school-tutor" name="school_tutor_id" class="form-select" required>
                            <option value="">Choisir...</option>
                            <?php foreach ($schoolTutors as $tutor): ?>
                                <option value="<?= (int) $tutor['id'] ?>" <?= (int) $tutor['id'] === $schoolTutorId ? 'selected' : '' ?>><?= e($tutor['name']) ?> - <?= e($tutor['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="company-tutor" class="form-label required">Tuteur entreprise</label>
                        <select id="company-tutor" name="company_tutor_id" class="form-select" required>
                            <option value="">Choisir...</option>
                            <?php foreach ($companyTutors as $tutor): ?>
                                <option value="<?= (int) $tutor['id'] ?>" <?= (int) $tutor['id'] === $companyTutorId ? 'selected' : '' ?>><?= e($tutor['name']) ?> - <?= e($tutor['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit" <?= (!$apprentices || !$schoolTutors || !$companyTutors) ? 'disabled' : '' ?>><?= $team ? 'Enregistrer' : 'Créer l’équipe' ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
