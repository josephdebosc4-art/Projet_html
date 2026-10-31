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

$pageTitle = 'Discussions';
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <h1 class="h2 mb-4">Discussions</h1>
    <?php if ($user['role'] === 'pending'): ?>
        <p>Votre compte est en attente. Un administrateur doit vous attribuer un rôle et vous placer dans une équipe.</p>
    <?php elseif (!$teams): ?>
        <p>Vous n'êtes pas encore dans une équipe.</p>
    <?php else: ?>
        <?php foreach ($teams as $team): ?>
            <section class="panel-card mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h2 class="h4 mb-1">Équipe de <?= e($team['apprentice_name']) ?></h2>
                        <div class="text-secondary small"><?= e($team['school_name']) ?> · <?= e($team['company_name']) ?></div>
                    </div>
                    <a class="btn btn-primary" href="<?= e(url('new_conversation.php?team_id=' . $team['id'])) ?>">Nouvelle discussion</a>
                </div>
                <?php if (!$team['conversations']): ?>
                    <p class="mb-0">Aucune discussion pour cette équipe.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($team['conversations'] as $conversation): ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" href="<?= e(url('conversation.php?id=' . $conversation['id'])) ?>">
                                <span>
                                    <strong><?= e($conversation['subject']) ?></strong><br>
                                    <small class="text-secondary"><?= e(category_label($conversation['category'])) ?> · <?= $conversation['closed_at'] ? 'Close' : 'Ouverte' ?></small>
                                </span>
                                <?php if ((int) $conversation['unread_count'] > 0): ?>
                                    <span class="badge text-bg-primary"><?= (int) $conversation['unread_count'] ?> non lu<?= (int) $conversation['unread_count'] > 1 ? 's' : '' ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
