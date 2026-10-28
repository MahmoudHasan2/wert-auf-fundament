<?php /** @var array $user @var array $errors */ ?>
<div class="grid-2">
    <section class="card">
        <h2 class="card__title">Ihr Konto</h2>
        <dl class="dl">
            <div><dt>Name</dt><dd><?= e($user['name']) ?></dd></div>
            <div><dt>E-Mail</dt><dd><?= e($user['email']) ?></dd></div>
            <div><dt>Rolle</dt><dd><?= e(\App\Security\Auth::ROLE_LABELS[$user['role']] ?? $user['role']) ?></dd></div>
        </dl>
    </section>
    <section class="card">
        <h2 class="card__title">Passwort ändern</h2>
        <form method="post" action="<?= e(url('/admin/konto')) ?>" class="form-stack">
            <?= csrf_field() ?>
            <?php foreach ([['current_password', 'Aktuelles Passwort', 'current-password'], ['password', 'Neues Passwort (mind. 12 Zeichen)', 'new-password'], ['password_confirm', 'Neues Passwort wiederholen', 'new-password']] as [$n, $l, $ac]): ?>
                <div class="field<?= isset($errors[$n]) ? ' has-error' : '' ?>">
                    <label for="a-<?= $n ?>"><?= e($l) ?></label>
                    <input id="a-<?= $n ?>" type="password" name="<?= $n ?>" required autocomplete="<?= $ac ?>"<?= isset($errors[$n]) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors[$n])): ?><p class="field__error"><?= e($errors[$n]) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button class="btn btn--primary" type="submit">Passwort ändern</button>
        </form>
    </section>
</div>
