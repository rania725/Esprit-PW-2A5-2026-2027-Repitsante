<section class="panel admin-form" aria-labelledby="delete-heading">
    <h2 class="panel-heading" id="delete-heading"><?php $icon('trash'); ?> Confirmer la suppression</h2>
    <p>Supprimer le compte <strong><?= e($target->getUsername()) ?></strong> (<?= e($target->getEmail()) ?>) ?</p>
    <form class="button-row" method="post" action="<?= e(url('user-delete', ['id' => $target->getId()])) ?>">
        <?php require __DIR__ . '/../layouts/csrf.php'; ?>
        <button class="button button-danger" type="submit">Supprimer le compte</button>
        <a class="button button-outline" href="<?= e(url('users')) ?>">Annuler</a>
    </form>
</section>
