<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Répit Santé</title>
    <link rel="stylesheet" href="<?= e(assetUrl('Views/assets/css/style.css')) ?>">
    <?php if (($section ?? '') === 'messages'): ?>
        <link rel="stylesheet" href="<?= e(assetUrl('Views/assets/css/messaging.css')) ?>">
    <?php endif; ?>
    <?php if (($section ?? '') === 'profile'): ?>
        <link rel="stylesheet" href="<?= e(assetUrl('Views/assets/css/profile.css')) ?>">
    <?php endif; ?>
</head>
<body class="front-body<?= ($section ?? '') === 'messages' ? ' messages-page' : '' ?>">
    <header class="front-header">
        <a class="wordmark front-brand" href="<?= e(url('home')) ?>">
            <img src="<?= e(assetUrl('Views/assets/img/logo-repit-sante.png')) ?>" alt="" width="58" height="54">
            <span>Répit <strong>Santé</strong></span>
        </a>
        <nav class="front-navigation" aria-label="Navigation principale">
            <?php if (isset($account)): ?>
                <a href="<?= e(url('home')) ?>"<?= ($section ?? '') === 'home' ? ' aria-current="page"' : '' ?>>Accueil</a>
                <a href="<?= e(url('messages')) ?>"<?= ($section ?? '') === 'messages' ? ' aria-current="page"' : '' ?>>Messages</a>
                <span class="front-nav-disabled" aria-disabled="true">Notifications</span>
                <a href="<?= e(url('dashboard')) ?>"<?= ($section ?? '') === 'profile' ? ' aria-current="page"' : '' ?>>Mon profil</a>
                <?php if ($account->getRole() === 'admin'): ?>
                    <a href="<?= e(url('admin')) ?>">Back Office</a>
                <?php endif; ?>
                <form class="inline-form" method="post" action="<?= e(url('logout')) ?>">
                    <?php require __DIR__ . '/csrf.php'; ?>
                    <button class="button button-outline front-logout" type="submit">Se déconnecter</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login')) ?>">Connexion</a>
                <a class="button" href="<?= e(url('register')) ?>">Créer un compte</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="front-main">
        <?php if ($message !== null): ?>
            <p class="notice" role="status"><?= e($message) ?></p>
        <?php endif; ?>
        <?= $content ?>
    </main>
</body>
</html>
