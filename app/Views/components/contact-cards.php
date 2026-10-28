<?php
/** @var array $d */
use App\Services\Settings;

$s = fn (string $k) => Settings::get($k);
$hours = Settings::lines('opening_hours');
$stacked = !empty($stacked);
?>
<div class="contact-cards<?= $stacked ? ' contact-cards--stacked' : '' ?>">
    <div class="contact-card">
        <span class="contact-card__icon" aria-hidden="true"><?= icon('phone') ?></span>
        <h3 class="contact-card__title">Telefon &amp; E-Mail</h3>
        <p>
            <?php if ($s('phone') !== ''): ?><a href="<?= e(tel_href($s('phone'))) ?>"><?= e($s('phone')) ?></a><br><?php endif; ?>
            <?php if ($s('email') !== ''): ?><a href="mailto:<?= e($s('email')) ?>"><?= e($s('email')) ?></a><?php endif; ?>
        </p>
    </div>
    <?php if (!empty($d['show_office']) && $s('office_street') !== ''): ?>
        <div class="contact-card">
            <span class="contact-card__icon" aria-hidden="true"><?= icon('map-pin') ?></span>
            <h3 class="contact-card__title">Büro</h3>
            <p><?= e($s('office_street')) ?><br><?= e($s('office_zip') . ' ' . $s('office_city')) ?></p>
            <?php if ($s('maps_url') !== ''): ?><p><a class="link-arrow" href="<?= e($s('maps_url')) ?>" target="_blank" rel="noopener noreferrer">Route planen <?= icon('external-link') ?></a></p><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($d['show_registered'])): ?>
        <div class="contact-card">
            <span class="contact-card__icon" aria-hidden="true"><?= icon('building-2') ?></span>
            <h3 class="contact-card__title">Firmensitz</h3>
            <p><?= e($s('company_name')) ?><br><?= e($s('registered_street')) ?><br><?= e($s('registered_zip') . ' ' . $s('registered_city')) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($d['show_hours']) && $hours): ?>
        <div class="contact-card">
            <span class="contact-card__icon" aria-hidden="true"><?= icon('clock') ?></span>
            <h3 class="contact-card__title">Bürozeiten</h3>
            <p><?= implode('<br>', array_map('e', $hours)) ?></p>
        </div>
    <?php endif; ?>
</div>
