<section class="conversation-detail">
    <a class="messages-back-link" href="<?= e(url('messages')) ?>">← Retour aux messages</a>
    <header class="conversation-detail-heading">
        <span class="conversation-avatar" aria-hidden="true"><?= e(strtoupper(substr($conversation['peer_name'], 0, 2))) ?></span>
        <div>
            <h1><?= e($conversation['peer_name']) ?></h1>
            <p><?= $conversation['peer_role'] === 'admin' ? 'Administrateur' : 'Utilisateur' ?></p>
        </div>
    </header>

    <ol class="conversation-thread" aria-label="Messages de la conversation">
        <?php foreach ($conversation['messages'] as $item): ?>
            <?php $ownMessage = (int) $item['sender_id'] === $account->getId(); ?>
            <li class="thread-message<?= $ownMessage ? ' is-own-message' : '' ?>">
                <div class="thread-message-heading">
                    <strong><?= $ownMessage ? 'Vous' : e($item['sender_name']) ?></strong>
                    <time datetime="<?= e($item['created_at']) ?>"><?= e($item['date_label']) ?></time>
                </div>
                <p><?= nl2br(e($item['body'])) ?></p>
            </li>
        <?php endforeach; ?>
    </ol>

    <form class="message-reply-form" method="post" action="<?= e(url('message-send')) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <input type="hidden" name="id" value="<?= e((string) $conversation['id']) ?>">
        <label for="reply-body">Votre message</label>
        <textarea id="reply-body" name="body" rows="4" maxlength="5000" required<?= isset($errors['body']) ? ' aria-invalid="true" aria-describedby="reply-error"' : '' ?>><?= e($body) ?></textarea>
        <?php if (isset($errors['body'])): ?><p class="field-error" id="reply-error" role="alert"><?= e($errors['body']) ?></p><?php endif; ?>
        <div class="message-form-actions">
            <span class="messages-hint">5000 caractères maximum</span>
            <button class="messages-new-button" type="submit"><?php $icon('send'); ?><span>Envoyer</span></button>
        </div>
    </form>
</section>
