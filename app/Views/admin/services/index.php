<?php
/** @var array $tree */
use App\Repositories\ServiceRepository;
use App\Services\Media;

$row = function (array $s, bool $child, int $i, int $count): string {
    ob_start(); ?>
    <tr id="service-<?= (int) $s['id'] ?>" class="<?= $child ? 'is-child' : '' ?>">
        <td data-label="Leistung">
            <div class="cell-media">
                <?php $m = Media::find((int) $s['image_media_id']); if ($m): ?><img src="<?= e(Media::url($m, 'sm')) ?>" alt="" width="64" height="44"><?php else: ?><span class="cell-media__ph"><?= icon($s['icon'] ?: 'hard-hat') ?></span><?php endif; ?>
                <div><?= $child ? '<span class="muted">↳ </span>' : '' ?><a href="<?= e(url('/admin/services/' . $s['id'])) ?>"><strong><?= e($s['title']) ?></strong></a>
                    <?php if ((int) $s['is_featured']): ?><span class="badge badge--red">Startseite</span><?php endif; ?>
                    <?php if ($s['admin_note'] !== ''): ?><span class="badge badge--amber" title="<?= e($s['admin_note']) ?>">Prüfvermerk</span><?php endif; ?>
                    <br><code class="small"><?= e(ServiceRepository::path($s)) ?></code></div>
            </div>
        </td>
        <td data-label="Status"><span class="pill pill--<?= e($s['status']) ?>"><?= e(status_label($s['status'])) ?></span></td>
        <td data-label="Reihenfolge" class="table__order">
            <form method="post" action="<?= e(url('/admin/services/' . $s['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="icon-btn" type="submit"<?= $i === 0 ? ' disabled' : '' ?> title="Nach oben"><?= icon('arrow-up') ?><span class="sr-only">Nach oben</span></button></form>
            <form method="post" action="<?= e(url('/admin/services/' . $s['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="icon-btn" type="submit"<?= $i === $count - 1 ? ' disabled' : '' ?> title="Nach unten"><?= icon('arrow-down') ?><span class="sr-only">Nach unten</span></button></form>
        </td>
        <td class="table__actions">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/services/' . $s['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/services/' . $s['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau</a>
            <?php if (!$child): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/services/create?parent=' . $s['id'])) ?>"><?= icon('plus') ?> Unterleistung</a><?php endif; ?>
        </td>
    </tr>
    <?php return (string) ob_get_clean();
};
?>
<div class="toolbar">
    <p class="muted">Hauptleistungen erscheinen in der Leistungsübersicht, im Menü und im Fußbereich. Unterleistungen erhalten eine eigene Seite unter der Hauptleistung.</p>
    <a class="btn btn--primary" href="<?= e(url('/admin/services/create')) ?>"><?= icon('plus') ?> Neue Leistung</a>
</div>
<div class="card card--flush">
    <?php if (!$tree): ?>
        <p class="empty">Noch keine Leistungen angelegt.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Leistung</th><th>Status</th><th>Reihenfolge</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
        <tbody>
        <?php foreach ($tree as $i => $node): ?>
            <?= $row($node['service'], false, $i, count($tree)) ?>
            <?php foreach ($node['children'] as $j => $c): ?><?= $row($c, true, $j, count($node['children'])) ?><?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
