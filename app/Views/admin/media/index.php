<?php
/** @var array $items @var string $q @var string $filter @var \App\Support\Paginator $pager @var int $maxMb */
use App\Services\Media;
?>
<section class="card upload-card">
    <form method="post" action="<?= e(url('/admin/media/upload')) ?>" enctype="multipart/form-data" class="dropzone" data-dropzone>
        <?= csrf_field() ?>
        <?= icon('upload', 'icon icon--xl') ?>
        <label for="m-files"><strong>Bilder hochladen</strong> – Dateien auswählen oder hierher ziehen</label>
        <input id="m-files" type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple required>
        <p class="field__help">JPG, PNG oder WebP, max. <?= $maxMb ?> MB je Datei. Bilder werden automatisch verkleinert, optimiert und von Metadaten (z. B. GPS) bereinigt. SVG und andere Formate werden aus Sicherheitsgründen nicht angenommen.</p>
        <button class="btn btn--primary" type="submit"><?= icon('upload') ?> Hochladen</button>
    </form>
</section>

<div class="toolbar">
    <form class="filters" method="get" action="<?= e(url('/admin/media')) ?>">
        <label class="sr-only" for="mq">Suchen</label>
        <input type="search" id="mq" name="q" value="<?= e($q) ?>" placeholder="Dateiname oder Alternativtext">
        <label class="sr-only" for="mf">Filter</label>
        <select id="mf" name="filter">
            <option value="">Alle Bilder</option>
            <option value="placeholder"<?= $filter === 'placeholder' ? ' selected' : '' ?>>Nur Platzhalterbilder</option>
            <option value="noalt"<?= $filter === 'noalt' ? ' selected' : '' ?>>Ohne Alternativtext</option>
        </select>
        <button class="btn btn--secondary btn--sm" type="submit">Filtern</button>
    </form>
    <span class="muted"><?= $pager->total ?> Bilder</span>
</div>

<?php if (!$items): ?>
    <div class="card"><p class="empty">Keine Bilder gefunden.</p></div>
<?php else: ?>
    <ul class="media-grid">
        <?php foreach ($items as $m): ?>
            <li class="media-tile">
                <a href="<?= e(url('/admin/media/' . $m['id'])) ?>">
                    <img src="<?= e(Media::url($m, 'sm')) ?>" alt="" loading="lazy" width="240" height="180">
                    <span class="media-tile__name"><?= e($m['alt'] !== '' ? $m['alt'] : $m['original_name']) ?></span>
                    <span class="media-tile__meta"><?= (int) $m['width'] ?> × <?= (int) $m['height'] ?> px</span>
                    <?php if ((int) $m['is_placeholder']): ?><span class="badge badge--amber media-tile__badge">Platzhalter</span><?php endif; ?>
                    <?php if ($m['alt'] === ''): ?><span class="badge badge--red media-tile__badge media-tile__badge--2">Alt-Text fehlt</span><?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= \App\Core\View::partial('admin/partials/pagination', ['pager' => $pager, 'query' => array_filter(['q' => $q, 'filter' => $filter])]) ?>
<?php endif; ?>
