<section class="form-card">
    <h1>Nouveau mot de passe</h1>
    <?php if (isset($errors['general'])): ?><p class="error" role="alert"><?= e($errors['general']) ?></p><?php endif; ?>
    <form class="form-grid" method="post" action="<?= e(url('password-new')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <label for="password">Nouveau mot de passe</label>
        <input required id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72"<?= isset($errors['password']) ? ' aria-invalid="true" aria-describedby="password-error"' : '' ?>>
        <?php if (isset($errors['password'])): ?><p class="field-error" id="password-error"><?= e($errors['password']) ?></p><?php endif; ?>
        <label for="password_confirm">Confirmer le mot de passe</label>
        <input required id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" maxlength="72"<?= isset($errors['password_confirm']) ? ' aria-invalid="true" aria-describedby="confirm-error"' : '' ?>>
        <?php if (isset($errors['password_confirm'])): ?><p class="field-error" id="confirm-error"><?= e($errors['password_confirm']) ?></p><?php endif; ?>
        <button class="button" type="submit">Enregistrer le nouveau mot de passe</button>
    </form>
</section>
