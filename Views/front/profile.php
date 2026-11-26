<section class="form-card profile-card">
    <h1>Mon espace</h1>
    <p>Bienvenue, <?= e($account->getUsername()) ?>.</p>

    <div class="profile-photo-editor">
        <?php if ($account->getProfilePhoto() !== null): ?>
            <img class="profile-photo" src="<?= e(url('profile-photo')) ?>" alt="Votre photo de profil" width="112" height="112">
        <?php else: ?>
            <div class="profile-photo-placeholder" aria-hidden="true"><?= e(strtoupper(substr($account->getUsername(), 0, 2))) ?></div>
        <?php endif; ?>
        <p>Photo de profil <span>(facultative)</span></p>
    </div>

    <form class="profile-edit-form" method="post" action="<?= e(url('dashboard')) ?>" enctype="multipart/form-data">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <label for="profile_photo">Ajouter ou modifier ma photo</label>
        <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help<?= isset($errors['profile_photo']) ? ' photo-error' : '' ?>"<?= isset($errors['profile_photo']) ? ' aria-invalid="true"' : '' ?>>
        <p class="hint" id="photo-help">JPEG, PNG ou WebP · 2 Mo maximum.</p>
        <?php if (isset($errors['profile_photo'])): ?><p class="field-error" id="photo-error" role="alert"><?= e($errors['profile_photo']) ?></p><?php endif; ?>

        <?php if ($account->getProfilePhoto() !== null): ?>
            <label class="profile-remove-photo">
                <input type="checkbox" name="remove_profile_photo" value="1"<?= $removePhotoRequested ? ' checked' : '' ?>>
                Supprimer ma photo actuelle
            </label>
        <?php endif; ?>

        <label for="phone">Numéro de téléphone <span>(facultatif)</span></label>
        <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="30" value="<?= e($values['phone']) ?>" placeholder="+216 12 345 678"<?= isset($errors['phone']) ? ' aria-invalid="true" aria-describedby="phone-error"' : '' ?>>
        <?php if (isset($errors['phone'])): ?><p class="field-error" id="phone-error" role="alert"><?= e($errors['phone']) ?></p><?php endif; ?>

        <button class="button" type="submit">Enregistrer mon profil</button>
    </form>

    <dl class="profile-data">
        <div><dt>Nom d’utilisateur</dt><dd><?= e($account->getUsername()) ?></dd></div>
        <div><dt>Adresse e-mail</dt><dd><?= e($account->getEmail()) ?></dd></div>
        <div><dt>Téléphone</dt><dd><?= $account->getPhone() !== null ? e($account->getPhone()) : 'Non renseigné' ?></dd></div>
        <div><dt>Rôle</dt><dd><?= $account->getRole() === 'admin' ? 'Administrateur' : 'Utilisateur' ?></dd></div>
    </dl>
    <?php if ($account->getRole() === 'admin'): ?>
        <a class="button" href="<?= e(url('admin')) ?>">Ouvrir le Back Office</a>
    <?php endif; ?>
</section>
