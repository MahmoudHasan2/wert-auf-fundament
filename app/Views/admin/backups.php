<?php /** @var array $backups @var bool $zip */ ?>
<div class="alert alert--info"><?= icon('info') ?>
    <div>
        <p><strong>Wichtig:</strong> Backups auf demselben Server schützen nicht vor einem Ausfall des Hostings. Laden Sie Backups regelmäßig herunter und nutzen Sie zusätzlich die automatischen Backups von Hostinger (hPanel → Dateien → Backups). Die vollständige Anleitung finden Sie in der README (Abschnitt „Backups“).</p>
    </div>
</div>
<div class="grid-2">
    <section class="card form-stack">
        <h2 class="card__title">Datenbank sichern</h2>
        <p class="muted">Sichert alle Inhalte, Einstellungen, Benutzer und Anfragen als SQL-Datei (gzip-komprimiert).</p>
        <form method="post" action="<?= e(url('/admin/backups/database')) ?>"><?= csrf_field() ?><button class="btn btn--primary" type="submit"><?= icon('database') ?> Datenbank-Backup erstellen</button></form>
    </section>
    <section class="card form-stack">
        <h2 class="card__title">Bilder sichern</h2>
        <p class="muted">Erstellt ein ZIP-Archiv aller hochgeladenen Bilder (Ordner public/uploads).</p>
        <?php if ($zip): ?>
            <form method="post" action="<?= e(url('/admin/backups/media')) ?>"><?= csrf_field() ?><button class="btn btn--secondary" type="submit"><?= icon('images') ?> Medien-Archiv erstellen</button></form>
        <?php else: ?>
            <p class="status status--warn"><?= icon('triangle-alert') ?> Die PHP-Erweiterung „zip“ fehlt – bitte den Ordner public/uploads per Dateimanager/FTP sichern.</p>
        <?php endif; ?>
    </section>
</div>
<section class="card card--flush">
    <div class="card__head card__head--padded"><h2 class="card__title">Vorhandene Backups</h2></div>
    <?php if (!$backups): ?>
        <p class="empty">Noch keine Backups vorhanden.</p>
    <?php else: ?>
    <table class="table">
        <thead><tr><th>Datei</th><th>Art</th><th>Größe</th><th>Erstellt</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
            <tr>
                <td data-label="Datei"><code><?= e($b['name']) ?></code></td>
                <td data-label="Art"><?= $b['type'] === 'db' ? 'Datenbank' : 'Bilder' ?></td>
                <td data-label="Größe"><?= number_format($b['size'] / 1048576, 2, ',', '.') ?> MB</td>
                <td data-label="Erstellt"><?= e(date('d.m.Y, H:i', $b['time'])) ?></td>
                <td class="table__actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/backups/download/' . $b['name'])) ?>"><?= icon('download') ?> Herunterladen</a>
                    <form method="post" action="<?= e(url('/admin/backups/delete')) ?>" data-confirm="Backup löschen?"><?= csrf_field() ?><input type="hidden" name="name" value="<?= e($b['name']) ?>"><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash-2') ?> Löschen</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>

<?php $dbBackups = array_filter($backups, fn ($b) => $b['type'] === 'db'); if ($dbBackups): ?>
<section class="card form-stack card--danger">
    <h2 class="card__title">Datenbank wiederherstellen</h2>
    <p><strong>Achtung:</strong> Der aktuelle Datenbankinhalt wird vollständig durch den Stand des Backups ersetzt (Seiten, Einstellungen, Benutzer, Anfragen). Vorher wird automatisch ein Sicherungs-Backup des aktuellen Stands erstellt. Bilder werden dabei nicht verändert.</p>
    <form method="post" action="<?= e(url('/admin/backups/restore')) ?>" class="form-stack form-narrow" data-confirm="Datenbank jetzt wirklich wiederherstellen?">
        <?= csrf_field() ?>
        <div class="field"><label for="rb-name">Backup</label><select id="rb-name" name="name" required><?php foreach ($dbBackups as $b): ?><option value="<?= e($b['name']) ?>"><?= e($b['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="rb-pw">Ihr Passwort</label><input id="rb-pw" type="password" name="password" required autocomplete="current-password"></div>
        <div class="field"><label for="rb-confirm">Zur Bestätigung <code>WIEDERHERSTELLEN</code> eingeben</label><input id="rb-confirm" type="text" name="confirm" required autocomplete="off" pattern="WIEDERHERSTELLEN"></div>
        <button class="btn btn--danger" type="submit"><?= icon('rotate-ccw') ?> Wiederherstellen</button>
    </form>
</section>
<?php endif; ?>
