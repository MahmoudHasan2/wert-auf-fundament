<?php
/** @var array $items  (gallery_items rows joined with media) @var string $layout */
use App\Services\Media;

Media::preload(array_column($items, 'media_id'));
?>
<ul class="gallery gallery--<?= e($layout) ?>" data-lightbox>
    <?php foreach ($items as $it): ?>
        <?php $m = Media::find((int) $it['media_id']); if (!$m) { continue; } ?>
        <?php $caption = $it['caption'] !== '' ? $it['caption'] : ($it['media_caption'] ?? ''); $credit = Media::credit((int) $m['id']); ?>
        <li class="gallery__item">
            <figure>
                <a class="gallery__link" href="<?= e(Media::url($m, 'full')) ?>" data-caption="<?= e(trim($caption . ($credit !== '' ? ' – ' . $credit : ''), ' –')) ?>">
                    <?= Media::img((int) $m['id'], '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', 'gallery__img', true, null, 'md') ?>
                    <span class="sr-only">Bild vergrößern</span>
                </a>
                <?php if ($caption !== '' || $credit !== ''): ?>
                    <figcaption><?= e($caption) ?><?php if ($credit !== ''): ?> <small><?= e($credit) ?></small><?php endif; ?></figcaption>
                <?php endif; ?>
            </figure>
        </li>
    <?php endforeach; ?>
</ul>
