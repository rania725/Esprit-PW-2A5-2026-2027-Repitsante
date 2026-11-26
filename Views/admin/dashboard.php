<section class="metrics" aria-label="Indicateurs du tableau de bord">
    <?php foreach ($metrics as [$symbol, $count, $label]): ?>
        <article class="panel metric">
            <span class="metric-icon"><?php $icon($symbol); ?></span>
            <div><strong><?= e($count) ?></strong><p><?= e($label) ?></p></div>
        </article>
    <?php endforeach; ?>
</section>

<div class="dashboard-row" id="coordination">
    <section class="panel pathways" id="patients" aria-labelledby="patients-heading">
        <h2 class="panel-heading" id="patients-heading"><?php $icon('route'); ?> Parcours à organiser</h2>
        <div class="table-scroll">
            <table class="dashboard-table">
                <thead>
                    <tr><th scope="col">Patient</th><th scope="col">Étapes</th><th scope="col">Statut</th><th scope="col">Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pathways as [$id, $steps, $status, $symbol, $color, $detail]): ?>
                        <tr>
                            <th scope="row">
                                <span class="patient-id"><span class="patient-icon"><?php $icon('user'); ?></span><?= e($id) ?></span>
                            </th>
                            <td><?= e($steps) ?></td>
                            <td><span class="pill <?= e($color) ?>"><?php $icon($symbol); ?><?= e($status) ?></span></td>
                            <td><button class="button button-small" type="button" data-dialog="pathway-<?= e($id) ?>">Consulter <?php $icon('chevron'); ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($pathways === []): ?><tr><td colspan="4">Aucun parcours trouvé.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel alerts" aria-labelledby="alerts-heading">
        <h2 class="panel-heading" id="alerts-heading"><?php $icon('bell'); ?> Dernières alertes</h2>
        <?php foreach ($alerts as [$symbol, $heading, $description, $color]): ?>
            <a class="alert-row" href="<?= e(url('admin', ['q' => $color === 'teal' ? 'Radiologie' : 'P-031'])) ?>#planning">
                <span class="alert-icon <?= e($color) ?>"><?php $icon($symbol); ?></span>
                <span class="alert-copy"><strong><?= e($heading) ?></strong><span><?= e($description) ?></span></span>
                <?php $icon('chevron'); ?>
            </a>
        <?php endforeach; ?>
    </section>
</div>

<section class="panel planning" id="planning" aria-labelledby="planning-heading">
    <h2 class="panel-heading" id="planning-heading"><?php $icon('calendar'); ?> Planning de la semaine</h2>
    <div class="week-scroll">
        <div class="week-grid">
            <?php foreach ($week as [$day, $date, $slots]): ?>
                <div class="week-day">
                    <h3><?= e($day) ?><span><?= e($date) ?></span></h3>
                    <ul>
                        <?php foreach ($slots as [$time, $label]): ?>
                            <li><time><?= e($time) ?></time><span><?= e($label) ?></span></li>
                        <?php endforeach; ?>
                        <?php if ($slots === []): ?><li class="empty-day">Aucun créneau</li><?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<p class="demo-note">Données fictives</p>

<?php foreach ($pathways as [$id, $steps, $status, $symbol, $color, $detail]): ?>
    <dialog id="pathway-<?= e($id) ?>" class="pathway-dialog" aria-labelledby="title-<?= e($id) ?>">
        <h2 id="title-<?= e($id) ?>">Parcours <?= e($id) ?></h2>
        <p><strong><?= e($steps) ?></strong></p>
        <p><?= e($detail) ?></p>
        <p><span class="pill <?= e($color) ?>"><?php $icon($symbol); ?><?= e($status) ?></span></p>
        <form method="dialog"><button class="button" type="submit">Fermer</button></form>
    </dialog>
<?php endforeach; ?>
