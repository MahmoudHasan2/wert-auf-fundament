<?php
/** @var array $d @var array $block @var array $ctx */
use App\Repositories\ProjectRepository;
use App\Repositories\ServiceRepository;
use App\Services\BlockRenderer;
use App\Services\Media;

$filter = 0;
if ($d['show_filter'] && isset($ctx['request'])) {
    $filter = (int) ($ctx['request']->query['bereich'] ?? 0);
}
$all = ProjectRepository::published((int) $d['limit'], (bool) $d['featured_only']);
$projects = $filter ? array_values(array_filter($all, fn ($p) => (int) $p['service_id'] === $filter)) : $all;
if (!$all && $d['hide_when_empty']) {
    return;
}
Media::preload(array_column($projects, 'cover_media_id'));
$used = array_values(array_unique(array_filter(array_map(fn ($p) => (int) $p['service_id'], $all))));
?>
<section class="<?= e(BlockRenderer::sectionClass('projects', $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <?= \App\Core\View::partial('components/section-head', ['d' => $d]) ?>
        <?php if ($d['show_filter'] && count($used) > 1): ?>
            <nav class="filter" aria-label="Referenzen filtern">
                <a class="filter__item<?= $filter === 0 ? ' is-active' : '' ?>" href="?#block-<?= (int) $block['id'] ?>"<?= $filter === 0 ? ' aria-current="true"' : '' ?>>Alle</a>
                <?php foreach ($used as $sid): $s = ServiceRepository::find($sid); if (!$s) { continue; } ?>
                    <a class="filter__item<?= $filter === $sid ? ' is-active' : '' ?>" href="?bereich=<?= (int) $sid ?>#block-<?= (int) $block['id'] ?>"<?= $filter === $sid ? ' aria-current="true"' : '' ?>><?= e($s['title']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <?php if ($projects): ?>
            <div class="cards cards--grid">
                <?php foreach ($projects as $p): ?>
                    <?= \App\Core\View::partial('components/project-card', ['p' => $p]) ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-state__icon" aria-hidden="true"><?= icon('building-2') ?></span>
                <p><?= nl2br(e($d['empty_text'] !== '' ? $d['empty_text'] : 'Derzeit sind keine Referenzen veröffentlicht.')) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>
