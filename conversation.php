<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$user = require_member();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare(
    'SELECT c.*, apprentice.id AS apprentice_id, apprentice.name AS apprentice_name,
            school.id AS school_id, school.name AS school_name,
            company.id AS company_id, company.name AS company_name
     FROM conversations c
     JOIN teams t ON t.id = c.team_id
     JOIN users apprentice ON apprentice.id = t.apprentice_id
     JOIN users school ON school.id = t.school_tutor_id
     JOIN users company ON company.id = t.company_tutor_id
     WHERE c.id = ? AND (t.apprentice_id = ? OR t.school_tutor_id = ? OR t.company_tutor_id = ?)'
    . ' AND EXISTS (SELECT 1 FROM conversation_members cm WHERE cm.conversation_id = c.id AND cm.user_id = ?)'
);
$stmt->execute([$id, (int) $user['id'], (int) $user['id'], (int) $user['id'], (int) $user['id']]);
$conversation = $stmt->fetch();
if (!$conversation) {
    http_response_code(404);
    exit('Discussion introuvable.');
}
$stmt = $pdo->prepare('SELECT u.id, u.name FROM conversation_members cm JOIN users u ON u.id = cm.user_id
    WHERE cm.conversation_id = ? AND cm.user_id <> ? ORDER BY u.name');
$stmt->execute([$id, (int) $user['id']]);
$contacts = $stmt->fetchAll();

$body = '';
$errors = [];
if (is_post()) {
    verify_csrf();
    if ($conversation['closed_at']) {
        http_response_code(403);
        exit('Cette discussion est close.');
    }
    $body = trim((string) ($_POST['body'] ?? ''));
    if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) $errors[] = 'Le message doit contenir entre 2 et 5000 caractères.';

    $pdfs = [];
    $files = $_FILES['attachments'] ?? null;
    if ($files && is_array($files['name'])) {
        $selectedFiles = array_filter($files['error'], fn($error) => $error !== UPLOAD_ERR_NO_FILE);
        if (count($selectedFiles) > MAX_PDF_FILES) $errors[] = 'Choisissez au maximum 5 PDF par message.';
        foreach ($selectedFiles as $index => $error) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || (int) $files['size'][$index] > MAX_PDF_SIZE) {
                $errors[] = 'Chaque PDF doit faire au maximum 5 Mo.';
            } elseif ($error !== UPLOAD_ERR_OK) {
                $errors[] = 'Un document n’a pas pu être envoyé.';
            } else {
                $temporaryPath = $files['tmp_name'][$index];
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
                $extension = strtolower(pathinfo($files['name'][$index], PATHINFO_EXTENSION));
                if ($mime !== 'application/pdf' || $extension !== 'pdf') {
                    $errors[] = 'Toutes les pièces jointes doivent être des PDF.';
                } else {
                    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', basename((string) $files['name'][$index]));
                    $pdfs[] = ['temporary_path' => $temporaryPath, 'stored_name' => bin2hex(random_bytes(16)) . '.pdf',
                        'original_name' => mb_substr($name ?: 'document.pdf', 0, 180)];
                }
            }
        }
    }

    if (!$errors) {
        $uploadedPaths = [];
        foreach ($pdfs as $pdf) {
            $path = UPLOAD_DIR . '/' . $pdf['stored_name'];
            if (!move_uploaded_file($pdf['temporary_path'], $path)) {
                $errors[] = 'Impossible d’enregistrer un PDF.';
                break;
            }
            $uploadedPaths[] = $path;
        }
        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $lock = $pdo->prepare('SELECT closed_at FROM conversations WHERE id = ? FOR UPDATE');
                $lock->execute([$id]);
                if ($lock->fetchColumn() !== null) {
                    $pdo->rollBack();
                    foreach ($uploadedPaths as $path) if (is_file($path)) unlink($path);
                    flash('warning', 'La discussion vient d’être close. Le message n’a pas été envoyé.');
                    redirect('conversation.php?id=' . $id);
                }
                $stmt = $pdo->prepare(
                    'INSERT INTO messages (team_id, conversation_id, sender_id, subject, category, body)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $conversation['team_id'], $id, (int) $user['id'], $conversation['subject'],
                    $conversation['category'], $body,
                ]);
                $messageId = (int) $pdo->lastInsertId();
                $stmt = $pdo->prepare('INSERT INTO message_recipients (message_id, recipient_id) VALUES (?, ?)');
                foreach ($contacts as $contact) $stmt->execute([$messageId, (int) $contact['id']]);
                $stmt = $pdo->prepare('INSERT INTO message_attachments (message_id, stored_name, original_name) VALUES (?, ?, ?)');
                foreach ($pdfs as $pdf) $stmt->execute([$messageId, $pdf['stored_name'], $pdf['original_name']]);
                $pdo->commit();
                redirect('conversation.php?id=' . $id);
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                foreach ($uploadedPaths as $path) if (is_file($path)) unlink($path);
                throw $exception;
            }
        }
        if ($errors) foreach ($uploadedPaths as $path) if (is_file($path)) unlink($path);
    }
}

// La lecture est enregistrée quand la discussion est ouverte, y compris lors des rafraîchissements du chat.
$stmt = $pdo->prepare(
    'UPDATE message_recipients mr JOIN messages m ON m.id = mr.message_id
     SET mr.read_at = NOW()
     WHERE m.conversation_id = ? AND mr.recipient_id = ? AND mr.read_at IS NULL'
);
$stmt->execute([$id, (int) $user['id']]);

$stmt = $pdo->prepare(
    'SELECT m.id, m.sender_id, m.body, m.attachment_name, m.created_at,
            sender.name AS sender_name,
            (SELECT COUNT(*) FROM message_recipients r WHERE r.message_id = m.id) AS recipient_count,
            (SELECT COUNT(*) FROM message_recipients r WHERE r.message_id = m.id AND r.read_at IS NOT NULL) AS read_count
     FROM messages m
     JOIN users sender ON sender.id = m.sender_id
     LEFT JOIN message_recipients mine ON mine.message_id = m.id AND mine.recipient_id = ?
     WHERE m.conversation_id = ? AND (m.sender_id = ? OR mine.recipient_id IS NOT NULL)
     ORDER BY m.created_at, m.id'
);
$stmt->execute([(int) $user['id'], $id, (int) $user['id']]);
$messages = $stmt->fetchAll();

if (isset($_GET['partial']) && !is_post()) {
    header('X-Chat-Partial: 1');
    if ($conversation['closed_at']) header('X-Conversation-Closed: 1');
    require __DIR__ . '/partials/chat_messages.php';
    exit;
}

$pageTitle = $conversation['subject'];
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <a href="<?= e(url('dashboard.php')) ?>">← Discussions</a>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-2">
        <div>
            <h1 class="h2 mb-1"><?= e($conversation['subject']) ?></h1>
            <p class="text-secondary mb-0">Équipe de <?= e($conversation['apprentice_name']) ?> · <?= e(category_label($conversation['category'])) ?></p>
        </div>
        <?php if (!$conversation['closed_at']): ?>
            <form method="post" action="<?= e(url('close_conversation.php')) ?>" onsubmit="return confirm('Clore cette discussion ? Elle restera visible mais personne ne pourra y écrire.')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <button class="btn btn-outline-secondary" type="submit">Clore la discussion</button>
            </form>
        <?php else: ?>
            <span class="badge text-bg-secondary">Discussion close</span>
        <?php endif; ?>
    </div>
    <p class="small text-secondary mb-3">Avec <?= e(implode(', ', array_column($contacts, 'name'))) ?>. Les réponses apparaissent automatiquement.</p>

    <div class="row"><div class="col-lg-8">
        <div id="chat-messages" class="chat-messages border rounded bg-white p-3 mb-3" role="log" aria-label="Messages de la discussion">
            <?php require __DIR__ . '/partials/chat_messages.php'; ?>
        </div>
        <?php if (!$conversation['closed_at']): ?>
            <div class="panel-card" id="ecrire">
                <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label for="body" class="form-label required">Votre message</label>
                    <textarea class="form-control mb-3" id="body" name="body" rows="3" maxlength="5000" required><?= e($body) ?></textarea>
                    <label for="attachments" class="form-label">PDF (facultatif : 5 fichiers maximum, 5 Mo chacun)</label>
                    <input class="form-control mb-3" type="file" id="attachments" name="attachments[]" accept="application/pdf,.pdf" multiple>
                    <button class="btn btn-primary" type="submit">Envoyer</button>
                </form>
            </div>
        <?php else: ?>
            <p>Ce sujet est clos. Vous pouvez <a href="<?= e(url('new_conversation.php?team_id=' . $conversation['team_id'])) ?>">ouvrir une autre discussion</a>.</p>
        <?php endif; ?>
    </div></div>
</div>
<script>
const chatBox = document.getElementById('chat-messages');
chatBox.scrollTop = chatBox.scrollHeight;
async function updateChat() {
    if (document.hidden) return;
    const nearBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 80;
    const oldScroll = chatBox.scrollTop;
    try {
        const response = await fetch(<?= json_encode(url('conversation.php?id=' . $id . '&partial=1'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, {credentials: 'same-origin', cache: 'no-store'});
        if (!response.ok || response.redirected || response.headers.get('X-Chat-Partial') !== '1') return;
        if (response.headers.get('X-Conversation-Closed') === '1' && document.getElementById('ecrire')) {
            location.reload();
            return;
        }
        chatBox.innerHTML = await response.text();
        chatBox.scrollTop = nearBottom ? chatBox.scrollHeight : oldScroll;
    } catch (error) { /* Une coupure réseau n'empêche pas d'utiliser la page. */ }
}
setInterval(updateChat, 3000);
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
