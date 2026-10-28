<?php
/** @var array $d @var array $block @var array $ctx */
use App\Services\Media;

$variant = in_array($d['variant'], ['large', 'medium', 'compact'], true) ? $d['variant'] : 'medium';
$hasImage = $variant !== 'compact' && $d['image'] && Media::find((int) $d['image']);
$isFirst = ($ctx['index'] ?? 0) === 0;
$tag = $isFirst ? 'h1' : 'h2';
$credit = $hasImage ? Media::credit((int) $d['image']) : '';
?>
<section class="hero hero--<?= e($variant) ?><?= $hasImage ? '' : ' hero--plain' ?>" id="block-<?= (int) $block['id'] ?>">
    <?php if ($hasImage): ?>
        <div class="hero__media"><?= Media::img((int) $d['image'], '100vw', 'hero__img', !$isFirst, '', 'lg') ?></div>
    <?php endif; ?>
    <div class="container hero__inner">
        <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow<?= $hasImage ? ' eyebrow--light' : '' ?>"><?= e($d['eyebrow']) ?></p><?php endif; ?>
        <<?= $tag ?> class="hero__title"><?= e($d['title']) ?></<?= $tag ?>>
        <?php if ($d['text'] !== ''): ?><p class="hero__text"><?= nl2br(e($d['text'])) ?></p><?php endif; ?>
        <?php if (($d['button1_label'] !== '' && $d['button1_url'] !== '') || ($d['button2_label'] !== '' && $d['button2_url'] !== '')): ?>
            <div class="hero__actions">
                <?php if ($d['button1_label'] !== '' && $d['button1_url'] !== ''): ?>
                    <a class="btn btn--primary" href="<?= e(link_href($d['button1_url'])) ?>"><?= e($d['button1_label']) ?> <?= icon('arrow-right') ?></a>
                <?php endif; ?>
                <?php if ($d['button2_label'] !== '' && $d['button2_url'] !== ''): ?>
                    <a class="btn <?= $hasImage ? 'btn--ghost-light' : 'btn--outline' ?>" href="<?= e(link_href($d['button2_url'])) ?>"><?= e($d['button2_label']) ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($credit !== ''): ?><p class="hero__credit"><?= e($credit) ?></p><?php endif; ?>
    <?php if ($variant !== 'compact'): ?><div class="hero__stripes" aria-hidden="true"><span></span><span></span><span></span></div><?php endif; ?>
</section>
