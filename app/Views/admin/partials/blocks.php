<?php
/** @var array $blocks @var string $owner @var int $ownerId */
use App\Repositories\BlockRepository;
use App\Services\BlockTypes;

$types = BlockTypes::all();
$library = BlockRepository::library();
$last = count($blocks) - 1;
?>
<?php if (!$blocks): ?>
    <p class="empty">Diese Seite hat noch keine Inhaltsblöcke. Fügen Sie unten den ersten Block hinzu.</p>
<?php else: ?>
<ol class="block-list" data-reorder="<?= e(url('/admin/blocks/reorder')) ?>" data-owner="<?= e($owner) ?>" data-owner-id="<?= (int) $ownerId ?>">
    <?php foreach ($blocks as $i => $b): $def = $types[$b['type']] ?? ['label' => $b['type'], 'icon' => 'square']; ?>
        <li class="block-item<?= (int) $b['is_visible'] ? '' : ' is-hidden' ?>" id="block-<?= (int) $b['id'] ?>" data-id="<?= (int) $b['id'] ?>" draggable="true">
            <span class="block-item__handle" aria-hidden="true" title="Ziehen zum Sortieren"><?= icon('grip-vertical') ?></span>
            <span class="block-item__icon" aria-hidden="true"><?= icon($def['icon']) ?></span>
            <div class="block-item__body">
                <a class="block-item__title" href="<?= e(url('/admin/blocks/' . $b['id'])) ?>"><?= e($def['label']) ?></a>
                <span class="block-item__summary"><?= e(BlockTypes::summary($b['type'], $b['data']) ?: '—') ?></span>
                <?php if (!(int) $b['is_visible']): ?><span class="badge badge--amber">Ausgeblendet</span><?php endif; ?>
                <?php if ($b['admin_note'] !== ''): ?><span class="block-item__note"><?= icon('info', 'icon icon--xs') ?> <?= e($b['admin_note']) ?></span><?php endif; ?>
            </div>
            <div class="block-item__actions">
                <a class="icon-btn" href="<?= e(url('/admin/blocks/' . $b['id'])) ?>" title="Bearbeiten"><?= icon('pencil') ?><span class="sr-only">Bearbeiten</span></a>
                <form method="post" action="<?= e(url('/admin/blocks/' . $b['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="up"><button class="icon-btn" type="submit" title="Nach oben"<?= $i === 0 ? ' disabled' : '' ?>><?= icon('arrow-up') ?><span class="sr-only">Nach oben</span></button></form>
                <form method="post" action="<?= e(url('/admin/blocks/' . $b['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="down"><button class="icon-btn" type="submit" title="Nach unten"<?= $i === $last ? ' disabled' : '' ?>><?= icon('arrow-down') ?><span class="sr-only">Nach unten</span></button></form>
                <form method="post" action="<?= e(url('/admin/blocks/' . $b['id'] . '/toggle')) ?>"><?= csrf_field() ?><button class="icon-btn" type="submit" title="<?= (int) $b['is_visible'] ? 'Ausblenden' : 'Einblenden' ?>"><?= icon((int) $b['is_visible'] ? 'eye-off' : 'eye') ?><span class="sr-only"><?= (int) $b['is_visible'] ? 'Ausblenden' : 'Einblenden' ?></span></button></form>
                <form method="post" action="<?= e(url('/admin/blocks/' . $b['id'] . '/duplicate')) ?>"><?= csrf_field() ?><button class="icon-btn" type="submit" title="Duplizieren"><?= icon('copy') ?><span class="sr-only">Duplizieren</span></button></form>
                <form method="post" action="<?= e(url('/admin/blocks/' . $b['id'] . '/delete')) ?>" data-confirm="Block „<?= e($def['label']) ?>“ endgültig löschen?"><?= csrf_field() ?><button class="icon-btn icon-btn--danger" type="submit" title="Löschen"><?= icon('trash-2') ?><span class="sr-only">Löschen</span></button></form>
            </div>
        </li>
    <?php endforeach; ?>
</ol>
<?php endif; ?>

<div class="add-block">
    <form method="post" action="<?= e(url('/admin/blocks/add')) ?>" class="add-block__form">
        <?= csrf_field() ?>
        <input type="hidden" name="owner" value="<?= e($owner) ?>"><input type="hidden" name="owner_id" value="<?= (int) $ownerId ?>">
        <label for="add-type-<?= e($owner) ?>">Neuen Block hinzufügen</label>
        <div class="input-row">
            <select id="add-type-<?= e($owner) ?>" name="type" required>
                <option value="">Blocktyp wählen …</option>
                <?php foreach ($types as $k => $t): if ($k === 'library_ref') { continue; } ?>
                    <option value="<?= e($k) ?>"><?= e($t['label']) ?> – <?= e(str_excerpt($t['desc'], 60)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn--primary" type="submit"><?= icon('plus') ?> Hinzufügen</button>
        </div>
    </form>
    <?php if ($library): ?>
        <form method="post" action="<?= e(url('/admin/blocks/add')) ?>" class="add-block__form">
            <?= csrf_field() ?>
            <input type="hidden" name="owner" value="<?= e($owner) ?>"><input type="hidden" name="owner_id" value="<?= (int) $ownerId ?>">
            <label for="add-lib-<?= e($owner) ?>">Wiederverwendbaren Baustein einfügen</label>
            <div class="input-row">
                <select id="add-lib-<?= e($owner) ?>" name="library_id" required>
                    <option value="">Baustein wählen …</option>
                    <?php foreach ($library as $lb): if ($lb['type'] === 'library_ref') { continue; } ?>
                        <option value="<?= (int) $lb['id'] ?>"><?= e($lb['library_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn--secondary" type="submit"><?= icon('copy') ?> Einfügen</button>
            </div>
        </form>
    <?php endif; ?>
</div>
