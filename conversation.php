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
