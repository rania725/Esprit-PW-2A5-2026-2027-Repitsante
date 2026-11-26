<section class="message-form-card">
    <a class="messages-back-link" href="<?= e(url('messages')) ?>">← Retour aux messages</a>
    <h1>Nouveau message</h1>
    <p>Choisis un membre de l’équipe pour commencer une conversation privée.</p>

    <?php if ($recipients === []): ?>
        <p class="messages-empty" role="status">Aucun autre compte n’est disponible pour recevoir un message.</p>
    <?php else: ?>
        <form class="message-form" method="post" action="<?= e(url('message-new')) ?>">
            <?php require __DIR__ . '/../layouts/csrf.php'; ?>
            <label for="recipient_id">Destinataire</label>
            <select id="recipient_id" name="recipient_id" required<?= isset($errors['recipient_id']) ? ' aria-invalid="true" aria-describedby="recipient-error"' : '' ?>>
                <option value="">Choisir un destinataire</option>
                <?php foreach ($recipients as $recipient): ?>
                    <option value="<?= e((string) $recipient['id']) ?>"<?= $values['recipient_id'] === (string) $recipient['id'] ? ' selected' : '' ?>>
                        <?= e($recipient['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['recipient_id'])): ?><p class="field-error" id="recipient-error" role="alert"><?= e($errors['recipient_id']) ?></p><?php endif; ?>

            <label for="message-body">Message</label>
            <textarea id="message-body" name="body" rows="7" maxlength="5000" required<?= isset($errors['body']) ? ' aria-invalid="true" aria-describedby="message-error"' : '' ?>><?= e($values['body']) ?></textarea>
            <?php if (isset($errors['body'])): ?><p class="field-error" id="message-error" role="alert"><?= e($errors['body']) ?></p><?php endif; ?>

            <div class="message-form-actions">
                <a class="message-cancel" href="<?= e(url('messages')) ?>">Annuler</a>
                <button class="messages-new-button" type="submit"><?php $icon('send'); ?><span>Envoyer le message</span></button>
            </div>
        </form>
    <?php endif; ?>
</section>
