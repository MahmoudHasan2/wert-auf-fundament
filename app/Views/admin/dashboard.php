<?php
/** @var array $stats @var array $enquiries @var array $activity @var array $checks @var bool $mailOk @var array $user */
$errors = array_filter($checks, fn ($c) => $c['level'] === 'error');
$warnings = array_filter($checks, fn ($c) => $c['level'] !== 'error');
?>
<p class="lead">Willkommen, <?= e($user['name']) ?>. Hier sehen Sie den aktuellen Stand Ihrer Website.</p>

<div class="stat-grid">
    <?php foreach ($stats as [$label, $value, $href, $ic]): ?>
        <a class="stat-card" href="<?= e(url($href)) ?>">
            <span class="stat-card__icon"><?= icon($ic) ?></span>
            <span class="stat-card__value"><?= (int) $value ?></span>
            <span class="stat-card__label"><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="grid-2 grid-2--wide-left">
    <section class="card">
        <div class="card__head">
            <h2 class="card__title">Checkliste vor dem Livegang</h2>
            <span class="badge <?= $errors ? 'badge--red' : 'badge--green' ?>"><?= count($errors) ?> offen · <?= count($warnings) ?> Hinweise</span>
        </div>
        <?php if (!$checks): ?>
            <p class="empty">Alles erledigt – keine offenen Punkte.</p>
        <?php else: ?>
            <ul class="checks">
                <?php foreach (array_merge($errors, $warnings) as $c): ?>
                    <li class="checks__item checks__item--<?= e($c['level']) ?>">
                        <?= icon($c['level'] === 'error' ? 'triangle-alert' : ($c['level'] === 'warning' ? 'info' : 'circle-check')) ?>
                        <div>
                            <a href="<?= e(url($c['url'])) ?>"><strong><?= e($c['title']) ?></strong></a>
                            <p><?= e($c['text']) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <div class="stack">
        <section class="card">
            <h2 class="card__title">E-Mail-Benachrichtigungen</h2>
            <?php if ($mailOk): ?>
                <p class="status status--ok"><?= icon('circle-check') ?> E-Mail-Versand ist konfiguriert (<?= e(config('mail.transport')) ?>).</p>
            <?php else: ?>
                <p class="status status--warn"><?= icon('triangle-alert') ?> Nicht eingerichtet. Anfragen werden sicher gespeichert, aber es wird keine Benachrichtigung versendet.</p>
                <p class="muted small">Einrichtung: SMTP-Zugangsdaten in env.php eintragen (siehe README).</p>
            <?php endif; ?>
        </section>

        <?php if (\App\Security\Auth::can('enquiries')): ?>
        <section class="card">
            <div class="card__head"><h2 class="card__title">Neueste Anfragen</h2><a href="<?= e(url('/admin/enquiries')) ?>" class="link">Alle anzeigen</a></div>
            <?php if (!$enquiries): ?>
                <p class="empty">Noch keine Anfragen eingegangen.</p>
            <?php else: ?>
                <ul class="mini-list">
                    <?php foreach ($enquiries as $q): ?>
                        <li><a href="<?= e(url('/admin/enquiries/' . $q['id'])) ?>"><strong><?= e($q['name']) ?></strong><span><?= e($q['project_type'] ?: 'Allgemeine Anfrage') ?> · <?= e(format_date($q['created_at'], true)) ?></span></a><span class="pill pill--<?= e($q['status']) ?>"><?= e(status_label($q['status'])) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <section class="card">
            <h2 class="card__title">Letzte Änderungen</h2>
            <?php if (!$activity): ?>
                <p class="empty">Noch keine Änderungen protokolliert.</p>
            <?php else: ?>
                <ul class="mini-list">
                    <?php foreach ($activity as $a): ?>
                        <li><span><strong><?= e($a['summary'] ?: $a['action']) ?></strong><span><?= e($a['admin_name'] ?: 'System') ?> · <?= e(format_date($a['created_at'], true)) ?></span></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
