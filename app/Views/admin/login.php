<?php /** @var array $old @var bool $mailEnabled */ ?>
<h1 class="auth__title">Anmeldung Verwaltung</h1>
<form method="post" action="<?= e(url('/admin/login')) ?>" class="form-stack">
    <?= csrf_field() ?>
    <div class="field">
        <label for="l-email">E-Mail-Adresse</label>
        <input id="l-email" type="email" name="email" autocomplete="username" required autofocus value="<?= e($old['email'] ?? '') ?>">
    </div>
    <div class="field">
        <label for="l-password">Passwort</label>
        <input id="l-password" type="password" name="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn--primary btn--block" type="submit">Anmelden</button>
</form>
<p class="auth__links">
    <?php if ($mailEnabled): ?><a href="<?= e(url('/admin/passwort-vergessen')) ?>">Passwort vergessen?</a><?php else: ?><span class="muted">Passwort vergessen? Bitte wenden Sie sich an einen Administrator.</span><?php endif; ?>
    <a href="<?= e(url('/')) ?>">Zur Website</a>
</p>
