<section class="form-card">
    <h1>Connexion</h1>
    <?php if (isset($errors['general'])): ?><p class="error" role="alert"><?= e($errors['general']) ?></p><?php endif; ?>
    <form class="form-grid" method="post" action="<?= e(url('login')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <label for="email">Adresse e-mail</label>
        <input required id="email" name="email" type="email" autocomplete="username" maxlength="150" value="<?= e($email) ?>"<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
        <?php if (isset($errors['email'])): ?><p class="field-error" id="email-error"><?= e($errors['email']) ?></p><?php endif; ?>
        <label for="password">Mot de passe</label>
        <input required id="password" name="password" type="password" autocomplete="current-password" maxlength="72"<?= isset($errors['password']) ? ' aria-invalid="true" aria-describedby="password-error"' : '' ?>>
        <?php if (isset($errors['password'])): ?><p class="field-error" id="password-error"><?= e($errors['password']) ?></p><?php endif; ?>
        <a class="button button-outline password-forgot-button" href="<?= e(url('password-forgot')) ?>">Mot de passe oublié ?</a>
        <button class="button" type="submit">Se connecter</button>
    </form>
    <p class="form-footer">Pas encore de compte ? <a href="<?= e(url('register')) ?>">Inscris-toi</a>.</p>
</section>
