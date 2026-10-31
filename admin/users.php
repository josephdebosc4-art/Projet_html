<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';
$admin = require_admin();
$error = '';

if (is_post()) {
    verify_csrf();
    $userId = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $newRole = (string) ($_POST['role'] ?? '');
    $allowedRoles = ['pending', 'apprentice', 'school_tutor', 'company_tutor'];

    if (!$userId || !in_array($newRole, $allowedRoles, true)) {
        $error = 'La modification demandée n’est pas valide.';
    } elseif ($userId === (int) $admin['id']) {
        $error = 'Vous ne pouvez pas modifier votre propre rôle.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT
                EXISTS(SELECT 1 FROM teams WHERE apprentice_id = ?) AS is_apprentice,
                EXISTS(SELECT 1 FROM teams WHERE school_tutor_id = ?) AS is_school,
                EXISTS(SELECT 1 FROM teams WHERE company_tutor_id = ?) AS is_company'
        );
        $stmt->execute([$userId, $userId, $userId]);
        $links = $stmt->fetch();
        $requiredRole = null;
        if ($links['is_apprentice']) $requiredRole = 'apprentice';
        if ($links['is_school']) $requiredRole = 'school_tutor';
        if ($links['is_company']) $requiredRole = 'company_tutor';

        if ($requiredRole && $newRole !== $requiredRole) {
            $error = 'Retirez d’abord cet utilisateur de son équipe avant de changer son rôle.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role <> 'admin'");
            $stmt->execute([$newRole, $userId]);
            flash('success', 'Le rôle a été mis à jour.');
            redirect('admin/users.php');
        }
    }
}

$users = $pdo->query(
    "SELECT u.id, u.name, u.email, u.role, u.created_at,
            (SELECT GROUP_CONCAT(t.id ORDER BY t.id SEPARATOR ', ')
             FROM teams t WHERE t.apprentice_id = u.id OR t.school_tutor_id = u.id OR t.company_tutor_id = u.id) AS team_ids
     FROM users u ORDER BY FIELD(u.role, 'pending', 'apprentice', 'school_tutor', 'company_tutor', 'admin'), u.name"
)->fetchAll();

$pageTitle = 'Gestion des utilisateurs';
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="container">
    <div class="page-heading d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3">
        <div><div class="eyebrow text-primary mb-2">Administration</div><h1 class="h2 mb-0">Utilisateurs</h1></div>
        <a class="btn btn-primary" href="<?= e(url('admin/teams.php')) ?>">Gérer les équipes</a>
    </div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="panel-card">
        <div class="table-responsive">
            <table class="table admin-table align-middle mb-0">
                <thead><tr><th>Nom</th><th>E-mail</th><th>Inscription</th><th>Équipe(s)</th><th>Rôle</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $listedUser): ?>
                    <tr>
                        <td><?= e($listedUser['name']) ?></td>
                        <td><?= e($listedUser['email']) ?></td>
                        <td><?= e(format_date($listedUser['created_at'])) ?></td>
                        <td><?= $listedUser['team_ids'] === null ? 'Aucune' : e('N° ' . $listedUser['team_ids']) ?></td>
                        <?php if ($listedUser['role'] === 'admin'): ?>
                            <td><span class="badge badge-role"><?= e(role_label($listedUser['role'])) ?></span></td><td></td>
                        <?php else: ?>
                            <?php $formId = 'role-form-' . (int) $listedUser['id']; ?>
                            <td>
                                <form id="<?= e($formId) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                                    <label class="visually-hidden" for="role-<?= (int) $listedUser['id'] ?>">Rôle de <?= e($listedUser['name']) ?></label>
                                    <select id="role-<?= (int) $listedUser['id'] ?>" name="role" class="form-select form-select-sm">
                                        <?php foreach (['pending', 'apprentice', 'school_tutor', 'company_tutor'] as $role): ?>
                                            <option value="<?= e($role) ?>" <?= $listedUser['role'] === $role ? 'selected' : '' ?>><?= e(role_label($role)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                            <td><button class="btn btn-sm btn-outline-primary" type="submit" form="<?= e($formId) ?>">Enregistrer</button></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
