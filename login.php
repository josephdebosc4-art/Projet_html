<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $account = $stmt->fetch();

    if ($account && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $account['id'];
        flash('success', 'Connexion réussie.');
        redirect('dashboard.php');
    }
    $error = 'Adresse e-mail ou mot de passe incorrect.';
}

