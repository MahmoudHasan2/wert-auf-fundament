<?php
/** @var ?array $page @var array $old @var array $errors */
use App\Repositories\PageRepository;

$v = fn (string $k, $default = '') => $old[$k] ?? ($page[$k] ?? $default);
$isSystem = $page && $page['system_key'] !== null;
$locked = $page && (PageRepository::isLegal($page) || $page['system_key'] === 'home');
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field__error">' . e($errors[$k]) . '</p>' : '';
$pub = $v('published_at');
?>
<div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
    <label for="p-title">Seitentitel <span class="req" aria-hidden="true">*</span></label>
    <input id="p-title" type="text" name="title" required maxlength="190" value="<?= e($v('title')) ?>" data-slug-source>
    <p class="field__help">Wird in Menüs, Brotkrumen und – ohne eigenen SEO-Titel – im Browser-Tab angezeigt.</p>
    <?= $err('title') ?>
</div>
<div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>">
    <label for="p-slug">URL-Pfad</label>
    <div class="input-prefix"><span><?= e(rtrim(abs_url('/'), '/')) ?>/</span>
        <input id="p-slug" type="text" name="slug" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= e($v('slug')) ?>"<?= $isSystem ? ' readonly aria-readonly="true"' : '' ?> data-slug-target>
    </div>
    <p class="field__help"><?= $isSystem ? 'Systemseite – die Adresse ist fest vorgegeben.' : 'Leer lassen, um sie automatisch aus dem Titel zu erzeugen. Nur Kleinbuchstaben, Ziffern und Bindestriche.' ?></p>
    <?= $err('slug') ?>
</div>
<div class="grid-2">
    <div class="field">
        <label for="p-template">Vorlage</label>
        <select id="p-template" name="template">
            <?php foreach (PageRepository::TEMPLATES as $k => $label): ?>
                <option value="<?= e($k) ?>"<?= $v('template', 'standard') === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field<?= isset($errors['status']) ? ' has-error' : '' ?>">
        <label for="p-status">Status</label>
        <select id="p-status" name="status">
            <option value="draft"<?= $v('status', 'draft') === 'draft' ? ' selected' : '' ?><?= $locked ? ' disabled' : '' ?>>Entwurf (nicht öffentlich)</option>
            <option value="published"<?= $v('status') === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
        </select>
        <?php if ($locked): ?><p class="field__help">Diese Seite muss immer veröffentlicht sein.</p><?php endif; ?>
        <?= $err('status') ?>
    </div>
</div>
<div class="field">
    <label for="p-pub">Veröffentlichen ab (optional)</label>
    <input id="p-pub" type="datetime-local" name="published_at" value="<?= e($pub ? date('Y-m-d\TH:i', (int) strtotime((string) $pub)) : '') ?>"<?= $locked ? ' disabled' : '' ?>>
    <p class="field__help">Bei Status „Veröffentlicht“ wird die Seite erst ab diesem Zeitpunkt angezeigt. Leer = sofort.</p>
</div>
<details class="details"<?= isset($errors['meta_title']) ? ' open' : '' ?>>
    <summary>Suchmaschinen &amp; Vorschau (SEO)</summary>
    <div class="field">
        <label for="p-mt">SEO-Titel</label>
        <input id="p-mt" type="text" name="meta_title" maxlength="190" value="<?= e($v('meta_title')) ?>" data-counter="60">
        <p class="field__help">Empfohlen: bis ca. 60 Zeichen. Leer = Seitentitel.</p>
    </div>
    <div class="field">
        <label for="p-md">Meta-Beschreibung</label>
        <textarea id="p-md" name="meta_description" rows="3" maxlength="320" data-counter="160"><?= e($v('meta_description')) ?></textarea>
        <p class="field__help">Empfohlen: 120–160 Zeichen. Erscheint als Text in Suchergebnissen.</p>
    </div>
    <?= \App\Core\View::partial('admin/partials/field', ['f' => ['type' => 'media', 'label' => 'Vorschaubild für soziale Netzwerke (optional)'], 'name' => 'og_media_id', 'value' => $v('og_media_id')]) ?>
    <div class="field field--check">
        <label class="check"><input type="checkbox" name="noindex" value="1"<?= $v('noindex') ? ' checked' : '' ?>> Diese Seite nicht in Suchmaschinen anzeigen (noindex)</label>
    </div>
</details>
