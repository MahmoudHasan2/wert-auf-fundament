<?php /** @var array $rows @var \App\Support\Paginator $pager @var string $action @var array $groups @var array $labels */ ?>
<div class="toolbar">
    <form class="filters" method="get" action="<?= e(url('/admin/audit')) ?>">
        <label class="sr-only" for="aa">Bereich</label>
        <select id="aa" name="aktion"><option value="">Alle Ereignisse</option><?php foreach ($groups as $g): ?><option value="<?= e($g) ?>"<?= $action === $g ? ' selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select>
        <button class="btn btn--secondary btn--sm" type="submit">Filtern</button>
    </form>
    <p class="muted small">IP-Adressen werden nur gekürzt gespeichert. Passwörter und Anfrageinhalte werden nie protokolliert.</p>
</div>
<div class="card card--flush">
    <?php if (!$rows): ?><p class="empty">Keine Einträge.</p><?php else: ?>
    <table class="table table--compact">
        <thead><tr><th>Zeitpunkt</th><th>Benutzer</th><th>Ereignis</th><th>Beschreibung</th><th>IP (gekürzt)</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="<?= str_contains($r['action'], 'failed') || str_contains($r['action'], 'throttled') ? 'is-warn' : '' ?>">
                <td data-label="Zeitpunkt"><?= e(format_date($r['created_at'], true)) ?></td>
                <td data-label="Benutzer"><?= e($r['admin_name'] ?: '–') ?></td>
                <td data-label="Ereignis"><code><?= e($labels[$r['action']] ?? $r['action']) ?></code></td>
                <td data-label="Beschreibung"><?= e($r['summary']) ?></td>
                <td data-label="IP"><?= e($r['ip_anon'] ?: '–') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?= \App\Core\View::partial('admin/partials/pagination', ['pager' => $pager, 'query' => array_filter(['aktion' => $action])]) ?>
