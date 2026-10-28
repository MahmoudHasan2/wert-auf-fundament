<?php use App\Repositories\PageRepository; use App\Repositories\ServiceRepository; ?>
<dialog class="modal" id="media-modal" aria-labelledby="media-modal-title">
    <div class="modal__head">
        <h2 id="media-modal-title">Bild auswählen</h2>
        <button type="button" class="btn btn--ghost btn--sm" data-modal-close aria-label="Schließen"><?= icon('x') ?></button>
    </div>
    <div class="modal__tools">
        <label class="sr-only" for="media-modal-search">Medien durchsuchen</label>
        <input type="search" id="media-modal-search" placeholder="Suchen nach Dateiname, Alternativtext …" data-media-search>
        <label class="btn btn--secondary btn--sm upload-btn"><?= icon('upload') ?> Neues Bild hochladen<input type="file" accept="image/jpeg,image/png,image/webp" data-media-upload hidden></label>
    </div>
    <p class="modal__status" role="status" data-media-status></p>
    <ul class="media-grid media-grid--picker" data-media-results></ul>
    <div class="modal__foot">
        <button type="button" class="btn btn--ghost" data-media-more hidden>Weitere laden</button>
        <button type="button" class="btn btn--primary" data-media-confirm hidden>Auswahl übernehmen</button>
    </div>
</dialog>
<datalist id="internal-links">
    <?php foreach (PageRepository::live() as $p): ?><option value="<?= e(PageRepository::path($p)) ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
    <?php foreach (ServiceRepository::all() as $s): ?><option value="<?= e(ServiceRepository::path($s)) ?>"><?= e($s['title']) ?></option><?php endforeach; ?>
    <option value="/kontakt#kontaktformular">Kontaktformular</option>
</datalist>
