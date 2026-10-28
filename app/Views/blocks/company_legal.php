<?php
/** @var array $d @var array $block */
use App\Services\Settings;

$s = fn (string $k) => Settings::get($k);
$rows = array_filter([
    'Vertreten durch die Geschäftsführung' => $s('managing_directors'),
    'Registergericht' => $s('register_court'),
    'Registernummer' => $s('register_number'),
    'Umsatzsteuer-Identifikationsnummer gemäß § 27a UStG' => $s('vat_id'),
]);
?>
<section class="legal-section legal-company" id="block-<?= (int) $block['id'] ?>">
    <?php if ($d['heading'] !== ''): ?><h2 class="legal-section__title"><?= e($d['heading']) ?></h2><?php endif; ?>
    <address class="legal-company__address">
        <strong><?= e($s('company_name')) ?></strong><br>
        <?= e($s('registered_street')) ?><br>
        <?= e($s('registered_zip') . ' ' . $s('registered_city')) ?><br>
        Deutschland
    </address>
    <?php if ($s('office_street') !== ''): ?>
        <p><strong>Büro / Betriebsstätte:</strong><br><?= e($s('office_street')) ?>, <?= e($s('office_zip') . ' ' . $s('office_city')) ?></p>
    <?php endif; ?>
    <h3 class="legal-section__sub">Kontakt</h3>
    <p>
        <?php if ($s('phone') !== ''): ?>Telefon: <a href="<?= e(tel_href($s('phone'))) ?>"><?= e($s('phone')) ?></a><br><?php endif; ?>
        <?php if ($s('fax') !== ''): ?>Telefax: <?= e($s('fax')) ?><br><?php endif; ?>
        <?php if ($s('email') !== ''): ?>E-Mail: <a href="mailto:<?= e($s('email')) ?>"><?= e($s('email')) ?></a><?php endif; ?>
    </p>
    <?php if ($rows): ?>
        <dl class="legal-company__list">
            <?php foreach ($rows as $label => $value): ?>
                <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>
    <?php if ($s('responsible_person') !== ''): ?>
        <h3 class="legal-section__sub">Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h3>
        <p><?= nl2br(e($s('responsible_person'))) ?></p>
    <?php endif; ?>
    <?php if ($s('professional_info') !== ''): ?>
        <h3 class="legal-section__sub">Weitere Angaben</h3>
        <p><?= nl2br(e($s('professional_info'))) ?></p>
    <?php endif; ?>
</section>
