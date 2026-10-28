<?php /** @var array $categories @var array $faqs */ ?>
<div class="toolbar">
    <p class="muted">Fragen werden über den Block „Häufige Fragen“ auf beliebigen Seiten eingebunden – wahlweise alle oder nur eine Kategorie.</p>
    <a class="btn btn--primary" href="<?= e(url('/admin/faq/create')) ?>"><?= icon('plus') ?> Neue Frage</a>
</div>
<div class="editor-grid">
    <section class="card card--flush">
        <?php if (!$faqs): ?>
            <p class="empty">Noch keine Fragen angelegt.</p>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Frage</th><th>Kategorie</th><th>Status</th><th>Reihenfolge</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
            <tbody>
            <?php foreach ($faqs as $f): ?>
                <tr id="faq-<?= (int) $f['id'] ?>">
                    <td data-label="Frage"><a href="<?= e(url('/admin/faq/' . $f['id'])) ?>"><strong><?= e($f['question']) ?></strong></a></td>
                    <td data-label="Kategorie"><?= e($f['category_name'] ?? '–') ?></td>
                    <td data-label="Status"><span class="pill pill--<?= e($f['status']) ?>"><?= e(status_label($f['status'])) ?></span></td>
                    <td data-label="Reihenfolge" class="table__order">
                        <form method="post" action="<?= e(url('/admin/faq/' . $f['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="icon-btn" type="submit" title="Nach oben"><?= icon('arrow-up') ?><span class="sr-only">Nach oben</span></button></form>
                        <form method="post" action="<?= e(url('/admin/faq/' . $f['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="icon-btn" type="submit" title="Nach unten"><?= icon('arrow-down') ?><span class="sr-only">Nach unten</span></button></form>
                    </td>
                    <td class="table__actions">
                        <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/faq/' . $f['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a>
                        <form method="post" action="<?= e(url('/admin/faq/' . $f['id'] . '/delete')) ?>" data-confirm="Frage löschen?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash-2') ?> Löschen</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
    <aside class="stack">
        <section class="card">
            <h2 class="card__title">Kategorien</h2>
            <?php foreach ($categories as $c): ?>
                <div class="inline-edit">
                    <form method="post" action="<?= e(url('/admin/faq-categories/' . $c['id'])) ?>" class="inline-form">
                        <?= csrf_field() ?>
                        <label class="sr-only" for="fc-<?= (int) $c['id'] ?>">Name</label>
                        <input id="fc-<?= (int) $c['id'] ?>" type="text" name="name" value="<?= e($c['name']) ?>" maxlength="120">
                        <label class="sr-only" for="fco-<?= (int) $c['id'] ?>">Sortierung</label>
                        <input id="fco-<?= (int) $c['id'] ?>" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" class="input-xs" title="Sortierung">
                        <button class="icon-btn" type="submit" title="Speichern"><?= icon('save') ?><span class="sr-only">Speichern</span></button>
                    </form>
                    <form method="post" action="<?= e(url('/admin/faq-categories/' . $c['id'] . '/delete')) ?>" data-confirm="Kategorie „<?= e($c['name']) ?>“ löschen?"><?= csrf_field() ?><button class="icon-btn icon-btn--danger" type="submit" title="Löschen"><?= icon('trash-2') ?><span class="sr-only">Löschen</span></button></form>
                </div>
            <?php endforeach; ?>
            <form method="post" action="<?= e(url('/admin/faq-categories')) ?>" class="inline-form inline-form--new">
                <?= csrf_field() ?>
                <label class="sr-only" for="fc-new">Neue Kategorie</label>
                <input id="fc-new" type="text" name="name" placeholder="Neue Kategorie" maxlength="120" required>
                <button class="btn btn--secondary btn--sm" type="submit"><?= icon('plus') ?> Anlegen</button>
            </form>
        </section>
    </aside>
</div>
