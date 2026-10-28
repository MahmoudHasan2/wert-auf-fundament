<?php
/** @var array $d @var array $block */
use App\Services\BlockRenderer;

if (!$d['items']) {
    return;
}
$cols = in_array($d['columns'], ['2', '3', '4'], true) ? $d['columns'] : '3';
?>
<section class="<?= e(BlockRenderer::sectionClass('features', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <ul class="features__grid features__grid--<?= e($cols) ?>">
            <?php foreach ($d['items'] as $item): ?>
                <li class="feature">
                    <?php if ($item['icon'] !== ''): ?><span class="feature__icon" aria-hidden="true"><?= icon($item['icon']) ?></span><?php endif; ?>
                    <h3 class="feature__title"><?= e($item['title']) ?></h3>
                    <?php if ($item['text'] !== ''): ?><p class="feature__text"><?= nl2br(e($item['text'])) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
