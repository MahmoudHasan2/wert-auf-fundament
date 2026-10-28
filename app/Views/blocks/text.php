<?php
/** @var array $d @var array $block @var array $ctx */
use App\Services\BlockRenderer;

$layout = in_array($d['layout'], ['narrow', 'split', 'wide'], true) ? $d['layout'] : 'narrow';
$isLegal = ($ctx['page']['template'] ?? '') === 'legal';
$headingTag = $isLegal && ($ctx['index'] ?? 0) === 0 ? 'h1' : 'h2';
?>
<?php if ($isLegal): ?>
<section class="legal-section" id="block-<?= (int) $block['id'] ?>">
    <?php if ($d['heading'] !== ''): ?><<?= $headingTag ?> class="<?= $headingTag === 'h1' ? 'page-title' : 'legal-section__title' ?>"><?= e($d['heading']) ?></<?= $headingTag ?>><?php endif; ?>
    <div class="prose"><?= $d['body'] ?></div>
</section>
<?php else: ?>
<section class="<?= e(BlockRenderer::sectionClass('text-block text-block--' . $layout, $d)) ?>" id="block-<?= (int) $block['id'] ?>">
    <div class="container">
        <div class="text-block__grid">
            <?php if ($d['eyebrow'] !== '' || $d['heading'] !== ''): ?>
                <div class="text-block__head">
                    <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($d['eyebrow']) ?></p><?php endif; ?>
                    <?php if ($d['heading'] !== ''): ?><h2 class="section-title"><?= e($d['heading']) ?></h2><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="prose text-block__body"><?= $d['body'] ?></div>
        </div>
    </div>
</section>
<?php endif; ?>
