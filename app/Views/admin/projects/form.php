<?php
/** @var ?array $project @var array $images @var array $old @var array $errors */
use App\Core\View;
use App\Repositories\ProjectRepository;
use App\Repositories\ServiceRepository;
use App\Services\Media;

$p = $project;
$v = fn (string $k, $d = '') => $old[$k] ?? ($p[$k] ?? $d);
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field__error">' . e($errors[$k]) . '</p>' : '';
?>
<?php if ($p): ?>
<div class="page-head">
    <p class="page-head__meta"><span class="pill pill--<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span> <code><?= e(ProjectRepository::path($p)) ?></code></p>
    <div class="btn-row"><a class="btn btn--secondary" href="<?= e(url('/admin/projects/' . $p['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Vorschau</a></div>
</div>
<?php endif; ?>
<div class="editor-grid">
    <form method="post" action="<?= e($p ? url('/admin/projects/' . $p['id']) : url('/admin/projects')) ?>" class="card form-stack">
        <?= csrf_field() ?>
        <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>"><label for="pr-title">Titel <span class="req">*</span></label><input id="pr-title" type="text" name="title" required maxlength="190" value="<?= e($v('title')) ?>" data-slug-source><?= $err('title') ?></div>
        <div class="grid-2">
            <div class="field<?= isset($errors['slug']) ? ' has-error' : '' ?>"><label for="pr-slug">URL-Bestandteil</label><input id="pr-slug" type="text" name="slug" maxlength="120" value="<?= e($v('slug')) ?>" data-slug-target><?= $err('slug') ?></div>
            <div class="field"><label for="pr-service">Leistungsbereich (Kategorie)</label>
                <select id="pr-service" name="service_id"><option value="0">– keiner –</option>
                    <?php foreach (ServiceRepository::all() as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $v('service_id') === (int) $s['id'] ? ' selected' : '' ?>><?= $s['parent_id'] ? '↳ ' : '' ?><?= e($s['title']) ?></option><?php endforeach; ?>
                </select></div>
        </div>
        <div class="grid-2">
            <div class="field"><label for="pr-loc">Ort (optional)</label><input id="pr-loc" type="text" name="location" maxlength="160" value="<?= e($v('location')) ?>"><p class="field__help">Nur angeben, wenn der Auftraggeber der Veröffentlichung zugestimmt hat.</p></div>
            <div class="field"><label for="pr-period">Zeitraum (optional)</label><input id="pr-period" type="text" name="period" maxlength="80" value="<?= e($v('period')) ?>" placeholder="z. B. Frühjahr 2026"></div>
        </div>
        <div class="field"><label for="pr-sum">Kurzbeschreibung</label><textarea id="pr-sum" name="summary" rows="3" maxlength="1000"><?= e($v('summary')) ?></textarea></div>
        <div class="field"><label for="pr-body">Projektbeschreibung</label><textarea id="pr-body" name="body" rows="10" data-richtext><?= e($v('body')) ?></textarea></div>
        <?= View::partial('admin/partials/field', ['f' => ['type' => 'media', 'label' => 'Titelbild'], 'name' => 'cover_media_id', 'value' => $v('cover_media_id'), 'error' => $errors['cover_media_id'] ?? null]) ?>
        <div class="grid-2">
            <div class="field"><label for="pr-status">Status</label>
                <select id="pr-status" name="status">
                    <?php foreach (['draft' => 'Entwurf', 'published' => 'Veröffentlicht', 'archived' => 'Archiviert'] as $k => $l): ?><option value="<?= $k ?>"<?= $v('status', 'draft') === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                </select></div>
            <div class="field field--check"><input type="hidden" name="is_featured" value=""><label class="check"><input type="checkbox" name="is_featured" value="1"<?= $v('is_featured') ? ' checked' : '' ?>> Hervorheben (Startseite)</label></div>
        </div>
        <details class="details"><summary>Suchmaschinen (SEO)</summary>
            <div class="field"><label for="pr-mt">SEO-Titel</label><input id="pr-mt" type="text" name="meta_title" maxlength="190" value="<?= e($v('meta_title')) ?>" data-counter="60"></div>
            <div class="field"><label for="pr-md">Meta-Beschreibung</label><textarea id="pr-md" name="meta_description" rows="3" maxlength="320" data-counter="160"><?= e($v('meta_description')) ?></textarea></div>
        </details>
        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= icon('save') ?> <?= $p ? 'Speichern' : 'Referenz anlegen' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('/admin/projects')) ?>">Zur Übersicht</a>
        </div>
    </form>
    <?php if ($p): ?>
    <aside class="stack">
        <section class="card">
            <div class="card__head"><h2 class="card__title">Projektfotos (<?= count($images) ?>)</h2>
                <?php if ($p['gallery_id']): ?><a class="btn btn--secondary btn--sm" href="<?= e(url('/admin/galleries/' . $p['gallery_id'])) ?>"><?= icon('images') ?> Fotos verwalten</a><?php endif; ?></div>
            <?php if (!$images): ?>
                <p class="empty">Noch keine Fotos. Über „Fotos verwalten“ hinzufügen und sortieren.</p>
            <?php else: ?>
                <ul class="thumb-grid">
                    <?php foreach (array_slice($images, 0, 12) as $img): $m = Media::find((int) $img['media_id']); if (!$m) { continue; } ?>
                        <li><img src="<?= e(Media::url($m, 'sm')) ?>" alt="<?= e($m['alt']) ?>" loading="lazy"></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <section class="card">
            <h2 class="card__title">Löschen</h2>
            <form method="post" action="<?= e(url('/admin/projects/' . $p['id'] . '/delete')) ?>" data-confirm="Referenz „<?= e($p['title']) ?>“ endgültig löschen? Die Bilder bleiben in der Mediathek.">
                <?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit"><?= icon('trash-2') ?> Referenz löschen</button>
            </form>
            <p class="muted small">Alternativ den Status „Archiviert“ wählen, um die Referenz nur auszublenden.</p>
        </section>
    </aside>
    <?php endif; ?>
</div>
