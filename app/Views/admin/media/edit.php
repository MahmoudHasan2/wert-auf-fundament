<?php
/** @var array $m @var array $usage @var int $maxMb @var array $errors */
use App\Services\Media;
?>
<p class="breadcrumb-admin"><a href="<?= e(url('/admin/media')) ?>"><?= icon('arrow-left') ?> Zur Mediathek</a></p>
<div class="editor-grid">
    <div class="stack">
        <section class="card">
            <figure class="media-preview">
                <img src="<?= e(Media::url($m, 'md')) ?>" alt="<?= e($m['alt']) ?>">
                <figcaption class="muted small"><?= e($m['original_name']) ?> · <?= (int) $m['width'] ?> × <?= (int) $m['height'] ?> px · <?= e(strtoupper($m['ext'])) ?> · <?= number_format((int) $m['bytes'] / 1024, 0, ',', '.') ?> KB (alle Größen) · hochgeladen am <?= e(format_date($m['created_at'], true)) ?></figcaption>
            </figure>
        </section>
        <form method="post" action="<?= e(url('/admin/media/' . $m['id'])) ?>" class="card form-stack">
            <?= csrf_field() ?>
            <h2 class="card__title">Bildangaben</h2>
            <div class="field"><label for="me-alt">Alternativtext</label><input id="me-alt" type="text" name="alt" maxlength="255" value="<?= e($m['alt']) ?>"><p class="field__help">Beschreibt den Bildinhalt für Screenreader und Suchmaschinen, z. B. „Bagger hebt eine Baugrube aus“. Rein dekorative Bilder: leer lassen.</p></div>
            <div class="field"><label for="me-cap">Standard-Bildunterschrift</label><input id="me-cap" type="text" name="caption" maxlength="255" value="<?= e($m['caption']) ?>"></div>
            <div class="grid-2">
                <div class="field"><label for="me-credit">Bildnachweis / Urheber</label><input id="me-credit" type="text" name="credit" maxlength="255" value="<?= e($m['credit']) ?>"></div>
                <div class="field"><label for="me-lic">Lizenz</label><input id="me-lic" type="text" name="license" maxlength="80" value="<?= e($m['license']) ?>" placeholder="z. B. CC BY-SA 4.0"></div>
            </div>
            <div class="field<?= isset($errors['source_url']) ? ' has-error' : '' ?>"><label for="me-src">Quelle (URL)</label><input id="me-src" type="url" name="source_url" maxlength="500" value="<?= e($m['source_url']) ?>"><?php if (isset($errors['source_url'])): ?><p class="field__error"><?= e($errors['source_url']) ?></p><?php endif; ?>
                <?php if ($m['source_url'] !== ''): ?><p class="field__help"><a href="<?= e($m['source_url']) ?>" target="_blank" rel="noopener noreferrer">Quelle öffnen <?= icon('external-link', 'icon icon--xs') ?></a></p><?php endif; ?></div>
            <div class="field field--check"><input type="hidden" name="is_placeholder" value=""><label class="check"><input type="checkbox" name="is_placeholder" value="1"<?= (int) $m['is_placeholder'] ? ' checked' : '' ?>> Übergangs-/Platzhalterbild (vor dem Livegang ersetzen)</label></div>
            <button class="btn btn--primary" type="submit"><?= icon('save') ?> Speichern</button>
        </form>
    </div>
    <aside class="stack">
        <section class="card">
            <h2 class="card__title">Verwendung (<?= count($usage) ?>)</h2>
            <?php if (!$usage): ?>
                <p class="empty">Dieses Bild wird derzeit nicht verwendet.</p>
            <?php else: ?>
                <ul class="mini-list"><?php foreach ($usage as $u): ?><li><a href="<?= e(url($u['url'])) ?>"><?= e($u['label']) ?></a></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </section>
        <section class="card">
            <h2 class="card__title">Bilddatei ersetzen</h2>
            <form method="post" action="<?= e(url('/admin/media/' . $m['id'] . '/replace')) ?>" enctype="multipart/form-data" class="form-stack" data-confirm="Die Bilddatei wird an <?= max(1, count($usage)) ?> Stelle(n) ersetzt. Fortfahren?">
                <?= csrf_field() ?>
                <input type="file" name="file" accept="image/jpeg,image/png,image/webp" required aria-label="Neue Bilddatei">
                <p class="field__help">Das neue Bild erscheint automatisch überall, wo dieses Bild verwendet wird. Max. <?= $maxMb ?> MB.</p>
                <button class="btn btn--secondary" type="submit"><?= icon('refresh-cw') ?> Ersetzen</button>
            </form>
        </section>
        <section class="card">
            <h2 class="card__title">Löschen</h2>
            <?php if ($usage): ?>
                <p class="muted small">Das Bild wird noch verwendet und kann erst gelöscht werden, wenn es an den oben genannten Stellen entfernt wurde.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/media/' . $m['id'] . '/delete')) ?>" data-confirm="Bild endgültig löschen?"><?= csrf_field() ?><button class="btn btn--danger btn--block" type="submit"><?= icon('trash-2') ?> Bild löschen</button></form>
            <?php endif; ?>
        </section>
    </aside>
</div>
