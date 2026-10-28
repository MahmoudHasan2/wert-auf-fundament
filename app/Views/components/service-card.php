<?php
/** @var array $s @var int $i @var string $linkLabel */
use App\Repositories\ServiceRepository;
use App\Services\Media;

$href = url(ServiceRepository::path($s));
$large = !empty($large);
?>
<article class="card card--service<?= $large ? ' card--large' : '' ?>">
    <a class="card__link" href="<?= e($href) ?>">
        <div class="card__media">
            <?php if ($s['image_media_id']): ?>
                <?= Media::img((int) $s['image_media_id'], $large ? '(min-width: 1024px) 60vw, 100vw' : '(min-width: 1024px) 30vw, (min-width: 640px) 50vw, 100vw', 'card__img', true, '', 'md') ?>
            <?php else: ?>
                <div class="card__placeholder"><?= icon($s['icon'] ?: 'hard-hat', 'icon icon--xl') ?></div>
            <?php endif; ?>
            <span class="card__index" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span>
        </div>
        <div class="card__body">
            <h3 class="card__title"><?= e($s['title']) ?></h3>
            <?php if (trim((string) $s['teaser']) !== ''): ?><p class="card__text"><?= e(str_excerpt($s['teaser'], $large ? 260 : 170)) ?></p><?php endif; ?>
            <span class="card__more"><?= e($linkLabel ?: 'Mehr erfahren') ?> <?= icon('arrow-right') ?></span>
        </div>
    </a>
</article>
