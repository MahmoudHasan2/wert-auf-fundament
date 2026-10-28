<?php
/** @var array $meta @var string $content */
use App\Services\Media;

$logoLight = Media::find((int) setting('logo_light_media_id'));
$favicon = Media::find((int) setting('favicon_media_id'));
?>
<!doctype html>
<html lang="de" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($meta['title']) ?></title>
    <?php if ($meta['description'] !== ''): ?><meta name="description" content="<?= e($meta['description']) ?>"><?php endif; ?>
    <meta name="robots" content="<?= e($meta['robots']) ?>">
    <?php if ($meta['canonical'] !== ''): ?><link rel="canonical" href="<?= e($meta['canonical']) ?>"><?php endif; ?>
    <meta property="og:type" content="<?= e($meta['type']) ?>">
    <meta property="og:locale" content="de_DE">
    <meta property="og:site_name" content="<?= e(setting('company_name', 'Wert auf Fundament GmbH')) ?>">
    <meta property="og:title" content="<?= e($meta['og_title']) ?>">
    <?php if ($meta['description'] !== ''): ?><meta property="og:description" content="<?= e($meta['description']) ?>"><?php endif; ?>
    <?php if ($meta['canonical'] !== ''): ?><meta property="og:url" content="<?= e($meta['canonical']) ?>"><?php endif; ?>
    <meta property="og:image" content="<?= e($meta['og_image']) ?>">
    <meta name="theme-color" content="#1d1f21">
    <link rel="preload" href="<?= e(url('/assets/fonts/archivo.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(url('/assets/fonts/inter.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= e(Media::url($favicon, 'sm')) ?>">
        <link rel="apple-touch-icon" href="<?= e(Media::url($favicon, 'sm')) ?>">
    <?php else: ?>
        <link rel="icon" href="<?= e(url('/favicon.ico')) ?>" sizes="any">
        <link rel="icon" href="<?= e(url('/assets/img/favicon-32.png')) ?>" type="image/png">
        <link rel="apple-touch-icon" href="<?= e(url('/assets/img/apple-touch-icon.png')) ?>">
    <?php endif; ?>
    <script src="<?= e(asset('js/boot.js')) ?>"></script>
    <script src="<?= e(asset('js/site.js')) ?>" defer></script>
    <?php foreach ($meta['schema'] as $schema): ?>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endforeach; ?>
</head>
<body>
<a class="skip-link" href="#main">Zum Inhalt springen</a>
<?php if (!empty($isPreview)): ?>
    <div class="preview-bar" role="status">Vorschau – diese Ansicht ist nur für angemeldete Administratoren sichtbar und wird nicht indexiert.</div>
<?php endif; ?>
<?= \App\Core\View::partial('components/header', get_defined_vars()) ?>
<main id="main" tabindex="-1">
    <?php if (!empty($meta['breadcrumbs']) && empty($hideBreadcrumbBar)): ?>
        <?= \App\Core\View::partial('components/breadcrumbs', ['crumbs' => $meta['breadcrumbs'], 'variant' => 'bar']) ?>
    <?php endif; ?>
    <?= $content ?>
</main>
<?= \App\Core\View::partial('components/footer', get_defined_vars() + ['logoLight' => $logoLight]) ?>
</body>
</html>
