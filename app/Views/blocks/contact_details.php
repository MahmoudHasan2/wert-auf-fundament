<?php
/** @var array $d @var array $block */
use App\Services\BlockRenderer;
?>
<section class="<?= e(BlockRenderer::sectionClass('contact-details', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => ['eyebrow' => $d['eyebrow'], 'heading' => $d['heading'], 'intro' => $d['text']]]) ?>
        <?= \App\Core\View::partial('components/contact-cards', ['d' => $d]) ?>
    </div>
</section>
