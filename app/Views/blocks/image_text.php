<?php
/** @var array $d @var array $block */
use App\Services\BlockRenderer;
use App\Services\Media;

$pos = $d['image_position'] === 'left' ? 'left' : 'right';
$credit = Media::credit((int) $d['image']);
?>
<section class="<?= e(BlockRenderer::sectionClass('split split--image-' . $pos, $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container split__grid">
        <div class="split__content">
            <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($d['eyebrow']) ?></p><?php endif; ?>
            <?php if ($d['heading'] !== ''): ?><h2 class="section-title"><?= e($d['heading']) ?></h2><?php endif; ?>
            <div class="prose"><?= $d['body'] ?></div>
            <?php if ($d['button_label'] !== '' && $d['button_url'] !== ''): ?>
                <p class="split__action"><a class="btn btn--primary" href="<?= e(link_href($d['button_url'])) ?>"><?= e($d['button_label']) ?> <?= icon('arrow-right') ?></a></p>
            <?php endif; ?>
        </div>
        <?php if ($d['image']): ?>
            <figure class="split__figure">
                <div class="split__frame"><?= Media::img((int) $d['image'], '(min-width: 1024px) 50vw, 100vw', 'split__img', true, null, 'lg') ?></div>
                <?php if ($d['caption'] !== '' || $credit !== ''): ?>
                    <figcaption><?= e($d['caption']) ?><?php if ($credit !== ''): ?> <small><?= e($credit) ?></small><?php endif; ?></figcaption>
                <?php endif; ?>
            </figure>
        <?php endif; ?>
    </div>
</section>
