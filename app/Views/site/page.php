<?php /** @var array $page @var string $blocksHtml */ ?>
<div class="page page--<?= e($page['template']) ?>">
    <?php if ($page['template'] === 'legal'): ?>
        <div class="legal">
            <div class="container legal__inner">
                <?= $blocksHtml ?>
                <p class="legal__updated">Stand: <?= e(format_date($page['updated_at'])) ?></p>
            </div>
        </div>
    <?php else: ?>
        <?= $blocksHtml ?>
    <?php endif; ?>
    <?php if (trim($blocksHtml) === ''): ?>
        <section class="section">
            <div class="container narrow">
                <h1 class="page-title"><?= e($page['title']) ?></h1>
                <p class="muted">Für diese Seite wurden noch keine Inhalte hinterlegt.</p>
            </div>
        </section>
    <?php endif; ?>
</div>
