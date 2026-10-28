<?php
/** @var string $menu @var array $tree */
use App\Repositories\NavigationRepository;

$target = function (array $r): string {
    return match ($r['link_type']) {
        'page' => 'Seite: ' . ($r['page_title'] ?? '(gelöscht)'),
        'service' => 'Leistung: ' . ($r['service_title'] ?? '(gelöscht)'),
        default => $r['url'],
    };
};
$row = function (array $r, bool $child, int $i, int $count) use ($target): string {
    $resolved = NavigationRepository::resolve($r);
    ob_start(); ?>
    <tr class="<?= $child ? 'is-child' : '' ?>">
        <td data-label="Eintrag"><?= $child ? '<span class="muted">↳ </span>' : '' ?><a href="<?= e(url('/admin/navigation/' . $r['id'])) ?>"><strong><?= e($r['label']) ?></strong></a>
            <?php if ((int) $r['auto_children']): ?><span class="badge" title="Untermenü wird automatisch aus den Leistungen erzeugt">+ Leistungen</span><?php endif; ?></td>
        <td data-label="Ziel"><?= e($target($r)) ?><?php if ($resolved === null): ?> <span class="badge badge--amber" title="Ziel ist nicht veröffentlicht – Eintrag wird auf der Website nicht angezeigt">nicht erreichbar</span><?php endif; ?></td>
        <td data-label="Sichtbar"><?= (int) $r['is_visible'] ? 'Ja' : '<span class="muted">Nein</span>' ?></td>
        <td data-label="Reihenfolge" class="table__order">
            <form method="post" action="<?= e(url('/admin/navigation/' . $r['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="icon-btn" type="submit"<?= $i === 0 ? ' disabled' : '' ?> title="Nach oben"><?= icon('arrow-up') ?><span class="sr-only">Nach oben</span></button></form>
            <form method="post" action="<?= e(url('/admin/navigation/' . $r['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="icon-btn" type="submit"<?= $i === $count - 1 ? ' disabled' : '' ?> title="Nach unten"><?= icon('arrow-down') ?><span class="sr-only">Nach unten</span></button></form>
        </td>
        <td class="table__actions">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/navigation/' . $r['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a>
            <form method="post" action="<?= e(url('/admin/navigation/' . $r['id'] . '/delete')) ?>" data-confirm="Menüeintrag „<?= e($r['label']) ?>“ löschen?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash-2') ?> Löschen</button></form>
        </td>
    </tr>
    <?php return (string) ob_get_clean();
};
?>
<nav class="tabs" aria-label="Menüs">
    <?php foreach (NavigationRepository::MENUS as $k => $label): ?>
        <a href="<?= e(url('/admin/navigation?menu=' . $k)) ?>" class="tabs__tab<?= $k === $menu ? ' is-active' : '' ?>"<?= $k === $menu ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if ($menu === 'footer'): ?><p class="muted">Die Spalte „Leistungen“ im Fußbereich wird automatisch aus den veröffentlichten Hauptleistungen erzeugt.</p><?php endif; ?>
<div class="editor-grid">
    <section class="card card--flush">
        <?php if (!$tree): ?>
            <p class="empty">Dieses Menü ist leer.</p>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Eintrag</th><th>Ziel</th><th>Sichtbar</th><th>Reihenfolge</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
            <tbody>
            <?php foreach ($tree as $i => $node): ?>
                <?= $row($node['item'], false, $i, count($tree)) ?>
                <?php foreach ($node['children'] as $j => $c): ?><?= $row($c, true, $j, count($node['children'])) ?><?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
    <aside>
        <form method="post" action="<?= e(url('/admin/navigation')) ?>" class="card form-stack">
            <?= csrf_field() ?>
            <input type="hidden" name="menu" value="<?= e($menu) ?>">
            <h2 class="card__title">Neuer Eintrag</h2>
            <?= \App\Core\View::partial('admin/navigation/form-fields', ['item' => null, 'menu' => $menu, 'old' => $old, 'errors' => $errors]) ?>
            <button class="btn btn--primary" type="submit"><?= icon('plus') ?> Hinzufügen</button>
        </form>
    </aside>
</div>
