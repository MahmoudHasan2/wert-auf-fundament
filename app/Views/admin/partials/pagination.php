<?php
/** @var \App\Support\Paginator $pager @var array $query */
if ($pager->pages <= 1) {
    return;
}
$query = $query ?? [];
$link = fn (int $p) => '?' . http_build_query($query + ['seite' => $p]);
$cur = $pager->current();
?>
<nav class="pagination" aria-label="Seitennavigation">
    <?php if ($cur > 1): ?><a href="<?= e($link($cur - 1)) ?>" rel="prev"><?= icon('chevron-left') ?> Zurück</a><?php endif; ?>
    <span>Seite <?= $cur ?> von <?= $pager->pages ?> (<?= $pager->total ?> Einträge)</span>
    <?php if ($cur < $pager->pages): ?><a href="<?= e($link($cur + 1)) ?>" rel="next">Weiter <?= icon('chevron-right') ?></a><?php endif; ?>
</nav>
