<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';
require_admin();

$teams = $pdo->query(
    'SELECT t.id, t.created_at,
            apprentice.name AS apprentice_name,
            school.name AS school_tutor_name,
            company.name AS company_tutor_name,
            COUNT(m.id) AS message_count
     FROM teams t
     JOIN users apprentice ON apprentice.id = t.apprentice_id
     JOIN users school ON school.id = t.school_tutor_id
     JOIN users company ON company.id = t.company_tutor_id
     LEFT JOIN messages m ON m.team_id = t.id
     GROUP BY t.id, t.created_at, apprentice.name, school.name, company.name
     ORDER BY apprentice.name'
)->fetchAll();

$pageTitle = 'Gestion des équipes';
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="container">
    <div class="page-heading d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3">
        <div><div class="eyebrow text-primary mb-2">Administration</div><h1 class="h2 mb-0">Équipes de suivi</h1></div>
        <a class="btn btn-primary" href="<?= e(url('admin/team_form.php')) ?>">Créer une équipe</a>
    </div>
    <div class="panel-card">
        <?php if (!$teams): ?>
            <div class="empty-state">Aucune équipe n’a encore été créée.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>N°</th><th>Apprenti</th><th>Tuteur école</th><th>Tuteur entreprise</th><th>Messages</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td><?= (int) $team['id'] ?></td>
                            <td><?= e($team['apprentice_name']) ?></td>
                            <td><?= e($team['school_tutor_name']) ?></td>
                            <td><?= e($team['company_tutor_name']) ?></td>
                            <td><?= (int) $team['message_count'] ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/team_form.php?id=' . $team['id'])) ?>">Modifier</a>
                                    <form method="post" action="<?= e(url('admin/team_delete.php')) ?>" onsubmit="return confirm('Supprimer cette équipe, son apprenti et tous ses messages ?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $team['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
