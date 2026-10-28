<?php
/** @var array $projects */
use App\Repositories\ProjectRepository;
use App\Services\Media;

$count = count($projects);
?>
<div class="toolbar">
    <p class="muted">Referenzen erscheinen auf der Seite „Referenzen“ und – wenn hervorgehoben – auf der Startseite. Bitte nur tatsächlich ausgeführte und zur Veröffentlichung freigegebene Projekte eintragen.</p>
    <a class="btn btn--primary" href="<?= e(url('/admin/projects/create')) ?>"><?= icon('plus') ?> Neue Referenz</a>
</div>
<div class="card card--flush">
    <?php if (!$projects): ?>
        <div class="empty empty--big">
            <?= icon('building-2', 'icon icon--xl') ?>
            <p><strong>Noch keine Referenzen.</strong></p>
            <p>Solange keine Referenz veröffentlicht ist, zeigt die Website einen neutralen Hinweistext an.</p>
            <a class="btn btn--primary" href="<?= e(url('/admin/projects/create')) ?>">Erste Referenz anlegen</a>
        </div>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Referenz</th><th>Leistungsbereich</th><th>Bilder</th><th>Status</th><th>Reihenfolge</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
        <tbody>
        <?php foreach ($projects as $i => $p): $m = Media::find((int) $p['cover_media_id']); ?>
            <tr>
                <td data-label="Referenz"><div class="cell-media"><?php if ($m): ?><img src="<?= e(Media::url($m, 'sm')) ?>" alt="" width="64" height="44"><?php else: ?><span class="cell-media__ph"><?= icon('image') ?></span><?php endif; ?>
                    <div><a href="<?= e(url('/admin/projects/' . $p['id'])) ?>"><strong><?= e($p['title']) ?></strong></a><?php if ((int) $p['is_featured']): ?> <span class="badge badge--red">Hervorgehoben</span><?php endif; ?><br><code class="small"><?= e(ProjectRepository::path($p)) ?></code></div></div></td>
                <td data-label="Bereich"><?= e($p['service_title'] ?? '–') ?></td>
                <td data-label="Bilder"><?= (int) $p['image_count'] ?></td>
                <td data-label="Status"><span class="pill pill--<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
                <td data-label="Reihenfolge" class="table__order">
                    <form method="post" action="<?= e(url('/admin/projects/' . $p['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="icon-btn" type="submit"<?= $i === 0 ? ' disabled' : '' ?> title="Nach oben"><?= icon('arrow-up') ?><span class="sr-only">Nach oben</span></button></form>
                    <form method="post" action="<?= e(url('/admin/projects/' . $p['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="icon-btn" type="submit"<?= $i === $count - 1 ? ' disabled' : '' ?> title="Nach unten"><?= icon('arrow-down') ?><span class="sr-only">Nach unten</span></button></form>
                </td>
                <td class="table__actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/projects/' . $p['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/projects/' . $p['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
