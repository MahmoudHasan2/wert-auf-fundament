<?php
/** @var array $d @var array $block */
use App\Repositories\ContentRepository;
use App\Services\BlockRenderer;

$faqs = ContentRepository::faqs((int) $d['category']);
if (!$faqs) {
    return;
}
$schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => [
    '@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $f['answer'])],
], $faqs)];
?>
<section class="<?= e(BlockRenderer::sectionClass('faq', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container faq__grid">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <div class="faq__list">
            <?php foreach ($faqs as $f): ?>
                <details class="faq__item">
                    <summary class="faq__question"><span><?= e($f['question']) ?></span><?= icon('plus', 'icon faq__icon') ?></summary>
                    <div class="faq__answer prose"><?= $f['answer'] ?></div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</section>
