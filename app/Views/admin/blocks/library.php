<?php
/** @var array $blocks @var array $usage @var array $errors */
use App\Services\BlockTypes;

$types = BlockTypes::all();
?>
<p class="lead">Bausteine sind Inhaltsblöcke, die auf mehreren Seiten eingebunden werden – z. B. eine einheitliche Kontakt-Aufforderung. Änderungen wirken überall gleichzeitig.</p>
<div class="editor-grid">
    <section class="card card--flush">
        <?php if (!$blocks): ?>
            <p class="empty">Noch keine Bausteine angelegt.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Name</th><th>Typ</th><th>Verwendet</th><th>Status</th><th><span class="sr-only">Aktionen</span></th></tr></thead>
                <tbody>
                <?php foreach ($blocks as $b): ?>
                    <tr>
                        <td data-label="Name"><a href="<?= e(url('/admin/blocks/' . $b['id'])) ?>"><strong><?= e($b['library_name']) ?></strong></a></td>
                        <td data-label="Typ"><?= e($types[$b['type']]['label'] ?? $b['type']) ?></td>
                        <td data-label="Verwendet"><?= (int) $usage[$b['id']] ?>×</td>
                        <td data-label="Status"><span class="pill pill--<?= (int) $b['is_visible'] ? 'published' : 'draft' ?>"><?= (int) $b['is_visible'] ? 'Sichtbar' : 'Ausgeblendet' ?></span></td>
                        <td class="table__actions">
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('/admin/blocks/' . $b['id'])) ?>"><?= icon('pencil') ?> Bearbeiten</a>
                            <?php if ((int) $usage[$b['id']] === 0): ?>
                                <form method="post" action="<?= e(url('/admin/blocks/' . $b['id'] . '/delete')) ?>" data-confirm="Baustein „<?= e($b['library_name']) ?>“ löschen?"><?= csrf_field() ?><button class="btn btn--ghost btn--sm btn--danger-text" type="submit"><?= icon('trash-2') ?> Löschen</button></form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <aside>
        <form method="post" action="<?= e(url('/admin/library')) ?>" class="card form-stack">
            <?= csrf_field() ?>
            <h2 class="card__title">Neuer Baustein</h2>
            <div class="field<?= isset($errors['library_name']) ? ' has-error' : '' ?>">
                <label for="nl-name">Name (intern)</label>
                <input id="nl-name" type="text" name="library_name" maxlength="160" required>
                <?php if (isset($errors['library_name'])): ?><p class="field__error"><?= e($errors['library_name']) ?></p><?php endif; ?>
            </div>
            <div class="field">
                <label for="nl-type">Typ</label>
                <select id="nl-type" name="type" required>
                    <?php foreach ($types as $k => $t): if (in_array($k, ['library_ref', 'contact_form'], true)) { continue; } ?>
                        <option value="<?= e($k) ?>"><?= e($t['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn--primary" type="submit"><?= icon('plus') ?> Anlegen</button>
        </form>
    </aside>
</div>
