<section class="form-card">
    <h1>Créer un compte</h1>
    <form class="form-grid" method="post" action="<?= e(url('register')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <?php require __DIR__ . '/../layouts/user-fields.php'; ?>
        <label for="password_confirm">Confirmer le mot de passe</label>
        <input required id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" maxlength="72"<?= isset($errors['password_confirm']) ? ' aria-invalid="true" aria-describedby="confirm-error"' : '' ?>>
        <?php if (isset($errors['password_confirm'])): ?><p class="field-error" id="confirm-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
        <button class="button" type="submit">S’inscrire</button>
    </form>
    <p class="form-footer">Déjà un compte ? <a href="<?= e(url('login')) ?>">Connecte-toi</a>.</p>
</section>
