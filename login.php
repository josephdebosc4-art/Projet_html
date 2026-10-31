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

$pageTitle = 'Connexion';
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-xl-4">
            <div class="panel-card">
                <h1 class="h2 mb-4">Connexion</h1>
                <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label required">Adresse e-mail</label>
                        <input id="email" name="email" type="email" class="form-control" required autocomplete="email" value="<?= e($email) ?>">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label required">Mot de passe</label>
                        <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                </form>
                <p class="text-center mt-4 mb-0">Pas encore de compte&nbsp;? <a href="<?= e(url('register.php')) ?>">S’inscrire</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

