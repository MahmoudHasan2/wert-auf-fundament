<?php /** @var array $crumbs @var string $variant */ ?>
<nav class="breadcrumbs breadcrumbs--<?= e($variant ?? 'bar') ?>" aria-label="Brotkrumennavigation">
    <?php if (($variant ?? 'bar') === 'bar'): ?><div class="container"><?php endif; ?>
    <ol>
        <?php foreach ($crumbs as $i => [$label, $path]): ?>
            <li>
                <?php if ($i === array_key_last($crumbs)): ?>
                    <span aria-current="page"><?= e($label) ?></span>
                <?php else: ?>
                    <a href="<?= e(url($path)) ?>"><?= e($label) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
    <?php if (($variant ?? 'bar') === 'bar'): ?></div><?php endif; ?>
</nav>
