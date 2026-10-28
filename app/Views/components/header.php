<?php
/** @var array $mainNav @var string $currentPath */
use App\Services\Media;

$logo = Media::find((int) setting('logo_media_id'));
$phone = setting('phone');
$email = setting('email');
$ctaLabel = setting('header_cta_label');
$ctaUrl = setting('header_cta_url', '/kontakt');
$isActive = static function (array $item) use ($currentPath): bool {
    $p = $item['path'] ?? '';
    if ($p === '') {
        return false;
    }
    return $p === $currentPath || ($p !== '/' && str_starts_with($currentPath, rtrim($p, '/') . '/'));
};
?>
<header class="site-header" data-header>
    <div class="topbar">
        <div class="container topbar__inner">
            <p class="topbar__claim"><?= e(setting('site_tagline')) ?></p>
            <ul class="topbar__contact">
                <?php if ($phone !== ''): ?><li><a href="<?= e(tel_href($phone)) ?>"><?= icon('phone') ?><span><?= e($phone) ?></span></a></li><?php endif; ?>
                <?php if ($email !== ''): ?><li><a href="mailto:<?= e($email) ?>"><?= icon('mail') ?><span><?= e($email) ?></span></a></li><?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e(setting('company_name', 'Wert auf Fundament GmbH')) ?> – zur Startseite">
            <?php if ($logo): ?>
                <?= Media::img((int) $logo['id'], '260px', 'brand__logo', false, setting('company_name'), 'sm') ?>
            <?php else: ?>
                <img class="brand__logo" src="<?= e(asset('img/logo.png')) ?>" width="556" height="190" alt="<?= e(setting('company_name', 'Wert auf Fundament GmbH')) ?>">
            <?php endif; ?>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
            <span class="nav-toggle__label">Menü</span>
        </button>

        <nav class="site-nav" id="site-nav" aria-label="Hauptnavigation" data-nav>
            <ul class="nav">
                <?php foreach ($mainNav as $i => $item): ?>
                    <?php $active = $isActive($item) || array_filter($item['children'], $isActive); ?>
                    <li class="nav__item<?= $item['children'] ? ' has-sub' : '' ?><?= $active ? ' is-active' : '' ?>">
                        <a class="nav__link" href="<?= e($item['href']) ?>"<?= $isActive($item) ? ' aria-current="page"' : '' ?><?= $item['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['label']) ?></a>
                        <?php if ($item['children']): ?>
                            <button class="nav__sub-toggle" type="button" aria-expanded="false" aria-controls="subnav-<?= $i ?>">
                                <span class="sr-only">Untermenü „<?= e($item['label']) ?>“ öffnen</span><?= icon('chevron-down') ?>
                            </button>
                            <ul class="nav__sub" id="subnav-<?= $i ?>">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a href="<?= e($child['href']) ?>"<?= $isActive($child) ? ' aria-current="page"' : '' ?><?= $child['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($child['label']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="site-nav__extra">
                <?php if ($phone !== ''): ?>
                    <a class="site-nav__phone" href="<?= e(tel_href($phone)) ?>"><?= icon('phone') ?> <?= e($phone) ?></a>
                <?php endif; ?>
                <?php if ($ctaLabel !== ''): ?>
                    <a class="btn btn--primary site-nav__cta" href="<?= e(link_href($ctaUrl)) ?>"><?= e($ctaLabel) ?></a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
