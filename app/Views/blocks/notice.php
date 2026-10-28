<?php
/** @var array $d @var array $block @var array $ctx */
$tone = $d['tone'] === 'warning' ? 'warning' : 'info';
$inLegal = ($ctx['page']['template'] ?? '') === 'legal';
?>
<?php if (!$inLegal): ?><section class="section section--white notice-section" id="block-<?= (int) $block['id'] ?>"><div class="container narrow"><?php endif; ?>
<aside class="notice notice--<?= e($tone) ?>"<?= $inLegal ? ' id="block-' . (int) $block['id'] . '"' : '' ?>>
    <span class="notice__icon" aria-hidden="true"><?= icon($tone === 'warning' ? 'triangle-alert' : 'info') ?></span>
    <div>
        <?php if ($d['heading'] !== ''): ?><h2 class="notice__title"><?= e($d['heading']) ?></h2><?php endif; ?>
        <div class="prose"><?= $d['body'] ?></div>
    </div>
</aside>
<?php if (!$inLegal): ?></div></section><?php endif; ?>
