<?php
/** @var array $checks @var string $dbVersion @var array $pending @var array $applied @var bool $mailOk @var string $mailTransport @var array $compliance @var array $logs @var string $appUrl */
?>
<div class="grid-2">
    <section class="card">
        <h2 class="card__title">Server &amp; PHP</h2>
        <ul class="syscheck">
            <?php foreach ($checks as $c): ?>
                <li class="syscheck__item <?= $c['ok'] ? 'is-ok' : ($c['warnOnly'] ? 'is-warn' : 'is-error') ?>">
                    <?= icon($c['ok'] ? 'circle-check' : 'triangle-alert') ?><span><strong><?= e($c['label']) ?></strong> – <?= e($c['detail']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <dl class="dl"><div><dt>Datenbank</dt><dd><?= e($dbVersion) ?></dd></div><div><dt>Website-Adresse (app.url)</dt><dd><?= e($appUrl) ?></dd></div><div><dt>Anwendungsversion</dt><dd><?= e(APP_VERSION) ?></dd></div></dl>
    </section>
    <div class="stack">
        <section class="card form-stack">
            <h2 class="card__title">Datenbank-Aktualisierungen</h2>
            <p class="muted small"><?= count($applied) ?> Migration(en) angewendet.</p>
            <?php if ($pending): ?>
                <p class="status status--warn"><?= icon('triangle-alert') ?> <?= count($pending) ?> ausstehend: <?= e(implode(', ', $pending)) ?></p>
                <p class="muted small">Erstellen Sie vorher ein Datenbank-Backup.</p>
                <form method="post" action="<?= e(url('/admin/system/migrate')) ?>" data-confirm="Datenbankaktualisierung jetzt ausführen? Bitte vorher ein Backup erstellen."><?= csrf_field() ?><button class="btn btn--primary" type="submit"><?= icon('refresh-cw') ?> Jetzt aktualisieren</button></form>
            <?php else: ?>
                <p class="status status--ok"><?= icon('circle-check') ?> Die Datenbank ist aktuell.</p>
            <?php endif; ?>
        </section>
        <section class="card form-stack">
            <h2 class="card__title">E-Mail-Versand</h2>
            <?php if ($mailOk): ?>
                <p class="status status--ok"><?= icon('circle-check') ?> Konfiguriert (Transport: <?= e($mailTransport) ?>).</p>
                <form method="post" action="<?= e(url('/admin/system/test-mail')) ?>"><?= csrf_field() ?><button class="btn btn--secondary" type="submit"><?= icon('send') ?> Testnachricht an mich senden</button></form>
            <?php else: ?>
                <p class="status status--warn"><?= icon('triangle-alert') ?> Nicht eingerichtet. Kontaktanfragen werden gespeichert, aber nicht per E-Mail gemeldet; „Passwort vergessen“ ist deaktiviert.</p>
                <p class="muted small">SMTP-Daten in env.php unter „mail“ eintragen (siehe README → „E-Mail/SMTP“).</p>
            <?php endif; ?>
        </section>
        <section class="card">
            <h2 class="card__title">Fehlerprotokolle</h2>
            <?php if (!$logs): ?><p class="empty">Keine Fehler protokolliert.</p><?php else: ?>
                <p class="muted small">Protokolle liegen im geschützten Ordner storage/logs (Zugriff per Dateimanager/FTP).</p>
                <ul class="mini-list"><?php foreach ($logs as $l): ?><li><code><?= e($l['name']) ?></code><span><?= number_format($l['size'] / 1024, 1, ',', '.') ?> KB</span></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </section>
    </div>
</div>
<section class="card">
    <h2 class="card__title">Checkliste vor dem Livegang</h2>
    <ul class="checks">
        <?php foreach ($compliance as $c): ?>
            <li class="checks__item checks__item--<?= e($c['level']) ?>"><?= icon($c['level'] === 'error' ? 'triangle-alert' : 'info') ?><div><a href="<?= e(url($c['url'])) ?>"><strong><?= e($c['title']) ?></strong></a><p><?= e($c['text']) ?></p></div></li>
        <?php endforeach; ?>
    </ul>
</section>
