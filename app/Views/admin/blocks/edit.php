<?php
/** @var array $block @var array $def @var string $owner @var ?array $ownerRow @var string $back @var array $usage @var array $old @var array $errors */
use App\Core\View;

$data = $old['data'] ?? $block['data'];
$visible = isset($old['is_visible']) ? !empty($old['is_visible']) : (bool) $block['is_visible'];
$previewUrl = match ($owner) {
    'page' => '/admin/pages/' . $ownerRow['id'] . '/preview',
    'service' => '/admin/services/' . $ownerRow['id'] . '/preview',
    default => null,
};
?>
<p class="breadcrumb-admin">
    <a href="<?= e(url($back)) ?>"><?= icon('arrow-left') ?> Zurück zu <?= e($owner === 'library' ? 'den Bausteinen' : '„' . ($ownerRow['title'] ?? '') . '“') ?></a>
</p>
<?php if ($def['desc'] !== ''): ?><p class="lead"><?= e($def['desc']) ?></p><?php endif; ?>
<?php if ($block['type'] === 'stats'): ?>
    <div class="alert alert--warning"><?= icon('triangle-alert') ?><p>Bitte nur geprüfte, belegbare Zahlen veröffentlichen.</p></div>
<?php endif; ?>
<?php if ($owner === 'library'): ?>
    <div class="alert alert--info"><?= icon('info') ?><p>Dieser Baustein wird <?= count($usage) ?>× verwendet<?= $usage ? ': ' . e(implode(', ', array_column($usage, 'label'))) : '' ?>. Änderungen wirken überall.</p></div>
<?php endif; ?>

<form method="post" action="<?= e(url('/admin/blocks/' . $block['id'])) ?>" class="editor-grid">
    <?= csrf_field() ?>
    <section class="card form-stack">
        <?php if ($owner === 'library'): ?>
            <div class="field<?= isset($errors['library_name']) ? ' has-error' : '' ?>">
                <label for="lib-name">Name des Bausteins (nur intern) <span class="req">*</span></label>
                <input id="lib-name" type="text" name="library_name" maxlength="160" required value="<?= e($old['library_name'] ?? $block['library_name']) ?>">
                <?php if (isset($errors['library_name'])): ?><p class="field__error"><?= e($errors['library_name']) ?></p><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php foreach ($def['fields'] as $f): ?>
            <?= View::partial('admin/partials/field', ['f' => $f, 'name' => 'data[' . $f['name'] . ']', 'value' => $data[$f['name']] ?? ($f['default'] ?? ''), 'error' => $errors[$f['name']] ?? null]) ?>
        <?php endforeach; ?>
    </section>
    <aside class="stack sticky-aside">
        <section class="card form-stack">
            <h2 class="card__title">Veröffentlichung</h2>
            <input type="hidden" name="is_visible" value="">
            <label class="switch"><input type="checkbox" name="is_visible" value="1"<?= $visible ? ' checked' : '' ?>> <span>Block auf der Website anzeigen</span></label>
            <div class="field">
                <label for="b-note">Interne Notiz (nicht öffentlich)</label>
                <textarea id="b-note" name="admin_note" rows="3" maxlength="500"><?= e($old['admin_note'] ?? $block['admin_note']) ?></textarea>
                <p class="field__help">Z. B. „Vor Freigabe bestätigen lassen“. Ausgeblendete Blöcke mit Notiz erscheinen in der Checkliste.</p>
            </div>
            <button class="btn btn--primary btn--block" type="submit" name="after" value="stay"><?= icon('save') ?> Speichern</button>
            <button class="btn btn--secondary btn--block" type="submit" name="after" value="back">Speichern &amp; zurück</button>
            <?php if ($previewUrl): ?><a class="btn btn--ghost btn--block" href="<?= e(url($previewUrl)) ?>#block-<?= (int) $block['id'] ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau öffnen</a><?php endif; ?>
            <p class="muted small">Zuletzt geändert: <?= e(format_date($block['updated_at'], true)) ?></p>
        </section>
    </aside>
</form>
