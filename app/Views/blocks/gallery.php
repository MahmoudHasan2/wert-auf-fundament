<?php
/** @var array $d @var array $block */
use App\Repositories\ContentRepository;
use App\Services\BlockRenderer;

$items = $d['gallery'] ? ContentRepository::galleryItems((int) $d['gallery']) : [];
if (!$items) {
    return;
}
?>
<section class="<?= e(BlockRenderer::sectionClass('gallery-block', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <?= \App\Core\View::partial('components/gallery-grid', ['items' => $items, 'layout' => $d['layout'] === 'mosaic' ? 'mosaic' : 'grid']) ?>
    </div>
</section>
