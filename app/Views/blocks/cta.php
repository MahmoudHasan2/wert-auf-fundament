<?php
/** @var array $d @var array $block */
use App\Services\Media;

$variant = in_array($d['variant'], ['dark', 'red', 'light'], true) ? $d['variant'] : 'dark';
$hasImage = $d['image'] && Media::find((int) $d['image']);
$phone = setting('phone');
?>
<section class="cta cta--<?= e($variant) ?><?= $hasImage ? ' cta--image' : '' ?>" id="block-<?= (int) $block['id'] ?>">
    <?php if ($hasImage): ?><div class="cta__media"><?= Media::img((int) $d['image'], '100vw', 'cta__img', true, '', 'lg') ?></div><?php endif; ?>
    <div class="container cta__inner">
        <div class="cta__content">
            <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow<?= $variant !== 'light' ? ' eyebrow--light' : '' ?>"><?= e($d['eyebrow']) ?></p><?php endif; ?>
            <h2 class="cta__title"><?= e($d['heading']) ?></h2>
            <?php if ($d['text'] !== ''): ?><p class="cta__text"><?= nl2br(e($d['text'])) ?></p><?php endif; ?>
        </div>
        <div class="cta__actions">
            <?php if ($d['button1_label'] !== '' && $d['button1_url'] !== ''): ?>
                <a class="btn <?= $variant === 'red' ? 'btn--white' : 'btn--primary' ?> btn--lg" href="<?= e(link_href($d['button1_url'])) ?>"><?= e($d['button1_label']) ?> <?= icon('arrow-right') ?></a>
            <?php endif; ?>
            <?php if ($d['button2_label'] !== '' && $d['button2_url'] !== ''): ?>
                <a class="btn <?= $variant === 'light' ? 'btn--outline' : 'btn--ghost-light' ?> btn--lg" href="<?= e(link_href($d['button2_url'])) ?>"><?= e($d['button2_label']) ?></a>
            <?php endif; ?>
            <?php if ($d['show_phone'] && $phone !== ''): ?>
                <a class="cta__phone" href="<?= e(tel_href($phone)) ?>"><?= icon('phone') ?><span><small>Direkt anrufen</small><?= e($phone) ?></span></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="cta__stripes" aria-hidden="true"><span></span><span></span><span></span></div>
</section>
