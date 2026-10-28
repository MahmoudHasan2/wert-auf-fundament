<?php /** @var array $problems @var bool $needToken @var bool $tokenConfigured @var array $old @var array $errors */ ?>
<h1 class="auth__title">Einrichtung – Schritt 2 von 2</h1>
<p class="muted">Legt die Datenbanktabellen und die Startinhalte an und erstellt Ihr Administratorkonto. Die Installation kann nur einmal ausgeführt werden.</p>
<?php if ($problems || ($needToken && !$tokenConfigured)): ?>
    <div class="alert alert--error" role="alert"><?= icon('triangle-alert') ?>
        <div>
            <p><strong>Die Installation kann noch nicht gestartet werden:</strong></p>
            <ul class="list">
                <?php foreach ($problems as $p): ?><li><?= e($p) ?></li><?php endforeach; ?>
                <?php if ($needToken && !$tokenConfigured): ?><li>Diese Sitzung hat Schritt 1 nicht ausgeführt. Tragen Sie in env.php ein install.token (mind. 16 Zeichen) ein oder löschen Sie env.php und starten Sie die Einrichtung neu.</li><?php endif; ?>
            </ul>
            <p>Details: siehe README, Abschnitt „Installation“.</p>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?= e(url('/admin/install')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <?php
        $fields = [
            ['name', 'Ihr Name', 'text', 'name'],
            ['email', 'E-Mail-Adresse (Benutzername)', 'email', 'email'],
            ['password', 'Passwort (mind. 12 Zeichen)', 'password', 'new-password'],
            ['password_confirm', 'Passwort wiederholen', 'password', 'new-password'],
        ];
        if ($needToken) {
            array_unshift($fields, ['token', 'Installations-Token (aus env.php)', 'password', 'off']);
        }
        foreach ($fields as [$n, $label, $type, $ac]): ?>
            <div class="field<?= isset($errors[$n]) ? ' has-error' : '' ?>">
                <label for="i-<?= $n ?>"><?= e($label) ?></label>
                <input id="i-<?= $n ?>" type="<?= $type ?>" name="<?= $n ?>" autocomplete="<?= $ac ?>" required value="<?= in_array($type, ['text', 'email'], true) ? e($old[$n] ?? '') : '' ?>"<?= isset($errors[$n]) ? ' aria-invalid="true"' : '' ?>>
                <?php if (isset($errors[$n])): ?><p class="field__error"><?= e($errors[$n]) ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="btn btn--primary btn--block" type="submit">Installation starten</button>
        <p class="field__help">Das Importieren der Startbilder kann bis zu einer Minute dauern – bitte die Seite nicht schließen.</p>
    </form>
<?php endif; ?>
