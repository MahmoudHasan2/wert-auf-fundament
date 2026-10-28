<?php /** @var string $token @var bool $valid */ ?>
<h1 class="auth__title">Neues Passwort festlegen</h1>
<?php if (!$valid): ?>
    <p>Dieser Link ist ungültig oder abgelaufen.</p>
    <p class="auth__links"><a href="<?= e(url('/admin/passwort-vergessen')) ?>">Neuen Link anfordern</a></p>
<?php else: ?>
    <form method="post" action="<?= e(url('/admin/passwort-zuruecksetzen')) ?>" class="form-stack">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field"><label for="r-pw">Neues Passwort (mind. 12 Zeichen)</label><input id="r-pw" type="password" name="password" required autocomplete="new-password"></div>
        <div class="field"><label for="r-pw2">Passwort wiederholen</label><input id="r-pw2" type="password" name="password_confirm" required autocomplete="new-password"></div>
        <button class="btn btn--primary btn--block" type="submit">Passwort speichern</button>
    </form>
<?php endif; ?>
