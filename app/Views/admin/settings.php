<?php
/** @var array $schema @var array $values @var array $old @var array $errors */
use App\Core\View;

$i = 0;
?>
<p class="lead">Diese Angaben werden zentral gespeichert und automatisch im Kopf- und Fußbereich, auf der Kontaktseite, im Impressum und in den strukturierten Daten für Suchmaschinen verwendet.</p>
<form method="post" action="<?= e(url('/admin/settings')) ?>" class="settings-form">
    <?= csrf_field() ?>
    <nav class="tabs tabs--anchors" aria-label="Abschnitte">
        <?php foreach (array_keys($schema) as $j => $group): ?><a class="tabs__tab" href="#grp-<?= $j ?>"><?= e($group) ?></a><?php endforeach; ?>
    </nav>
    <?php foreach ($schema as $group => $fields): ?>
        <section class="card form-stack" id="grp-<?= $i++ ?>">
            <h2 class="card__title"><?= e($group) ?></h2>
            <div class="grid-2 grid-2--fields">
            <?php foreach ($fields as $key => $f): ?>
                <?php $value = $old[$key] ?? ($values[$key] ?? ''); ?>
                <div class="<?= in_array($f['type'], ['textarea', 'media'], true) ? 'span-2' : '' ?>">
                    <?= View::partial('admin/partials/field', ['f' => $f + ['name' => $key, 'rows' => 3], 'name' => $key, 'value' => $value, 'error' => $errors[$key] ?? null]) ?>
                    <?php if (!empty($f['legal']) && $value === ''): ?><p class="field__hint-legal"><?= icon('triangle-alert', 'icon icon--xs') ?> Fehlt – wird im Impressum benötigt.</p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <div class="sticky-save"><button class="btn btn--primary btn--lg" type="submit"><?= icon('save') ?> Alle Einstellungen speichern</button></div>
</form>
