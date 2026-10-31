<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();
$sent = ($_GET['box'] ?? '') === 'sent';
if ($sent) {
    $stmt = $pdo->prepare('SELECT m.*, sender.name AS sender_name, NULL AS read_at,
        (SELECT GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ", ") FROM message_recipients mr
         JOIN users u ON u.id = mr.recipient_id WHERE mr.message_id = m.id) AS recipients,
        (SELECT COUNT(*) FROM message_recipients r WHERE r.message_id = m.id AND r.read_at IS NOT NULL) AS read_count,
        (SELECT COUNT(*) FROM message_recipients r WHERE r.message_id = m.id) AS recipient_count
        FROM messages m JOIN users sender ON sender.id = m.sender_id
        WHERE m.sender_id = ? ORDER BY m.created_at DESC, m.id DESC');
} else {
    $stmt = $pdo->prepare('SELECT m.*, sender.name AS sender_name, mr.read_at
        FROM messages m JOIN users sender ON sender.id = m.sender_id
        JOIN message_recipients mr ON mr.message_id = m.id
        WHERE mr.recipient_id = ? ORDER BY mr.read_at IS NULL DESC, m.created_at DESC, m.id DESC');
}
$stmt->execute([(int) $user['id']]);
$messages = $stmt->fetchAll();
$pageTitle = $sent ? 'Messages envoyés' : 'Messages reçus';
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <h1 class="h2 mb-3"><?= e($pageTitle) ?></h1>
    <?php if (!$sent): ?>
        <?php $unread = count(array_filter($messages, fn($message) => $message['read_at'] === null)); ?>
        <p><?= $unread ?> message<?= $unread > 1 ? 's' : '' ?> non lu<?= $unread > 1 ? 's' : '' ?>. Les nouveaux messages sont placés en premier.</p>
    <?php endif; ?>
    <?php if (!$messages): ?>
        <p>Aucun message pour le moment.</p>
    <?php else: ?>
        <div class="table-responsive bg-white border rounded">
            <table class="table message-table">
                <thead><tr><th>Titre</th><th><?= $sent ? 'Destinataires' : 'Expéditeur' ?></th><th>Catégorie</th><th>Date d’envoi</th><th><?= $sent ? 'Lectures' : 'État' ?></th></tr></thead>
                <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr class="<?= !$sent && $message['read_at'] === null ? 'message-row-unread' : '' ?>">
                        <td><a href="<?= e(url('message.php?id=' . $message['id'])) ?>"><?= e($message['subject']) ?></a></td>
                        <td data-label="<?= $sent ? 'À' : 'De' ?>"><?= e($sent ? $message['recipients'] : $message['sender_name']) ?></td>
                        <td data-label="Catégorie"><?= e(category_label($message['category'])) ?></td>
                        <td data-label="Date"><?= e(format_date($message['created_at'])) ?></td>
                        <td data-label="<?= $sent ? 'Lectures' : 'État' ?>">
                            <?php if ($sent): ?>
                                <?= (int) $message['read_count'] ?>/<?= (int) $message['recipient_count'] ?>
                            <?php else: ?>
                                <span class="badge <?= $message['read_at'] === null ? 'text-bg-primary' : 'text-bg-light' ?>"><?= $message['read_at'] === null ? 'Non lu' : 'Lu' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
