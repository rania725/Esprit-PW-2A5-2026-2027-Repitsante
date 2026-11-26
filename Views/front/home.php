<section class="hero">
    <div>
        <h1>Un parcours médical coordonné, moins de déplacements</h1>
        <p>Répit Santé aide à regrouper les analyses, les examens et les consultations quand un patient doit se déplacer jusqu’à Tunis.</p>
        <div class="button-row">
            <?php if (isset($account)): ?>
                <a class="button" href="<?= e(url($account->getRole() === 'admin' ? 'admin' : 'dashboard')) ?>">Accéder à mon espace</a>
            <?php else: ?>
                <a class="button" href="<?= e(url('register')) ?>">Créer un compte</a>
                <a class="button button-outline" href="<?= e(url('login')) ?>">Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
    <img src="<?= e(assetUrl('Views/assets/img/logo-repit-sante.png')) ?>" alt="Répit Santé" width="292" height="269">
</section>
