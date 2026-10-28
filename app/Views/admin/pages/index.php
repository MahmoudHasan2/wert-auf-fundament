<?php
/** @var array $pages @var string $status @var string $q */
use App\Repositories\PageRepository;
use App\Security\Auth;
?>
<div class="toolbar">
    <form class="filters" method="get" action="<?= e(url('/admin/pages')) ?>">
        <label class="sr-only" for="pq">Suchen</label>
        <input type="search" id="pq" name="q" value="<?= e($q) ?>" placeholder="Titel oder URL suchen">
        <label class="sr-only" for="ps">Status</label>
        <select id="ps" name="status">
            <option value="">Alle Status</option>
            <option value="published"<?= $status === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
            <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>Entwurf</option>
        </select>
        <button class="btn btn--secondary btn--sm" type="submit">Filtern</button>
    </form>
    <a class="btn btn--primary" href="<?= e(url('/admin/pages/create')) ?>"><?= icon('plus') ?> Neue Seite</a>
</div>

<div class="card card--flush">
    <?php if (!$pages): ?>
        <p class="empty">Keine Seiten gefunden.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Titel</th><th>URL</th><th>Vorlage</th><th>Status</th><th>Geändert</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $p): $legal = PageRepository::isLegal($p); $canEdit = !$legal || Auth::can('legal'); ?>
            <tr>
                <td data-label="Titel">
                    <?php if ($canEdit): ?><a href="<?= e(url('/admin/pages/' . $p['id'])) ?>"><strong><?= e($p['title']) ?></strong></a><?php else: ?><strong><?= e($p['title']) ?></strong><?php endif; ?>
                    <?php if ($p['system_key']): ?><span class="badge" title="Systemseite – URL fest, nicht löschbar"><?= e(PageRepository::SYSTEM[$p['system_key']] ?? 'System') ?></span><?php endif; ?>
                </td>
                <td data-label="URL"><code><?= e(PageRepository::path($p)) ?></code></td>
                <td data-label="Vorlage"><?= e(explode(' (', PageRepository::TEMPLATES[$p['template']] ?? $p['template'])[0]) ?></td>
                <td data-label="Status"><span class="pill pill--<?= e(PageRepository::displayStatus($p)) ?>"><?= e(status_label(PageRepository::displayStatus($p))) ?></span></td>
                <td data-label="Geändert"><?= e(format_date($p['updated_at'], true)) ?></td>
                <td class="table__actions">
                    <?php if ($canEdit): ?><a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/pages/' . $p['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a><?php endif; ?>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/pages/' . $p['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
