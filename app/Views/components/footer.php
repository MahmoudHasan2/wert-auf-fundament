<?php
/** @var array $footerNav @var array $legalNav @var array $footerServices @var ?array $logoLight */
use App\Repositories\ServiceRepository;
use App\Services\Media;
use App\Services\Settings;

$hours = Settings::lines('opening_hours');
$socials = array_filter(['Facebook' => setting('social_facebook'), 'Instagram' => setting('social_instagram'), 'LinkedIn' => setting('social_linkedin')]);
?>
<footer class="site-footer">
    <div class="site-footer__stripes" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="container site-footer__grid">
        <div class="site-footer__brand">
            <a href="<?= e(url('/')) ?>" class="site-footer__logo" aria-label="Zur Startseite">
                <?php if ($logoLight): ?>
                    <?= Media::img((int) $logoLight['id'], '240px', '', true, setting('company_name'), 'sm') ?>
                <?php else: ?>
                    <img src="<?= e(asset('img/logo-light.png')) ?>" width="556" height="190" alt="<?= e(setting('company_name')) ?>" loading="lazy">
                <?php endif; ?>
            </a>
            <?php if (setting('footer_text') !== ''): ?>
                <p class="site-footer__text"><?= nl2br(e(setting('footer_text'))) ?></p>
            <?php endif; ?>
            <?php if ($socials): ?>
                <ul class="site-footer__social">
                    <?php foreach ($socials as $label => $href): ?>
                        <li><a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?> <?= icon('external-link', 'icon icon--xs') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if ($footerServices): ?>
        <nav class="site-footer__col" aria-label="Leistungen">
            <h2 class="site-footer__heading">Leistungen</h2>
            <ul>
                <?php foreach ($footerServices as $s): ?>
                    <li><a href="<?= e(url(ServiceRepository::path($s))) ?>"><?= e($s['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php if ($footerNav): ?>
        <nav class="site-footer__col" aria-label="Unternehmen">
            <h2 class="site-footer__heading">Unternehmen</h2>
            <ul>
                <?php foreach ($footerNav as $item): ?>
                    <li><a href="<?= e($item['href']) ?>"<?= $item['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="site-footer__col site-footer__contact">
            <h2 class="site-footer__heading">Kontakt</h2>
            <address>
                <strong><?= e(setting('company_name')) ?></strong><br>
                <?php if (setting('office_street') !== ''): ?>
                    <span class="site-footer__label">Büro</span><br>
                    <?= e(setting('office_street')) ?><br><?= e(setting('office_zip') . ' ' . setting('office_city')) ?><br>
                <?php endif; ?>
                <span class="site-footer__label">Firmensitz</span><br>
                <?= e(setting('registered_street')) ?><br><?= e(setting('registered_zip') . ' ' . setting('registered_city')) ?>
            </address>
            <ul class="site-footer__direct">
                <?php if (setting('phone') !== ''): ?><li><a href="<?= e(tel_href(setting('phone'))) ?>"><?= icon('phone') ?> <?= e(setting('phone')) ?></a></li><?php endif; ?>
                <?php if (setting('email') !== ''): ?><li><a href="mailto:<?= e(setting('email')) ?>"><?= icon('mail') ?> <?= e(setting('email')) ?></a></li><?php endif; ?>
            </ul>
            <?php if ($hours): ?>
                <p class="site-footer__hours"><?= icon('clock') ?> <span><?= implode('<br>', array_map('e', $hours)) ?></span></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="site-footer__bottom">
        <div class="container site-footer__bottom-inner">
            <p>&copy; <?= date('Y') ?> <?= e(setting('company_name')) ?></p>
            <?php if ($legalNav): ?>
                <nav aria-label="Rechtliches">
                    <ul>
                        <?php foreach ($legalNav as $item): ?>
                            <li><a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</footer>
