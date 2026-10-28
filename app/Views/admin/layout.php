<?php
/** @var string $content @var ?array $user @var array $flash @var string $title @var \App\Core\Request $request @var int $newEnquiries */
use App\Repositories\PageRepository;
use App\Security\Auth;

$home = $user ? PageRepository::system('home') : null;
$imprint = $user ? PageRepository::system('imprint') : null;
$privacy = $user ? PageRepository::system('privacy') : null;
$path = $request->path;
$groups = [
    '' => [
        ['Übersicht', '/admin', 'layout-dashboard', 'auth', true],
    ],
    'Inhalte' => [
        ['Startseite', $home ? '/admin/pages/' . $home['id'] : '/admin/pages', 'house', 'content', true],
        ['Seiten', '/admin/pages', 'file-text', 'content'],
        ['Dienstleistungen', '/admin/services', 'hard-hat', 'content'],
        ['Referenzen', '/admin/projects', 'building-2', 'content'],
        ['Bausteine', '/admin/library', 'copy', 'content'],
        ['FAQ', '/admin/faq', 'circle-help', 'content'],
        ['Navigation', '/admin/navigation', 'navigation', 'content'],
    ],
    'Medien' => [
        ['Medien', '/admin/media', 'image', 'media'],
        ['Galerien', '/admin/galleries', 'images', 'content'],
    ],
    'Kommunikation' => [
        ['Kontaktanfragen', '/admin/enquiries', 'inbox', 'enquiries'],
    ],
    'Rechtliches' => [
        ['Impressum', $imprint ? '/admin/pages/' . $imprint['id'] : '/admin/pages', 'scale', 'legal', true],
        ['Datenschutz', $privacy ? '/admin/pages/' . $privacy['id'] : '/admin/pages', 'shield-check', 'legal', true],
    ],
    'Verwaltung' => [
        ['Unternehmen', '/admin/settings', 'settings', 'settings'],
        ['Benutzer', '/admin/users', 'users', 'users'],
        ['Protokoll', '/admin/audit', 'history', 'audit'],
        ['Backups', '/admin/backups', 'database', 'backup'],
        ['Systemstatus', '/admin/system', 'activity', 'system'],
    ],
];
$exactHit = false;
foreach ($groups as $items) {
    foreach ($items as $item) {
        $exactHit = $exactHit || (!empty($item[4]) && $item[1] === $path);
    }
}
$isActive = function (string $href, bool $exact) use ($path, $exactHit): bool {
    if ($exact) {
        return $path === $href;
    }
    return !$exactHit && ($path === $href || str_starts_with($path, $href . '/'));
};
?>
<!doctype html>
<html lang="de" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title !== '' ? $title . ' – ' : '') ?>Verwaltung · <?= e(setting('company_name', 'Wert auf Fundament')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <link rel="icon" href="<?= e(url('/assets/img/favicon-32.png')) ?>" type="image/png">
    <script src="<?= e(asset('js/boot.js')) ?>"></script>
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="admin<?= $user ? '' : ' admin--guest' ?>" data-base="<?= e(base_path()) ?>">
<a class="skip-link" href="#admin-main">Zum Inhalt springen</a>
<?php if ($user): ?>
<div class="admin-shell">
    <aside class="sidebar" id="sidebar" aria-label="Verwaltungsnavigation">
        <div class="sidebar__head">
            <a href="<?= e(url('/admin')) ?>" class="sidebar__brand"><img src="<?= e(asset('img/logo-light.png')) ?>" width="556" height="190" alt="Verwaltung – Übersicht"></a>
            <button class="sidebar__toggle" type="button" aria-expanded="false" aria-controls="sidebar-nav" data-sidebar-toggle><?= icon('menu') ?><span>Menü</span></button>
        </div>
        <nav class="sidebar__nav" id="sidebar-nav">
            <?php foreach ($groups as $group => $items): ?>
                <?php $items = array_filter($items, fn ($i) => $i[3] === 'auth' || Auth::can($i[3])); if (!$items) { continue; } ?>
                <?php if ($group !== ''): ?><p class="sidebar__group"><?= e($group) ?></p><?php endif; ?>
                <ul>
                    <?php foreach ($items as $item): $active = $isActive($item[1], !empty($item[4])); ?>
                        <li>
                            <a href="<?= e(url($item[1])) ?>" class="<?= $active ? 'is-active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                                <?= icon($item[2]) ?><span><?= e($item[0]) ?></span>
                                <?php if ($item[1] === '/admin/enquiries' && $newEnquiries > 0): ?><span class="badge badge--red" title="Neue Anfragen"><?= $newEnquiries ?></span><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="topbar">
            <h1 class="topbar__title"><?= e($title) ?></h1>
            <div class="topbar__actions">
                <a class="btn btn--ghost btn--sm" href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?> Website ansehen</a>
                <details class="usermenu">
                    <summary><span class="usermenu__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span><span class="usermenu__name"><?= e($user['name']) ?></span></summary>
                    <div class="usermenu__panel">
                        <p class="usermenu__role"><?= e(Auth::ROLE_LABELS[$user['role']] ?? $user['role']) ?></p>
                        <a href="<?= e(url('/admin/konto')) ?>"><?= icon('key-round') ?> Passwort ändern</a>
                        <form method="post" action="<?= e(url('/admin/logout')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icon('log-out') ?> Abmelden</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>
        <main class="admin-content" id="admin-main" tabindex="-1">
            <?= \App\Core\View::partial('admin/partials/flash', ['flash' => $flash]) ?>
            <?= $content ?>
        </main>
    </div>
</div>
<?= \App\Core\View::partial('admin/partials/media-modal') ?>
<?php else: ?>
<main class="auth" id="admin-main">
    <div class="auth__card">
        <img class="auth__logo" src="<?= e(asset('img/logo.png')) ?>" width="556" height="190" alt="<?= e(setting('company_name', 'Wert auf Fundament GmbH')) ?>">
        <?= \App\Core\View::partial('admin/partials/flash', ['flash' => $flash]) ?>
        <?= $content ?>
    </div>
</main>
<?php endif; ?>
</body>
</html>
