<?php
/** @var ?array $u @var array $old @var array $errors */
use App\Security\Auth;

$v = fn (string $k, $d = '') => $old[$k] ?? ($u[$k] ?? $d);
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field__error">' . e($errors[$k]) . '</p>' : '';
?>
<p class="breadcrumb-admin"><a href="<?= e(url('/admin/users')) ?>"><?= icon('arrow-left') ?> Alle Benutzer</a></p>
<form method="post" action="<?= e($u ? url('/admin/users/' . $u['id']) : url('/admin/users')) ?>" class="card form-stack form-narrow" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>"><label for="u-name">Name <span class="req">*</span></label><input id="u-name" type="text" name="name" maxlength="120" required value="<?= e($v('name')) ?>"><?= $err('name') ?></div>
    <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>"><label for="u-email">E-Mail-Adresse (Anmeldename) <span class="req">*</span></label><input id="u-email" type="email" name="email" maxlength="190" required value="<?= e($v('email')) ?>"><?= $err('email') ?></div>
    <fieldset class="field<?= isset($errors['role']) ? ' has-error' : '' ?>"><legend>Rolle</legend>
        <?php foreach (Auth::ROLE_LABELS as $k => $l): ?><label class="check"><input type="radio" name="role" value="<?= $k ?>"<?= $v('role', 'editor') === $k ? ' checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?>
        <?= $err('role') ?>
    </fieldset>
    <input type="hidden" name="is_active" value=""><label class="check"><input type="checkbox" name="is_active" value="1"<?= $v('is_active', 1) ? ' checked' : '' ?>> Konto aktiv</label>
    <fieldset class="field<?= isset($errors['password']) ? ' has-error' : '' ?>">
        <legend><?= $u ? 'Neues Passwort setzen (optional)' : 'Passwort' ?></legend>
        <div class="grid-2">
            <div class="field"><label for="u-pw">Passwort (mind. <?= Auth::MIN_PASSWORD_LENGTH ?> Zeichen)</label><input id="u-pw" type="password" name="password" autocomplete="new-password"<?= $u ? '' : ' required' ?>></div>
            <div class="field"><label for="u-pw2">Wiederholen</label><input id="u-pw2" type="password" name="password_confirm" autocomplete="new-password"<?= $u ? '' : ' required' ?>></div>
        </div>
        <?= $err('password') ?>
        <?php if ($u): ?><p class="field__help">Wird ein neues Passwort gesetzt, werden alle Sitzungen dieses Benutzers beendet.</p><?php endif; ?>
    </fieldset>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('save') ?> Speichern</button></div>
</form>
