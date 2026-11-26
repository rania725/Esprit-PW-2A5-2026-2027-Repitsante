<section class="form-card">
    <h1>Mot de passe oublié ?</h1>
    <p>Indique l’adresse e-mail associée à ton compte.</p>
    <form class="form-grid" method="post" action="<?= e(url('password-forgot')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <label for="email">Adresse e-mail</label>
        <input required id="email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= e($email) ?>">
        <button class="button" type="submit">Envoyer le code</button>
    </form>
    <p class="form-footer"><a href="<?= e(url('login')) ?>">Retour à la connexion</a></p>
</section>
