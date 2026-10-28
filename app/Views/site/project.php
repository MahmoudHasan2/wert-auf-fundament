<?php
/** @var array $project @var ?array $service @var array $images @var array $more @var array $meta @var ?array $overview */
use App\Repositories\ProjectRepository;
use App\Repositories\ServiceRepository;
use App\Services\Media;

?>
<section class="hero hero--medium<?= $project['cover_media_id'] ? '' : ' hero--plain' ?>">
    <?php if ($project['cover_media_id']): ?>
        <div class="hero__media"><?= Media::img((int) $project['cover_media_id'], '100vw', 'hero__img', false, '', 'lg') ?></div>
    <?php endif; ?>
    <div class="container hero__inner">
        <?= \App\Core\View::partial('components/breadcrumbs', ['crumbs' => $meta['breadcrumbs'], 'variant' => 'hero']) ?>
        <p class="eyebrow eyebrow--light">Referenz<?= $service ? ' · ' . e($service['title']) : '' ?></p>
        <h1 class="hero__title"><?= e($project['title']) ?></h1>
        <?php if (trim((string) $project['summary']) !== ''): ?><p class="hero__text"><?= nl2br(e($project['summary'])) ?></p><?php endif; ?>
    </div>
    <div class="hero__stripes" aria-hidden="true"><span></span><span></span><span></span></div>
</section>

<section class="section section--white">
    <div class="container project">
        <div class="project__body prose">
            <?= $project['body'] ?: '<p>' . e($project['summary']) . '</p>' ?>
        </div>
        <aside class="project__facts" aria-label="Projektdaten">
            <dl>
                <?php if ($service): ?><div><dt>Leistungsbereich</dt><dd><a href="<?= e(url(ServiceRepository::path($service))) ?>"><?= e($service['title']) ?></a></dd></div><?php endif; ?>
                <?php if ($project['location'] !== ''): ?><div><dt>Ort</dt><dd><?= e($project['location']) ?></dd></div><?php endif; ?>
                <?php if ($project['period'] !== ''): ?><div><dt>Zeitraum</dt><dd><?= e($project['period']) ?></dd></div><?php endif; ?>
            </dl>
            <a class="btn btn--primary btn--block" href="<?= e(url('/kontakt')) ?>#kontaktformular">Ähnliches Projekt anfragen</a>
        </aside>
    </div>
</section>

<?php if ($images): ?>
<section class="section section--sand">
    <div class="container">
        <div class="section-head"><p class="eyebrow">Bildergalerie</p><h2 class="section-title">Eindrücke vom Projekt</h2></div>
        <?= \App\Core\View::partial('components/gallery-grid', ['items' => $images, 'layout' => 'grid']) ?>
    </div>
</section>
<?php endif; ?>

<?php if ($more): ?>
<section class="section section--white">
    <div class="container">
        <div class="section-head section-head--row">
            <div><p class="eyebrow">Weitere Referenzen</p><h2 class="section-title">Mehr aus unserer Arbeit</h2></div>
            <?php if ($overview): ?><a class="link-arrow" href="<?= e(url('/' . $overview['slug'])) ?>">Alle Referenzen <?= icon('arrow-right') ?></a><?php endif; ?>
        </div>
        <div class="cards cards--grid">
            <?php foreach (array_slice($more, 0, 3) as $p): ?>
                <?= \App\Core\View::partial('components/project-card', ['p' => $p]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
