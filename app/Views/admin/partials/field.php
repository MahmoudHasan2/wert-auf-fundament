<?php
/**
 * Renders one schema-defined form field.
 * @var array $f definition  @var string $name input name  @var mixed $value  @var ?string $error
 */
use App\Core\DB;
use App\Repositories\ContentRepository;
use App\Repositories\ServiceRepository;
use App\Services\Media;

$id = 'f-' . preg_replace('/[^a-z0-9]+/i', '-', $name);
$error = $error ?? null;
$help = $f['help'] ?? '';
$type = $f['type'];
$required = !empty($f['required']);
$describedBy = trim(($help !== '' ? $id . '-help ' : '') . ($error ? $id . '-err' : ''));
$attrs = ($required ? ' required' : '') . ($error ? ' aria-invalid="true"' : '') . ($describedBy !== '' ? ' aria-describedby="' . e($describedBy) . '"' : '');
?>
<?php if ($type === 'bool'): ?>
    <div class="field field--check">
        <input type="hidden" name="<?= e($name) ?>" value="">
        <label class="check"><input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1"<?= !empty($value) ? ' checked' : '' ?><?= $attrs ?>> <span><?= e($f['label']) ?></span></label>
        <?php if ($help !== ''): ?><p class="field__help" id="<?= e($id) ?>-help"><?= e($help) ?></p><?php endif; ?>
    </div>
<?php elseif ($type === 'repeater'): ?>
    <?php $items = is_array($value) ? array_values($value) : []; ?>
    <fieldset class="repeater" data-repeater>
        <legend><?= e($f['label']) ?> <span class="muted">(max. <?= (int) ($f['max'] ?? 50) ?>)</span></legend>
        <div class="repeater__items" data-repeater-items>
            <?php foreach (array_merge($items, [[]]) as $i => $item): $isBlank = $i === count($items); ?>
                <div class="repeater__item<?= $isBlank ? ' repeater__item--blank' : '' ?>" data-repeater-item>
                    <div class="repeater__bar">
                        <strong><?= $isBlank ? 'Neuer Eintrag' : 'Eintrag ' . ($i + 1) ?></strong>
                        <label class="repeater__order">Position <input type="number" name="<?= e($name) ?>[<?= $i ?>][_order]" value="<?= $i + 1 ?>" min="1" max="99" data-repeater-order></label>
                        <?php if (!$isBlank): ?><label class="check check--danger"><input type="checkbox" name="<?= e($name) ?>[<?= $i ?>][_remove]" value="1"> Entfernen</label><?php endif; ?>
                    </div>
                    <div class="repeater__fields">
                        <?php foreach ($f['fields'] as $sub): ?>
                            <?= \App\Core\View::partial('admin/partials/field', ['f' => $sub, 'name' => $name . '[' . $i . '][' . $sub['name'] . ']', 'value' => $item[$sub['name']] ?? ($sub['default'] ?? '')]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="field__help">Leere Einträge werden ignoriert. Reihenfolge über „Position“ festlegen.</p>
    </fieldset>
<?php else: ?>
    <div class="field<?= $error ? ' has-error' : '' ?>">
        <label for="<?= e($id) ?>"><?= e($f['label']) ?><?= $required ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
        <?php switch ($type):
            case 'textarea': ?>
                <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int) ($f['rows'] ?? 4) ?>"<?= $attrs ?>><?= e($value) ?></textarea>
                <?php break;
            case 'richtext': ?>
                <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="10" data-richtext<?= $attrs ?>><?= e($value) ?></textarea>
                <?php break;
            case 'select': ?>
                <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $attrs ?>>
                    <?php foreach ($f['options'] as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= (string) $value === (string) $k ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php break;
            case 'number': ?>
                <input type="number" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" min="<?= (int) ($f['min'] ?? 0) ?>" max="<?= (int) ($f['max'] ?? 100000) ?>"<?= $attrs ?>>
                <?php break;
            case 'link': ?>
                <input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" list="internal-links" placeholder="/kontakt oder https://…" inputmode="url"<?= $attrs ?>>
                <?php break;
            case 'email': ?>
                <input type="email" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"<?= $attrs ?>>
                <?php break;
            case 'url': ?>
                <input type="url" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="https://…"<?= $attrs ?>>
                <?php break;
            case 'media': $m = Media::find((int) $value); ?>
                <div class="media-field" data-media-field>
                    <input type="number" min="0" class="media-field__id" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= (int) $value ?: '' ?>" placeholder="Bild-ID" data-media-input>
                    <div class="media-field__preview" data-media-preview>
                        <?php if ($m): ?><img src="<?= e(Media::url($m, 'sm')) ?>" alt=""><span><?= e($m['alt'] ?: $m['original_name']) ?></span><?php else: ?><span class="muted">Kein Bild ausgewählt</span><?php endif; ?>
                    </div>
                    <div class="media-field__actions">
                        <button type="button" class="btn btn--secondary btn--sm" data-media-pick><?= icon('image') ?> Bild wählen</button>
                        <button type="button" class="btn btn--ghost btn--sm" data-media-clear>Entfernen</button>
                    </div>
                </div>
                <?php break;
            case 'services': $selected = array_map('intval', (array) $value); ?>
                <div class="checklist-box">
                    <?php foreach (ServiceRepository::tree() as $node): ?>
                        <?php foreach (array_merge([$node['service']], $node['children']) as $s): ?>
                            <label class="check<?= $s['parent_id'] ? ' check--indent' : '' ?>"><input type="checkbox" name="<?= e($name) ?>[]" value="<?= (int) $s['id'] ?>"<?= in_array((int) $s['id'], $selected, true) ? ' checked' : '' ?>> <?= e($s['title']) ?><?= $s['status'] !== 'published' ? ' <span class="muted">(' . e(status_label($s['status'])) . ')</span>' : '' ?></label>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <?php break;
            case 'gallery': ?>
                <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $attrs ?>>
                    <option value="0">– Galerie wählen –</option>
                    <?php foreach (ContentRepository::galleries() as $g): ?>
                        <option value="<?= (int) $g['id'] ?>"<?= (int) $value === (int) $g['id'] ? ' selected' : '' ?>><?= e($g['name']) ?> (<?= (int) $g['item_count'] ?> Bilder)</option>
                    <?php endforeach; ?>
                </select>
                <?php break;
            case 'faq_category': ?>
                <select id="<?= e($id) ?>" name="<?= e($name) ?>">
                    <option value="0">Alle Kategorien</option>
                    <?php foreach (ContentRepository::faqCategories() as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"<?= (int) $value === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php break;
            case 'library': ?>
                <select id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $attrs ?>>
                    <option value="0">– Baustein wählen –</option>
                    <?php foreach (DB::all("SELECT id, library_name, type FROM content_blocks WHERE library_name IS NOT NULL AND type <> 'library_ref' ORDER BY library_name") as $lb): ?>
                        <option value="<?= (int) $lb['id'] ?>"<?= (int) $value === (int) $lb['id'] ? ' selected' : '' ?>><?= e($lb['library_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php break;
            default: ?>
                <input type="text" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" maxlength="255"<?= $attrs ?>>
        <?php endswitch; ?>
        <?php if ($help !== ''): ?><p class="field__help" id="<?= e($id) ?>-help"><?= e($help) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="field__error" id="<?= e($id) ?>-err"><?= e($error) ?></p><?php endif; ?>
    </div>
<?php endif; ?>
