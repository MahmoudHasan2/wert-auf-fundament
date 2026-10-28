<?php
/** @var array $p */
use App\Repositories\ProjectRepository;
use App\Services\Media;
?>
<article class="card card--project">
    <a class="card__link" href="<?= e(url(ProjectRepository::path($p))) ?>">
        <div class="card__media">
            <?php if ($p['cover_media_id']): ?>
                <?= Media::img((int) $p['cover_media_id'], '(min-width: 1024px) 30vw, (min-width: 640px) 50vw, 100vw', 'card__img', true, '', 'md') ?>
            <?php else: ?>
                <div class="card__placeholder"><?= icon('building-2', 'icon icon--xl') ?></div>
            <?php endif; ?>
        </div>
        <div class="card__body">
            <?php if (!empty($p['service_title'])): ?><p class="card__kicker"><?= e($p['service_title']) ?></p><?php endif; ?>
            <h3 class="card__title"><?= e($p['title']) ?></h3>
            <?php if ($p['location'] !== ''): ?><p class="card__meta"><?= icon('map-pin') ?> <?= e($p['location']) ?></p><?php endif; ?>
            <span class="card__more">Projekt ansehen <?= icon('arrow-right') ?></span>
        </div>
    </a>
</article>
