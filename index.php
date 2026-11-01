<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
$pageTitle = 'Accueil';
require __DIR__ . '/partials/header.php';
?>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 mb-3">Suivi des apprentis</h1>
            <p>Ce site permet aux apprentis, aux tuteurs école et aux tuteurs entreprise d'échanger pendant l'alternance.</p>
            <p>Chaque équipe peut ouvrir des discussions avec un objet, pour un ou deux destinataires. On peut y échanger des messages et joindre un ou plusieurs PDF.</p>

            <div class="d-flex flex-wrap gap-2 my-4">
                <?php if (current_user()): ?>
                    <a class="btn btn-primary" href="<?= e(url('dashboard.php')) ?>">Voir mes discussions</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="<?= e(url('login.php')) ?>">Se connecter</a>
                    <a class="btn btn-outline-primary" href="<?= e(url('register.php')) ?>">Créer un compte</a>
                <?php endif; ?>
            </div>

            <h2 class="h4 mt-5 mb-3">Fonctionnement</h2>
            <ol class="ps-4">
                <li class="mb-2">Créer un compte et se connecter.</li>
                <li class="mb-2">Attendre que l'administrateur attribue un rôle et crée l'équipe.</li>
                <li>Ouvrir une discussion sur un sujet, écrire des messages, puis la clore quand le sujet est terminé.</li>
            </ol>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
