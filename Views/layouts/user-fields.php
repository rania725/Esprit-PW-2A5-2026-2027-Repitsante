<?php if (isset($errors['general'])): ?><p class="error" role="alert"><?= e($errors['general']) ?></p><?php endif; ?>
<label for="username">Nom d’utilisateur</label>
<input required id="username" name="username" type="text" autocomplete="username" minlength="3" maxlength="50" pattern="[a-zA-Z0-9_\-]{3,50}" value="<?= e($values['username']) ?>" aria-describedby="username-help<?= isset($errors['username']) ? ' username-error' : '' ?>"<?= isset($errors['username']) ? ' aria-invalid="true"' : '' ?>>
<p class="hint" id="username-help">3 à 50 caractères. Lettres sans accents, chiffres, tirets ou tirets bas.</p>
<?php if (isset($errors['username'])): ?><p class="field-error" id="username-error"><?= e($errors['username']) ?></p><?php endif; ?>
<label for="email">Adresse e-mail</label>
<input required id="email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= e($values['email']) ?>"<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
<?php if (isset($errors['email'])): ?><p class="field-error" id="email-error"><?= e($errors['email']) ?></p><?php endif; ?>
<label for="password"><?= isset($target) ? 'Nouveau mot de passe' : 'Mot de passe' ?></label>
<input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72"<?= isset($target) ? '' : ' required' ?> aria-describedby="password-help<?= isset($errors['password']) ? ' password-error' : '' ?>"<?= isset($errors['password']) ? ' aria-invalid="true"' : '' ?>>
<p class="hint" id="password-help"><?= isset($target) ? 'Laisse vide pour garder le mot de passe actuel. ' : '' ?>Au moins 8 caractères, jusqu’à 72 octets.</p>
<?php if (isset($errors['password'])): ?><p class="field-error" id="password-error"><?= e($errors['password']) ?></p><?php endif; ?>
