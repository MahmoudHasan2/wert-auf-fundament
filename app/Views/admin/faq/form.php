<?php
/** @var ?array $faq @var array $categories @var array $old @var array $errors */
$v = fn (string $k, $d = '') => $old[$k] ?? ($faq[$k] ?? $d);
?>
<p class="breadcrumb-admin"><a href="<?= e(url('/admin/faq')) ?>"><?= icon('arrow-left') ?> Alle Fragen</a></p>
<form method="post" action="<?= e($faq ? url('/admin/faq/' . $faq['id']) : url('/admin/faq')) ?>" class="card form-stack form-narrow">
    <?= csrf_field() ?>
    <div class="field<?= isset($errors['question']) ? ' has-error' : '' ?>"><label for="fq">Frage <span class="req">*</span></label><input id="fq" type="text" name="question" maxlength="300" required value="<?= e($v('question')) ?>"><?php if (isset($errors['question'])): ?><p class="field__error"><?= e($errors['question']) ?></p><?php endif; ?></div>
    <div class="field<?= isset($errors['answer']) ? ' has-error' : '' ?>"><label for="fa">Antwort <span class="req">*</span></label><textarea id="fa" name="answer" rows="8" data-richtext><?= e($v('answer')) ?></textarea><?php if (isset($errors['answer'])): ?><p class="field__error"><?= e($errors['answer']) ?></p><?php endif; ?></div>
    <div class="grid-2">
        <div class="field"><label for="fcat">Kategorie</label><select id="fcat" name="category_id"><option value="0">– keine –</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $v('category_id') === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="fst">Status</label><select id="fst" name="status"><option value="draft"<?= $v('status', 'draft') === 'draft' ? ' selected' : '' ?>>Entwurf</option><option value="published"<?= $v('status') === 'published' ? ' selected' : '' ?>>Veröffentlicht</option></select></div>
    </div>
    <div class="form-actions"><button class="btn btn--primary" type="submit"><?= icon('save') ?> Speichern</button></div>
</form>
