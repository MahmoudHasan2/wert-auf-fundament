<?php
/** @var ?array $service @var array $related @var array $old @var array $errors */
use App\Controllers\Admin\ServiceAdminController;
use App\Core\View;
use App\Repositories\ServiceRepository;
use App\Services\BlockTypes;

$s = $service;
$v = fn (string $k, $d = '') => $old[$k] ?? ($s[$k] ?? $d);
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field__error">' . e($errors[$k]) . '</p>' : '';
$relatedSel = isset($old['related']) ? array_map('intval', (array) $old['related']) : $related;
$parentSel = (int) ($old['parent_id'] ?? ($s['parent_id'] ?? ($parentId ?? 0)));
$action = $s ? url('/admin/services/' . $s['id']) : url('/admin/services');
?>
<?php if ($s): ?>
<div class="page-head">
    <p class="page-head__meta"><span class="pill pill--<?= e($s['status']) ?>"><?= e(status_label($s['status'])) ?></span> <code><?= e(ServiceRepository::path($s)) ?></code></p>
    <div class="btn-row">
        <a class="btn btn--secondary" href="<?= e(url('/admin/services/' . $s['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau</a>
        <?php if (ServiceRepository::isPublished($s)): ?><a class="btn btn--ghost" href="<?= e(url(ServiceRepository::path($s))) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?> Live ansehen</a><?php endif; ?>
    </div>
</div>
<?php if ($s['admin_note'] !== ''): ?><div class="alert alert--warning"><?= icon('triangle-alert') ?><p><strong>Prüfvermerk:</strong> <?= e($s['admin_note']) ?></p></div><?php endif; ?>
<?php endif; ?>

<div class="editor-grid">
    <div class="stack">
        <form method="post" action="<?= e($action) ?>" class="card form-stack" id="service-form">
            <?= csrf_field() ?>
            <h2 class="card__title">Stammdaten</h2>
            <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
                <label for="s-title">Name der Leistung <span class="req">*</span></label>
                <input id="s-title" type="text" name="title" required maxlength="190" value="<?= e($v('title')) ?>" data-slug-source>
                <?= $err('title') ?>
            </div>
            <div class="grid-2">
                <div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
                    <label for="s-slug">URL-Bestandteil</label>
                    <input id="s-slug" type="text" name="slug" maxlength="120" value="<?= e($v('slug')) ?>" data-slug-target>
                    <p class="field__help">Leer = aus dem Namen erzeugen.</p>
                    <?= $err('slug') ?>
                </div>
                <div class="field<?= isset($errors['parent_id']) ? ' has-error' : '' ?>">
                    <label for="s-parent">Übergeordnete Leistung</label>
                    <select id="s-parent" name="parent_id">
                        <option value="0">– keine (Hauptleistung) –</option>
                        <?php foreach (ServiceRepository::all() as $p): if ($p['parent_id'] !== null || ($s && (int) $p['id'] === (int) $s['id'])) { continue; } ?>
                            <option value="<?= (int) $p['id'] ?>"<?= $parentSel === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('parent_id') ?>
                </div>
            </div>
            <div class="field">
                <label for="s-teaser">Kurzbeschreibung (für Karten und Übersichten)</label>
                <textarea id="s-teaser" name="teaser" rows="3" maxlength="600"><?= e($v('teaser')) ?></textarea>
            </div>
            <div class="field">
                <label for="s-intro">Einleitung im Kopfbereich der Detailseite</label>
                <textarea id="s-intro" name="intro" rows="4" maxlength="1200"><?= e($v('intro')) ?></textarea>
            </div>
            <div class="grid-2">
                <?= View::partial('admin/partials/field', ['f' => ['type' => 'media', 'label' => 'Bild (Karte & Kopfbereich)'], 'name' => 'image_media_id', 'value' => $v('image_media_id')]) ?>
                <div class="field">
                    <label for="s-icon">Symbol</label>
                    <select id="s-icon" name="icon" data-icon-select>
                        <option value="">– kein Symbol –</option>
                        <?php foreach (BlockTypes::ICONS as $k => $label): ?><option value="<?= e($k) ?>"<?= $v('icon') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                    <p class="icon-preview" data-icon-preview><?= $v('icon') !== '' ? icon($v('icon')) : '' ?></p>
                </div>
            </div>
            <div class="grid-2">
                <div class="field">
                    <label for="s-status">Status</label>
                    <select id="s-status" name="status">
                        <?php foreach (ServiceAdminController::STATUSES as $k => $label): ?><option value="<?= e($k) ?>"<?= $v('status', 'draft') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select>
                    <p class="field__help">Archiviert = nicht öffentlich, bleibt aber erhalten.</p>
                </div>
                <div class="field field--check">
                    <input type="hidden" name="is_featured" value="">
                    <label class="check"><input type="checkbox" name="is_featured" value="1"<?= $v('is_featured') ? ' checked' : '' ?>> Auf der Startseite hervorheben</label>
                </div>
            </div>
            <fieldset class="field">
                <legend>Verwandte Leistungen (werden unten auf der Detailseite verlinkt)</legend>
                <div class="checklist-box">
                    <?php foreach (ServiceRepository::all() as $o): if ($s && (int) $o['id'] === (int) $s['id']) { continue; } ?>
                        <label class="check<?= $o['parent_id'] ? ' check--indent' : '' ?>"><input type="checkbox" name="related[]" value="<?= (int) $o['id'] ?>"<?= in_array((int) $o['id'], $relatedSel, true) ? ' checked' : '' ?>> <?= e($o['title']) ?></label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <details class="details">
                <summary>Suchmaschinen (SEO)</summary>
                <div class="field"><label for="s-mt">SEO-Titel</label><input id="s-mt" type="text" name="meta_title" maxlength="190" value="<?= e($v('meta_title')) ?>" data-counter="60"></div>
                <div class="field"><label for="s-md">Meta-Beschreibung</label><textarea id="s-md" name="meta_description" rows="3" maxlength="320" data-counter="160"><?= e($v('meta_description')) ?></textarea></div>
            </details>
            <div class="field">
                <label for="s-note">Interner Prüfvermerk</label>
                <textarea id="s-note" name="admin_note" rows="2" maxlength="500"><?= e($v('admin_note')) ?></textarea>
                <p class="field__help">Wird in der Checkliste angezeigt, bis das Feld geleert wird.</p>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary" type="submit"><?= icon('save') ?> <?= $s ? 'Speichern' : 'Leistung anlegen' ?></button>
                <a class="btn btn--ghost" href="<?= e(url('/admin/services')) ?>">Zur Übersicht</a>
            </div>
        </form>
    </div>

    <?php if ($s): ?>
    <aside class="stack">
        <section class="card" id="bloecke">
            <h2 class="card__title">Inhalte der Detailseite</h2>
            <?= View::partial('admin/partials/blocks', ['blocks' => $blocks, 'owner' => 'service', 'ownerId' => (int) $s['id']]) ?>
        </section>
        <section class="card">
            <h2 class="card__title">Löschen</h2>
            <?php $hard = array_filter($blockers, fn ($b) => !str_contains($b, 'Referenz')); ?>
            <?php if ($hard): ?>
                <p class="muted small">Löschen nicht möglich: <?= e(implode(' ', $hard)) ?> Setzen Sie die Leistung stattdessen auf „Archiviert“.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/services/' . $s['id'] . '/delete')) ?>" data-confirm="Leistung „<?= e($s['title']) ?>“ mit allen Inhaltsblöcken endgültig löschen?<?= $projectCount ? ' ' . $projectCount . ' Referenz(en) verlieren ihre Zuordnung.' : '' ?>">
                    <?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit"><?= icon('trash-2') ?> Leistung löschen</button>
                </form>
            <?php endif; ?>
        </section>
    </aside>
    <?php endif; ?>
</div>
