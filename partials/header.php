<?php
$pageTitle = $pageTitle ?? 'Suivi apprentissage';
$connectedUser = current_user();
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Messagerie de suivi entre apprentis et tuteurs">
    <title><?= e($pageTitle) ?> - Suivi des apprentis</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/favicon.svg')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/style.css')) ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark main-nav">
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('index.php')) ?>">Suivi des apprentis</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu" aria-controls="mainMenu" aria-expanded="false" aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php if ($connectedUser): ?>
                    <?php if ($connectedUser['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/users.php')) ?>">Utilisateurs</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('admin/teams.php')) ?>">Équipes</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('dashboard.php')) ?>">Discussions</a></li>
                        <?php if ($connectedUser['role'] !== 'pending'): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('mailbox.php')) ?>">Reçus</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('mailbox.php?box=sent')) ?>">Envoyés</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li class="nav-item"><span class="nav-link user-name"><?= e($connectedUser['name']) ?></span></li>
                    <li class="nav-item">
                        <form action="<?= e(url('logout.php')) ?>" method="post" class="m-0">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-light" type="submit">Déconnexion</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Connexion</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-light ms-lg-2" href="<?= e(url('register.php')) ?>">Créer un compte</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?php if ($flashMessage): ?>
        <div class="container pt-3">
            <div class="alert alert-<?= e($flashMessage['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        </div>
    <?php endif; ?>
