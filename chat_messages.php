<?php if (!$messages): ?>
    <p class="text-secondary mb-0">Pas encore de messages. Écrivez le premier !</p>
<?php endif; ?>
<?php foreach ($messages as $message): ?>
    <?php $mine = (int) $message['sender_id'] === (int) $user['id']; ?>
    <div class="chat-bubble <?= $mine ? 'chat-mine' : '' ?>">
        <div class="d-flex justify-content-between gap-3 small mb-1">
            <strong><?= e($message['sender_name']) ?></strong>
            <span><?= e(format_date($message['created_at'])) ?></span>
        </div>
        <div class="message-body"><?= nl2br(e($message['body'])) ?></div>
        <?php foreach (message_attachments((int) $message['id']) as $pdf): ?>
            <div class="mt-2"><a href="<?= e(url('download.php?id=' . $message['id'] . '&file=' . $pdf['id'])) ?>">PDF : <?= e($pdf['original_name']) ?></a></div>
        <?php endforeach; ?>
        <?php if ($mine): ?>
            <div class="small mt-2">Lu par <?= (int) $message['read_count'] ?>/<?= (int) $message['recipient_count'] ?> · <a href="<?= e(url('message.php?id=' . $message['id'])) ?>">Détails</a></div>
            <form method="post" action="<?= e(url('delete_message.php')) ?>" onsubmit="return confirm('Supprimer ce message pour tous ?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
                <button class="btn btn-sm btn-link p-0 mt-1" type="submit">Supprimer</button>
            </form>
        <?php endif; ?>
        <?php if (!$mine): ?>
            <div class="small mt-2"><a href="<?= e(url('message.php?id=' . $message['id'])) ?>">Détails du message</a></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
