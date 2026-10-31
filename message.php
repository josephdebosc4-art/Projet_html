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
                </div>

                <dl class="row meta-list border-bottom pb-3 mb-4">
                    <dt class="col-sm-3">Expéditeur</dt><dd class="col-sm-9"><?= e($message['sender_name']) ?></dd>
                    <dt class="col-sm-3">Destinataire<?= count($recipients) > 1 ? 's' : '' ?></dt>
                    <dd class="col-sm-9"><?= e(implode(', ', array_column($recipients, 'name'))) ?></dd>
                    <dt class="col-sm-3">Équipe</dt><dd class="col-sm-9">Équipe de <?= e($message['apprentice_name']) ?></dd>
                    <dt class="col-sm-3">Envoyé le</dt><dd class="col-sm-9"><?= e(format_date($message['created_at'])) ?></dd>
                </dl>

                <div class="message-body mb-4"><?= nl2br(e($message['body'])) ?></div>

                <?php foreach (message_attachments($messageId) as $pdf): ?>
                    <div class="alert alert-light border d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                        <span>📎 <?= e($pdf['original_name']) ?></span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('download.php?id=' . $message['id'] . '&file=' . $pdf['id'])) ?>">Télécharger le PDF</a>
                    </div>
                <?php endforeach; ?>

                <?php if ((int) $message['sender_id'] === (int) $user['id']): ?>
                    <section class="mt-4" aria-labelledby="read-status">
                        <h2 id="read-status" class="h5">Suivi des lectures</h2>
                        <ul class="list-group mb-4">
                            <?php foreach ($recipients as $recipient): ?>
                                <li class="list-group-item d-flex justify-content-between gap-3">
                                    <span><?= e($recipient['name']) ?></span>
                                    <span class="text-secondary"><?= $recipient['read_at'] ? 'Lu le ' . e(format_date($recipient['read_at'])) : 'Pas encore lu' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <div class="d-flex flex-column flex-sm-row gap-2">
                    <a class="btn btn-primary" href="<?= e(url('conversation.php?id=' . $message['conversation_id'] . '#ecrire')) ?>">Répondre dans la discussion</a>
                    <?php if ((int) $message['sender_id'] === (int) $user['id']): ?>
                        <form method="post" action="<?= e(url('delete_message.php')) ?>" onsubmit="return confirm('Supprimer ce message pour tous ses destinataires ?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                            <button class="btn btn-outline-danger" type="submit">Supprimer mon message</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if ((int) $message['sender_id'] === (int) $user['id']): ?>
                    <p class="form-text mb-0">Cette suppression retire aussi le message de la boîte des destinataires.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
