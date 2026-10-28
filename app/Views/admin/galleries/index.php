<?php
/** @var array $galleries @var array $errors */
use App\Services\Media;
?>
<div class="editor-grid">
    <section class="card card--flush">
        <?php if (!$galleries): ?>
            <p class="empty">Noch keine Galerien. Galerien können über den Block „Bildergalerie“ auf Seiten eingebunden werden; jede Referenz besitzt automatisch eine eigene Galerie.</p>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>Galerie</th><th>Bilder</th><th>Verwendet in</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
            <tbody>
            <?php foreach ($galleries as $g): $m = Media::find((int) $g['first']); ?>
                <tr>
                    <td data-label="Galerie"><div class="cell-media"><?php if ($m): ?><img src="<?= e(Media::url($m, 'sm')) ?>" alt="" width="64" height="44"><?php else: ?><span class="cell-media__ph"><?= icon('images') ?></span><?php endif; ?><a href="<?= e(url('/admin/galleries/' . $g['id'])) ?>"><strong><?= e($g['name']) ?></strong></a></div></td>
                    <td data-label="Bilder"><?= (int) $g['item_count'] ?></td>
                    <td data-label="Verwendet in"><?= $g['usage'] ? e(implode(', ', array_column($g['usage'], 'label'))) : '<span class="muted">nicht verwendet</span>' ?></td>
                    <td class="table__actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/galleries/' . $g['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
    <aside>
        <form method="post" action="<?= e(url('/admin/galleries')) ?>" class="card form-stack">
            <?= csrf_field() ?>
            <h2 class="card__title">Neue Galerie</h2>
            <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>"><label for="g-name">Name</label><input id="g-name" type="text" name="name" maxlength="160" required><?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?></div>
            <button class="btn btn--primary" type="submit"><?= icon('plus') ?> Anlegen</button>
        </form>
    </aside>
</div>
