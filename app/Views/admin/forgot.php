<?php /** @var bool $mailEnabled */ ?>
<h1 class="auth__title">Passwort vergessen</h1>
<?php if (!$mailEnabled): ?>
    <p>Das Zurücksetzen per E-Mail ist nicht verfügbar, weil noch kein E-Mail-Versand eingerichtet ist. Ein Administrator kann Ihr Passwort unter „Benutzer“ neu setzen.</p>
<?php else: ?>
    <p class="muted">Geben Sie Ihre E-Mail-Adresse ein. Sie erhalten einen Link, über den Sie ein neues Passwort festlegen können.</p>
    <form method="post" action="<?= e(url('/admin/passwort-vergessen')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <div class="field"><label for="f-email">E-Mail-Adresse</label><input id="f-email" type="email" name="email" required autocomplete="username"></div>
        <button class="btn btn--primary btn--block" type="submit">Link anfordern</button>
    </form>
<?php endif; ?>
<p class="auth__links"><a href="<?= e(url('/admin/login')) ?>">Zurück zur Anmeldung</a></p>
