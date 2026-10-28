<?php
/** @var array $g @var array $items @var array $usage */
use App\Services\Media;

$last = count($items) - 1;
?>
<p class="breadcrumb-admin"><a href="<?= e(url('/admin/galleries')) ?>"><?= icon('arrow-left') ?> Alle Galerien</a></p>
<?php if ($usage): ?><div class="alert alert--info"><?= icon('info') ?><p>Verwendet in: <?php foreach ($usage as $i => $u): ?><?= $i ? ', ' : '' ?><a href="<?= e(url($u['url'])) ?>"><?= e($u['label']) ?></a><?php endforeach; ?></p></div><?php endif; ?>

<section class="card">
    <div class="card__head">
        <h2 class="card__title">Bilder (<?= count($items) ?>)</h2>
        <form method="post" action="<?= e(url('/admin/galleries/' . $g['id'] . '/items')) ?>" class="inline-form" data-gallery-add>
            <?= csrf_field() ?>
            <div data-gallery-ids></div>
            <label class="sr-only" for="ga-id">Bild-ID</label>
            <input type="number" min="1" id="ga-id" name="media_id" placeholder="Bild-ID" class="media-field__id">
            <button type="button" class="btn btn--primary btn--sm" data-media-pick-multi><?= icon('plus') ?> Bilder hinzufügen</button>
            <noscript><button type="submit" class="btn btn--primary btn--sm">Hinzufügen</button></noscript>
        </form>
    </div>
    <?php if (!$items): ?>
        <p class="empty">Diese Galerie enthält noch keine Bilder.</p>
    <?php else: ?>
    <form method="post" action="<?= e(url('/admin/galleries/' . $g['id'])) ?>" id="gallery-form"><?= csrf_field() ?></form>
    <ol class="gallery-admin">
        <?php foreach ($items as $i => $it): $m = Media::find((int) $it['media_id']); if (!$m) { continue; } ?>
            <li class="gallery-admin__item" id="item-<?= (int) $it['id'] ?>">
                <img src="<?= e(Media::url($m, 'sm')) ?>" alt="<?= e($m['alt']) ?>" loading="lazy">
                <div class="gallery-admin__body">
                    <span class="muted small"><?= $i === 0 ? '<strong>Titelbild der Galerie</strong> · ' : '' ?>Position <?= $i + 1 ?> · <a href="<?= e(url('/admin/media/' . $m['id'])) ?>">Bildangaben</a></span>
                    <label class="sr-only" for="cap-<?= (int) $it['id'] ?>">Bildunterschrift</label>
                    <input id="cap-<?= (int) $it['id'] ?>" type="text" form="gallery-form" name="captions[<?= (int) $it['id'] ?>]" value="<?= e($it['caption']) ?>" maxlength="255" placeholder="Bildunterschrift (optional, sonst Standard aus Mediathek)">
                </div>
                <div class="gallery-admin__actions">
                    <?php foreach ([['first', 'chevrons-up', 'Als erstes Bild', $i === 0], ['up', 'arrow-up', 'Nach vorne', $i === 0], ['down', 'arrow-down', 'Nach hinten', $i === $last]] as [$dir, $ic, $label, $disabled]): ?>
                        <form method="post" action="<?= e(url('/admin/galleries/' . $g['id'] . '/items/' . $it['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="icon-btn" type="submit" title="<?= e($label) ?>"<?= $disabled ? ' disabled' : '' ?>><?= icon($dir === 'first' ? 'arrow-up' : $ic) ?><?= $dir === 'first' ? '<span class="icon-btn__sup">1</span>' : '' ?><span class="sr-only"><?= e($label) ?></span></button></form>
                    <?php endforeach; ?>
                    <form method="post" action="<?= e(url('/admin/galleries/' . $g['id'] . '/items/' . $it['id'] . '/delete')) ?>" data-confirm="Bild aus der Galerie entfernen?"><?= csrf_field() ?><button class="icon-btn icon-btn--danger" type="submit" title="Entfernen"><?= icon('x') ?><span class="sr-only">Aus Galerie entfernen</span></button></form>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>
</section>

<div class="grid-2">
    <section class="card form-stack">
        <h2 class="card__title">Einstellungen</h2>
        <div class="field"><label for="g-name">Name der Galerie</label><input id="g-name" type="text" form="gallery-form" name="name" maxlength="160" value="<?= e($g['name']) ?>"></div>
        <?php if (!$items): ?><form method="post" action="<?= e(url('/admin/galleries/' . $g['id'])) ?>" id="gallery-form"><?= csrf_field() ?></form><?php endif; ?>
        <button class="btn btn--primary" type="submit" form="gallery-form"><?= icon('save') ?> Name &amp; Bildunterschriften speichern</button>
    </section>
    <section class="card">
        <h2 class="card__title">Galerie löschen</h2>
        <?php if ($usage): ?>
            <p class="muted small">Die Galerie wird noch verwendet und kann nicht gelöscht werden.</p>
        <?php else: ?>
            <form method="post" action="<?= e(url('/admin/galleries/' . $g['id'] . '/delete')) ?>" data-confirm="Galerie löschen? Die Bilder bleiben in der Mediathek."><?= csrf_field() ?><button class="btn btn--danger" type="submit"><?= icon('trash-2') ?> Galerie löschen</button></form>
        <?php endif; ?>
    </section>
</div>
