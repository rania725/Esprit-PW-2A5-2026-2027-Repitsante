<?php
$onDashboard = $section === 'dashboard';
$navigation = [
    ['grid', 'Tableau de bord', url('admin'), $onDashboard],
    ['users', 'Patients', url('admin') . '#patients', false],
    ['calendar', 'Rendez-vous', url('admin') . '#planning', false],
    ['layers', 'Services', url('admin', ['q' => 'Radiologie']) . '#planning', false],
    ['route', 'Coordination', url('admin') . '#coordination', false],
    ['settings', 'Utilisateurs', url('users'), !$onDashboard],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Répit Santé</title>
    <link rel="stylesheet" href="<?= e(assetUrl('Views/assets/css/style.css')) ?>">
    <script src="<?= e(assetUrl('Views/assets/js/dashboard.js')) ?>" defer></script>
</head>
<body class="admin-body">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= e(url('admin')) ?>">
            <img src="<?= e(assetUrl('Views/assets/img/logo-repit-sante.png')) ?>" alt="Répit Santé — Un parcours médical coordonné, moins de déplacements" width="292" height="269">
        </a>
        <nav class="sidebar-nav" aria-label="Back Office">
            <?php foreach ($navigation as [$symbol, $label, $href, $active]): ?>
                <a href="<?= e($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                    <?php $icon($symbol); ?><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <img class="sidebar-hospital" src="<?= e(assetUrl('Views/assets/img/hopital.png')) ?>" alt="" width="311" height="209">
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <div class="page-heading">
                <h1><?= e($title) ?></h1>
                <p><?= $onDashboard ? 'Suivi des rendez-vous et des parcours' : 'Comptes et droits d’accès' ?></p>
            </div>
            <div class="top-controls">
                <?php if ($onDashboard || isset($query)): ?>
                    <form class="search" action="index.php" method="get" role="search">
                        <input type="hidden" name="action" value="<?= $onDashboard ? 'admin' : 'users' ?>">
                        <button type="submit" aria-label="Lancer la recherche"><?php $icon('search'); ?></button>
                        <input type="search" name="q" aria-label="<?= $onDashboard ? 'Rechercher un patient ou un rendez-vous' : 'Rechercher un utilisateur' ?>" placeholder="<?= $onDashboard ? 'Rechercher un patient, un rendez-vous…' : 'Rechercher un utilisateur…' ?>" value="<?= e($query ?? '') ?>" maxlength="150">
                    </form>
                <?php endif; ?>
                <details class="account-menu">
                    <summary>
                        <img src="<?= e(assetUrl('Views/assets/img/avatar.png')) ?>" alt="" width="58" height="58">
                        <span>Administrateur</span>
                        <?php $icon('down'); ?>
                    </summary>
                    <div class="account-dropdown">
                        <strong><?= e($account->getUsername()) ?></strong>
                        <a href="<?= e(url('dashboard')) ?>">Mon espace</a>
                        <form method="post" action="<?= e(url('logout')) ?>">
                            <?php require __DIR__ . '/csrf.php'; ?>
                            <button type="submit">Se déconnecter</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>
        <?php if ($message !== null): ?>
            <p class="notice" role="status"><?= e($message) ?></p>
        <?php endif; ?>
        <?= $content ?>
    </main>
</body>
</html>
