<section class="panel users-panel" aria-labelledby="users-heading">
    <div class="section-toolbar">
        <h2 class="panel-heading" id="users-heading"><?php $icon('users'); ?> Liste des utilisateurs</h2>
        <a class="button" href="<?= e(url('user-create')) ?>"><?php $icon('plus'); ?> Ajouter un utilisateur</a>
    </div>
    <?php if ($query !== ''): ?>
        <p class="search-note">Résultats pour « <?= e($query) ?> » · <a href="<?= e(url('users')) ?>">Effacer la recherche</a></p>
    <?php endif; ?>
    <div class="table-scroll">
        <table class="dashboard-table user-table">
            <thead>
                <tr><th scope="col">Nom d’utilisateur</th><th scope="col">Adresse e-mail</th><th scope="col">Rôle</th><th scope="col">Date de création</th><th scope="col">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $member): ?>
                    <?php $isAdmin = $member->getRole() === 'admin'; ?>
                    <tr>
                        <th scope="row">
                            <span class="patient-id"><span class="patient-icon"><?php $icon('user'); ?></span><?= e($member->getUsername()) ?></span>
                        </th>
                        <td><?= e($member->getEmail()) ?></td>
                        <td><span class="pill <?= $isAdmin ? 'pink' : 'teal' ?>"><?= $isAdmin ? 'Administrateur' : 'Utilisateur' ?></span></td>
                        <td><?= e(date('d/m/Y', strtotime($member->getCreatedAt()))) ?></td>
                        <td>
                            <div class="table-actions">
                                <a class="text-action" href="<?= e(url('user-edit', ['id' => $member->getId()])) ?>"><?php $icon('edit'); ?> Modifier</a>
                                <?php if ($member->getId() !== $account->getId()): ?>
                                    <a class="text-action danger" href="<?= e(url('user-delete', ['id' => $member->getId()])) ?>"><?php $icon('trash'); ?> Supprimer</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($users === []): ?><tr><td colspan="5">Aucun utilisateur trouvé.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
