<?php
/** @var array $d @var array $block */
use App\Services\BlockRenderer;

if (!$d['items']) {
    return;
}
?>
<section class="<?= e(BlockRenderer::sectionClass('steps', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <ol class="steps__list">
            <?php foreach ($d['items'] as $i => $item): ?>
                <li class="step">
                    <span class="step__num" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
                    <h3 class="step__title"><?= e($item['title']) ?></h3>
                    <?php if ($item['text'] !== ''): ?><p class="step__text"><?= nl2br(e($item['text'])) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>
