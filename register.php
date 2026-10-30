<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (current_user()) {
    redirect('dashboard.php');
}

$errors = [];
$name = '';
$email = '';

if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Le nom doit contenir entre 2 et 100 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors[] = 'L’adresse e-mail n’est pas valide.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Compte créé. Vous pouvez vous connecter.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Créer un compte';
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-xl-5">
            <div class="panel-card">
                <h1 class="h2 mb-2">Créer un compte</h1>
                <p class="text-secondary mb-4">Votre rôle sera choisi ensuite par un administrateur.</p>
                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label required">Nom complet</label>
                        <input id="name" name="name" class="form-control" maxlength="100" required autocomplete="name" value="<?= e($name) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label required">Adresse e-mail</label>
                        <input id="email" name="email" type="email" class="form-control" maxlength="190" required autocomplete="email" value="<?= e($email) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label required">Mot de passe</label>
                        <input id="password" name="password" type="password" class="form-control" minlength="8" required autocomplete="new-password">
                        <div class="form-text">8 caractères minimum.</div>
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label required">Confirmer le mot de passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" required autocomplete="new-password">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Créer mon compte</button>
                </form>
                <p class="text-center mt-4 mb-0">Déjà inscrit&nbsp;? <a href="<?= e(url('login.php')) ?>">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>

