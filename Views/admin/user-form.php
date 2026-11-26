<section class="panel admin-form" aria-labelledby="form-heading">
    <h2 class="panel-heading" id="form-heading"><?php $icon($target ? 'edit' : 'plus'); ?> Informations du compte</h2>
    <form class="form-grid" method="post" action="<?= e($target ? url('user-edit', ['id' => $target->getId()]) : url('user-create')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <?php require __DIR__ . '/../layouts/user-fields.php'; ?>
        <label for="role">Rôle</label>
        <?php if ($ownAccount): ?>
            <input type="text" id="role" value="Administrateur" readonly>
            <input type="hidden" name="role" value="admin">
        <?php else: ?>
            <select name="role" id="role" required<?= isset($errors['role']) ? ' aria-invalid="true" aria-describedby="role-error"' : '' ?>>
                <option value="user"<?= $values['role'] === 'user' ? ' selected' : '' ?>>Utilisateur</option>
                <option value="admin"<?= $values['role'] === 'admin' ? ' selected' : '' ?>>Administrateur</option>
            </select>
        <?php endif; ?>
        <?php if (isset($errors['role'])): ?><p class="field-error" id="role-error"><?= e($errors['role']) ?></p><?php endif; ?>
        <div class="button-row">
            <button class="button" type="submit"><?= $target ? 'Enregistrer les modifications' : 'Ajouter l’utilisateur' ?></button>
            <a class="button button-outline" href="<?= e(url('users')) ?>">Annuler</a>
        </div>
    </form>
</section>
