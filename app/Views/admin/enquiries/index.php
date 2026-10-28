<?php
/** @var array $rows @var \App\Support\Paginator $pager @var string $status @var string $q @var array $counts @var int $expired @var int $days */
use App\Controllers\Admin\EnquiryAdminController;
use App\Security\Auth;

$byStatus = array_column($counts, 'n', 'status');
?>
<nav class="tabs" aria-label="Nach Status filtern">
    <a class="tabs__tab<?= $status === '' ? ' is-active' : '' ?>" href="<?= e(url('/admin/enquiries')) ?>">Alle (<?= array_sum($byStatus) ?>)</a>
    <?php foreach (EnquiryAdminController::STATUSES as $k => $label): ?>
        <a class="tabs__tab<?= $status === $k ? ' is-active' : '' ?>" href="<?= e(url('/admin/enquiries?status=' . $k)) ?>"><?= e($label) ?> (<?= (int) ($byStatus[$k] ?? 0) ?>)</a>
    <?php endforeach; ?>
</nav>
<div class="toolbar">
    <form class="filters" method="get" action="<?= e(url('/admin/enquiries')) ?>">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <label class="sr-only" for="eq">Suchen</label>
        <input type="search" id="eq" name="q" value="<?= e($q) ?>" placeholder="Name, E-Mail, Nachricht …">
        <button class="btn btn--secondary btn--sm" type="submit">Suchen</button>
    </form>
    <?php if (Auth::can('settings')): ?>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/enquiries/export?' . http_build_query(array_filter(['status' => $status, 'q' => $q])))) ?>"><?= icon('download') ?> CSV-Export</a>
    <?php endif; ?>
</div>
<?php if ($expired > 0): ?>
    <div class="alert alert--warning"><?= icon('clock') ?>
        <div><p><?= $expired ?> beantwortete bzw. abgeschlossene Anfrage(n) sind älter als die Aufbewahrungsfrist von <?= $days ?> Tagen.</p>
            <form method="post" action="<?= e(url('/admin/enquiries/purge')) ?>" data-confirm="<?= $expired ?> Anfrage(n) endgültig löschen?"><?= csrf_field() ?><button class="btn btn--secondary btn--sm" type="submit"><?= icon('trash-2') ?> Jetzt löschen</button></form>
        </div>
    </div>
<?php endif; ?>
<div class="card card--flush">
    <?php if (!$rows): ?>
        <p class="empty"><?= $q !== '' || $status !== '' ? 'Keine Anfragen für diesen Filter.' : 'Noch keine Anfragen eingegangen. Neue Anfragen aus dem Kontaktformular erscheinen hier.' ?></p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Eingang</th><th>Name</th><th>Vorhaben</th><th>Status</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="<?= $r['status'] === 'new' ? 'is-new' : '' ?>">
                <td data-label="Eingang"><?= e(format_date($r['created_at'], true)) ?></td>
                <td data-label="Name"><a href="<?= e(url('/admin/enquiries/' . $r['id'])) ?>"><strong><?= e($r['name']) ?></strong></a><br><span class="muted small"><?= e($r['email']) ?></span></td>
                <td data-label="Vorhaben"><?= e($r['project_type'] ?: '–') ?></td>
                <td data-label="Status"><span class="pill pill--<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
                <td class="table__actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/enquiries/' . $r['id'])) ?>"><?= icon('eye') ?> Öffnen</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?= \App\Core\View::partial('admin/partials/pagination', ['pager' => $pager, 'query' => array_filter(['status' => $status, 'q' => $q])]) ?>
