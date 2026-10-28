<?php
/** @var array $service @var ?array $parent @var string $blocksHtml @var array $children @var array $related @var array $meta */
use App\Repositories\ServiceRepository;
use App\Services\Media;

$credit = Media::credit((int) $service['image_media_id']);
?>
<section class="hero hero--medium<?= $service['image_media_id'] ? '' : ' hero--plain' ?>">
    <?php if ($service['image_media_id']): ?>
        <div class="hero__media"><?= Media::img((int) $service['image_media_id'], '100vw', 'hero__img', false, '', 'lg') ?></div>
    <?php endif; ?>
    <div class="container hero__inner">
        <?= \App\Core\View::partial('components/breadcrumbs', ['crumbs' => $meta['breadcrumbs'], 'variant' => 'hero']) ?>
        <p class="eyebrow eyebrow--light"><?= $parent ? e($parent['title']) : 'Leistungen' ?></p>
        <h1 class="hero__title"><?= e($service['title']) ?></h1>
        <?php if (trim((string) $service['intro']) !== ''): ?>
            <p class="hero__text"><?= nl2br(e($service['intro'])) ?></p>
        <?php endif; ?>
        <div class="hero__actions">
            <a class="btn btn--primary" href="<?= e(link_href(setting('header_cta_url', '/kontakt'))) ?>"><?= e(setting('header_cta_label', 'Projekt anfragen')) ?> <?= icon('arrow-right') ?></a>
            <?php if (setting('phone') !== ''): ?>
                <a class="btn btn--ghost-light" href="<?= e(tel_href(setting('phone'))) ?>"><?= icon('phone') ?> <?= e(setting('phone')) ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($credit !== ''): ?><p class="hero__credit"><?= e($credit) ?></p><?php endif; ?>
    <div class="hero__stripes" aria-hidden="true"><span></span><span></span><span></span></div>
</section>

<?= $blocksHtml ?>

<?php if ($children): ?>
    <section class="section section--white">
        <div class="container">
            <div class="section-head"><p class="eyebrow">Im Überblick</p><h2 class="section-title">Leistungen im Bereich <?= e($service['title']) ?></h2></div>
            <div class="cards cards--grid">
                <?php foreach ($children as $i => $c): ?>
                    <?= \App\Core\View::partial('components/service-card', ['s' => $c, 'i' => $i, 'linkLabel' => 'Mehr erfahren']) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($related): ?>
    <section class="section section--sand related">
        <div class="container">
            <div class="section-head"><p class="eyebrow">Weitere Leistungen</p><h2 class="section-title">Das könnte Sie auch interessieren</h2></div>
            <ul class="related__list">
                <?php foreach ($related as $r): ?>
                    <li>
                        <a class="related__link" href="<?= e(url(ServiceRepository::path($r))) ?>">
                            <span class="related__icon"><?= icon($r['icon'] ?: 'hard-hat') ?></span>
                            <span class="related__text"><strong><?= e($r['title']) ?></strong><?php if ($r['teaser']): ?><span><?= e(str_excerpt($r['teaser'], 110)) ?></span><?php endif; ?></span>
                            <?= icon('arrow-right', 'icon related__arrow') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endif; ?>
