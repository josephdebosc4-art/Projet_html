<?php
declare(strict_types=1);

// Le serveur PHP intégré ne lit pas les fichiers .htaccess.
// On ne laisse donc passer que les pages et ressources publiques du site.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($requestPath) ? ltrim(rawurldecode($requestPath), '/') : '';

$publicFiles = [
    'index.php', 'register.php', 'login.php', 'logout.php',
    'dashboard.php', 'conversation.php', 'new_conversation.php',
    'close_conversation.php', 'compose.php', 'message.php',
    'delete_message.php', 'download.php', 'mailbox.php',
    'admin/users.php', 'admin/teams.php',
    'admin/team_form.php', 'admin/team_delete.php',
    'assets/style.css', 'assets/favicon.svg',
];

if ($path === '' || in_array($path, $publicFiles, true)) {
    return false;
}

http_response_code(404);
exit('Page introuvable.');
