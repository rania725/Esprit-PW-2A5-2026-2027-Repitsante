<section class="form-card">
    <h1>Saisir le code</h1>
    <p role="status">Si cette adresse est associée à un compte, un code a été envoyé. Vérifie aussi tes courriers indésirables.</p>
    <form class="form-grid" method="post" action="<?= e(url('password-code')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <label for="code">Code à 6 chiffres</label>
        <input required id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"<?= isset($errors['code']) ? ' aria-invalid="true" aria-describedby="code-error"' : '' ?>>
        <?php if (isset($errors['code'])): ?><p class="field-error" id="code-error"><?= e($errors['code']) ?></p><?php endif; ?>
        <button class="button" type="submit">Valider le code</button>
    </form>
    <p class="form-footer"><a href="<?= e(url('password-forgot')) ?>">Demander un nouveau code</a></p>
</section>
