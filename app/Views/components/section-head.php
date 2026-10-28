<?php
/** Shared section heading. @var array $d @var string $level */
$level = $level ?? 'h2';
$align = $align ?? '';
if (($d['eyebrow'] ?? '') === '' && ($d['heading'] ?? '') === '' && ($d['intro'] ?? '') === '') {
    return;
}
?>
<div class="section-head<?= $align !== '' ? ' section-head--' . e($align) : '' ?>">
    <?php if (($d['eyebrow'] ?? '') !== ''): ?><p class="eyebrow"><?= e($d['eyebrow']) ?></p><?php endif; ?>
    <?php if (($d['heading'] ?? '') !== ''): ?><<?= $level ?> class="section-title"><?= e($d['heading']) ?></<?= $level ?>><?php endif; ?>
    <?php if (($d['intro'] ?? '') !== ''): ?><p class="section-intro"><?= nl2br(e($d['intro'])) ?></p><?php endif; ?>
</div>
