<?php
/** @var array $d @var array $block */
use App\Services\BlockRenderer;

if (!$d['items']) {
    return;
}
?>
<section class="<?= e(BlockRenderer::sectionClass('stats', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <dl class="stats__grid">
            <?php foreach ($d['items'] as $item): ?>
                <div class="stat"><dt class="stat__label"><?= e($item['label']) ?></dt><dd class="stat__value"><?= e($item['value']) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <?php if ($d['note'] !== ''): ?><p class="stats__note"><?= e($d['note']) ?></p><?php endif; ?>
    </div>
</section>
