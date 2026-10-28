<?php
/** @var array $d @var array $block */
use App\Services\BlockRenderer;
use App\Services\Media;

if (!$d['items'] && $d['heading'] === '') {
    return;
}
$hasImage = $d['image'] && Media::find((int) $d['image']);
$credit = $hasImage ? Media::credit((int) $d['image']) : '';
?>
<section class="<?= e(BlockRenderer::sectionClass('checklist' . ($hasImage ? ' checklist--image' : ''), $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container checklist__grid">
        <div class="checklist__intro">
            <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
            <?php if ($hasImage): ?>
                <figure class="checklist__figure">
                    <?= Media::img((int) $d['image'], '(min-width: 1024px) 40vw, 100vw', 'checklist__img', true, null, 'md') ?>
                    <?php if ($credit !== ''): ?><figcaption><small><?= e($credit) ?></small></figcaption><?php endif; ?>
                </figure>
            <?php endif; ?>
        </div>
        <ul class="checklist__list">
            <?php foreach ($d['items'] as $item): ?>
                <li class="checklist__item">
                    <span class="checklist__icon" aria-hidden="true"><?= icon('check') ?></span>
                    <div>
                        <?php if ($item['title'] !== ''): ?><h3 class="checklist__title"><?= e($item['title']) ?></h3><?php endif; ?>
                        <?php if ($item['text'] !== ''): ?><p><?= nl2br(e($item['text'])) ?></p><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
