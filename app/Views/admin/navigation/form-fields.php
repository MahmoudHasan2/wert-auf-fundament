<?php
/** @var ?array $item @var string $menu @var array $old @var array $errors */
use App\Core\DB;
use App\Repositories\NavigationRepository;
use App\Repositories\PageRepository;
use App\Repositories\ServiceRepository;

$v = fn (string $k, $d = '') => $old[$k] ?? ($item[$k] ?? $d);
$err = fn (string $k) => isset($errors[$k]) ? '<p class="field__error">' . e($errors[$k]) . '</p>' : '';
$parents = DB::all('SELECT id, label FROM nav_items WHERE menu = ? AND parent_id IS NULL ORDER BY sort_order', [$menu]);
$type = $v('link_type', 'page');
$uid = $item ? $item['id'] : 'new';
?>
<div class="field<?= isset($errors['label']) ? ' has-error' : '' ?>"><label for="n-label-<?= $uid ?>">Beschriftung <span class="req">*</span></label><input id="n-label-<?= $uid ?>" type="text" name="label" maxlength="120" required value="<?= e($v('label')) ?>"><?= $err('label') ?></div>
<fieldset class="field">
    <legend>Linkziel</legend>
    <div class="choice-row" data-linktype>
        <?php foreach (NavigationRepository::TYPES as $k => $l): ?><label class="check"><input type="radio" name="link_type" value="<?= $k ?>"<?= $type === $k ? ' checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?>
    </div>
</fieldset>
<div class="field<?= isset($errors['page_id']) ? ' has-error' : '' ?>" data-linktype-panel="page"><label for="n-page-<?= $uid ?>">Seite</label>
    <select id="n-page-<?= $uid ?>" name="page_id"><option value="0">– Seite wählen –</option>
        <?php foreach (DB::all('SELECT * FROM pages ORDER BY title') as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $v('page_id') === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['title']) ?> (<?= e(PageRepository::path($p)) ?>)<?= PageRepository::isLive($p) ? '' : ' – nicht veröffentlicht' ?></option><?php endforeach; ?>
    </select><?= $err('page_id') ?></div>
<div class="field<?= isset($errors['service_id']) ? ' has-error' : '' ?>" data-linktype-panel="service"><label for="n-svc-<?= $uid ?>">Leistung</label>
    <select id="n-svc-<?= $uid ?>" name="service_id"><option value="0">– Leistung wählen –</option>
        <?php foreach (ServiceRepository::all() as $s): ?><option value="<?= (int) $s['id'] ?>"<?= (int) $v('service_id') === (int) $s['id'] ? ' selected' : '' ?>><?= $s['parent_id'] ? '↳ ' : '' ?><?= e($s['title']) ?></option><?php endforeach; ?>
    </select><?= $err('service_id') ?></div>
<div class="field<?= isset($errors['url']) ? ' has-error' : '' ?>" data-linktype-panel="url"><label for="n-url-<?= $uid ?>">Adresse</label><input id="n-url-<?= $uid ?>" type="text" name="url" maxlength="500" value="<?= e($v('url')) ?>" list="internal-links" placeholder="/pfad, https://…, mailto:… oder tel:…"><?= $err('url') ?></div>
<div class="field<?= isset($errors['parent_id']) ? ' has-error' : '' ?>"><label for="n-parent-<?= $uid ?>">Übergeordneter Eintrag (Dropdown)</label>
    <select id="n-parent-<?= $uid ?>" name="parent_id"><option value="0">– keiner (oberste Ebene) –</option>
        <?php foreach ($parents as $p): if ($item && (int) $p['id'] === (int) $item['id']) { continue; } ?><option value="<?= (int) $p['id'] ?>"<?= (int) $v('parent_id') === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['label']) ?></option><?php endforeach; ?>
    </select><?= $err('parent_id') ?></div>
<input type="hidden" name="auto_children" value=""><input type="hidden" name="new_tab" value=""><input type="hidden" name="is_visible" value="">
<label class="check"><input type="checkbox" name="auto_children" value="1"<?= $v('auto_children') ? ' checked' : '' ?>> Alle veröffentlichten Hauptleistungen automatisch als Untermenü anzeigen</label>
<label class="check"><input type="checkbox" name="new_tab" value="1"<?= $v('new_tab') ? ' checked' : '' ?>> In neuem Tab öffnen</label>
<label class="check"><input type="checkbox" name="is_visible" value="1"<?= $v('is_visible', 1) ? ' checked' : '' ?>> Sichtbar</label>
<p class="field__help">Einträge zu nicht veröffentlichten Seiten oder Leistungen werden automatisch ausgeblendet, damit keine toten Links entstehen.</p>
