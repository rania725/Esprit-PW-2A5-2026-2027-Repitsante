<section class="messages-hero" aria-labelledby="messages-title">
    <div>
        <p class="messages-eyebrow">Restons en contact</p>
        <h1 id="messages-title">Mes messages</h1>
        <p class="messages-intro">Échangez simplement avec votre équipe de soins.</p>
    </div>
    <a class="messages-new-button" href="<?= e(url('message-new')) ?>">
        <?php $icon('plus'); ?>
        <span>Nouveau message</span>
    </a>
</section>

<form class="messages-search" action="index.php" method="get" role="search">
    <input type="hidden" name="action" value="messages">
    <label class="visually-hidden" for="conversation-search">Rechercher une conversation</label>
    <button type="submit" aria-label="Lancer la recherche"><?php $icon('search'); ?></button>
    <input id="conversation-search" type="search" name="q" value="<?= e($query) ?>" maxlength="150" placeholder="Rechercher une conversation…">
</form>

<?php if ($query !== ''): ?>
    <p class="messages-search-note">
        Résultats pour « <?= e($query) ?> » · <a href="<?= e(url('messages')) ?>">Effacer la recherche</a>
    </p>
<?php endif; ?>

<?php if ($conversations === []): ?>
    <div class="messages-empty" role="status">
        <h2><?= $query === '' ? 'Aucune conversation pour le moment' : 'Aucune conversation trouvée' ?></h2>
        <p><?= $query === '' ? 'Commence une conversation avec un membre de ton équipe de soins.' : 'Essaie un autre nom ou un mot du dernier message.' ?></p>
    </div>
<?php else: ?>
    <section class="conversation-grid" aria-label="Liste des conversations">
        <?php foreach ($conversations as $conversation): ?>
            <?php $unread = (int) $conversation['unread_count'] > 0; ?>
            <a class="conversation-card" href="<?= e(url('conversation', ['id' => $conversation['id']])) ?>">
                <span class="conversation-card-heading">
                    <span class="conversation-avatar" aria-hidden="true"><?= e(strtoupper(substr($conversation['peer_name'], 0, 2))) ?></span>
                    <span class="conversation-contact">
                        <strong><?= e($conversation['peer_name']) ?></strong>
                        <span><?= $conversation['peer_role'] === 'admin' ? 'Administrateur' : 'Utilisateur' ?></span>
                    </span>
                    <span class="message-status<?= $unread ? ' is-unread' : '' ?>"><?= $unread ? 'Non lu' : 'Lu' ?></span>
                </span>
                <span class="conversation-preview">
                    <?php $icon('message'); ?>
                    <span><?= e($conversation['last_message'] ?? '') ?></span>
                </span>
                <span class="conversation-time">
                    <?php $icon('clock'); ?>
                    <time datetime="<?= e($conversation['last_message_at']) ?>"><?= e($conversation['last_message_label']) ?></time>
                </span>
                <span class="conversation-open">Ouvrir la conversation <span aria-hidden="true">→</span></span>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
