<?php /** @var array $old @var array $errors */ ?>
<form method="post" action="<?= e(url('/admin/pages')) ?>" class="card form-stack form-narrow">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('admin/pages/settings-fields', ['page' => null, 'old' => $old, 'errors' => $errors]) ?>
    <div class="form-actions">
        <button class="btn btn--primary" type="submit">Seite anlegen</button>
        <a class="btn btn--ghost" href="<?= e(url('/admin/pages')) ?>">Abbrechen</a>
    </div>
</form>
