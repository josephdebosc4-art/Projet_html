<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sentToken = $_POST['csrf_token'] ?? '';
    if (!is_string($sentToken) || !hash_equals(csrf_token(), $sentToken)) {
        http_response_code(403);
        exit('Le formulaire a expiré. Revenez à la page précédente et réessayez.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function current_user(): ?array
{
    global $pdo;
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Connectez-vous pour accéder à cette page.');
        redirect('login.php');
    }
    return $user;
}

function require_member(): array
{
    $user = require_login();
    if ($user['role'] === 'pending') {
        flash('warning', 'Votre rôle doit encore être attribué par un administrateur.');
        redirect('dashboard.php');
    }
    if ($user['role'] === 'admin') {
        redirect('admin/users.php');
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Accès interdit.');
    }
    return $user;
}

function role_label(string $role): string
{
    $labels = [
        'pending' => 'En attente',
        'apprentice' => 'Apprenti',
        'school_tutor' => 'Tuteur école',
        'company_tutor' => 'Tuteur entreprise',
        'admin' => 'Administrateur',
    ];
    return $labels[$role] ?? $role;
}

function category_label(string $category): string
{
    $labels = [
        'suivi' => 'Suivi',
        'information' => 'Information',
        'rendez-vous' => 'Rendez-vous',
        'autre' => 'Autre',
    ];
    return $labels[$category] ?? $category;
}

function format_date(string $date): string
{
    $value = new DateTime($date);
    return $value->format('d/m/Y à H:i');
}

function user_can_access_message(int $messageId, int $userId): bool
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT 1 FROM messages m
         WHERE m.id = ? AND (
             m.sender_id = ? OR EXISTS (
                 SELECT 1 FROM message_recipients mr
                 WHERE mr.message_id = m.id AND mr.recipient_id = ?
             )
         )'
    );
    $stmt->execute([$messageId, $userId, $userId]);
    return (bool) $stmt->fetchColumn();
}

function message_attachments(int $messageId): array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, stored_name, original_name FROM message_attachments WHERE message_id = ? ORDER BY id');
    $stmt->execute([$messageId]);
    return $stmt->fetchAll();
}
