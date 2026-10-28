<?php /** @var string $detectedUrl @var array $old @var array $errors */ ?>
<h1 class="auth__title">Einrichtung – Schritt 1 von 2</h1>
<p class="muted">Tragen Sie die Zugangsdaten der Datenbank ein (Hostinger: <strong>hPanel → Datenbanken → MySQL-Datenbanken</strong>). Die Konfiguration wird automatisch gespeichert.</p>
<form method="post" action="<?= e(url('/admin/install/config')) ?>" class="form-stack">
    <?= csrf_field() ?>
    <?php foreach ([
        ['app_url', 'Adresse der Website', 'url', $old['app_url'] ?? $detectedUrl, 'Mit https://, sobald das SSL-Zertifikat aktiv ist.'],
        ['db_host', 'Datenbank-Host', 'text', $old['db_host'] ?? 'localhost', ''],
        ['db_port', 'Port', 'number', $old['db_port'] ?? '3306', ''],
        ['db_name', 'Datenbankname', 'text', $old['db_name'] ?? '', 'z. B. u123456789_waf'],
        ['db_user', 'Datenbank-Benutzer', 'text', $old['db_user'] ?? '', 'z. B. u123456789_waf'],
        ['db_pass', 'Datenbank-Passwort', 'password', '', ''],
    ] as [$n, $label, $type, $value, $help]): ?>
        <div class="field<?= isset($errors[$n]) ? ' has-error' : '' ?>">
            <label for="c-<?= $n ?>"><?= e($label) ?></label>
            <input id="c-<?= $n ?>" type="<?= $type ?>" name="<?= $n ?>" value="<?= e($value) ?>" autocomplete="off"<?= in_array($n, ['db_name', 'db_user', 'app_url'], true) ? ' required' : '' ?>>
            <?php if ($help !== ''): ?><p class="field__help"><?= e($help) ?></p><?php endif; ?>
            <?php if (isset($errors[$n])): ?><p class="field__error"><?= e($errors[$n]) ?></p><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <button class="btn btn--primary btn--block" type="submit">Verbindung prüfen &amp; speichern</button>
    <p class="field__help">Sicherheitshinweis: Führen Sie die Einrichtung direkt nach dem Hochladen der Dateien durch. Danach ist dieser Assistent dauerhaft gesperrt.</p>
</form>
