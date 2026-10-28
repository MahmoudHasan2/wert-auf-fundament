<?php /** @var array $item @var string $menu */ ?>
<p class="breadcrumb-admin"><a href="<?= e(url('/admin/navigation?menu=' . $menu)) ?>"><?= icon('arrow-left') ?> Zurück zum Menü</a></p>
<form method="post" action="<?= e(url('/admin/navigation/' . $item['id'])) ?>" class="card form-stack form-narrow">
    <?= csrf_field() ?>
    <?= \App\Core\View::partial('admin/navigation/form-fields', ['item' => $item, 'menu' => $menu, 'old' => $old, 'errors' => $errors]) ?>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('save') ?> Speichern</button></div>
</form>
