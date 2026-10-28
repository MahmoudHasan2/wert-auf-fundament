<?php
/** @var array $d @var array $block @var array $ctx */
use App\Repositories\ServiceRepository;
use App\Services\BlockRenderer;
use App\Services\Media;

$services = match ($d['source']) {
    'featured' => ServiceRepository::featured(),
    'children' => isset($ctx['service']) ? ServiceRepository::children((int) $ctx['service']['id']) : [],
    'manual'   => ServiceRepository::byIds((array) $d['services']),
    default    => ServiceRepository::publishedTop(),
};
if (!$services) {
    return;
}
Media::preload(array_column($services, 'image_media_id'));
$layout = in_array($d['layout'], ['grid', 'feature', 'compact'], true) ? $d['layout'] : 'grid';
?>
<section class="<?= e(BlockRenderer::sectionClass('services', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <?php if ($layout === 'compact'): ?>
            <ul class="service-list">
                <?php foreach ($services as $s): ?>
                    <li>
                        <a class="service-list__link" href="<?= e(url(ServiceRepository::path($s))) ?>">
                            <span class="service-list__icon" aria-hidden="true"><?= icon($s['icon'] ?: 'hard-hat') ?></span>
                            <span class="service-list__text"><strong><?= e($s['title']) ?></strong><?php if ($s['teaser']): ?><span><?= e(str_excerpt($s['teaser'], 140)) ?></span><?php endif; ?></span>
                            <?= icon('arrow-right', 'icon service-list__arrow') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="cards cards--<?= e($layout) ?>">
                <?php foreach ($services as $i => $s): ?>
                    <?= \App\Core\View::partial('components/service-card', ['s' => $s, 'i' => $i, 'linkLabel' => $d['link_label'], 'large' => $layout === 'feature' && $i === 0]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
