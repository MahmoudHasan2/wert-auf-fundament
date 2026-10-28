<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\BlockRepository;
use App\Repositories\NavigationRepository;
use App\Repositories\PageRepository;
use App\Repositories\ServiceRepository;
use App\Services\BlockRenderer;
use App\Services\Seo;

abstract class SiteController
{
    public function __construct(protected Request $request)
    {
    }

    /** Renders a template inside the public layout. */
    protected function view(string $template, array $data, array $meta, int $status = 200): Response
    {
        return Response::html(self::layout($template, $data, $meta, $this->request), $status);
    }

    public static function layout(string $template, array $data, array $meta, Request $request): string
    {
        $crumbs = $meta['breadcrumbs'] ?? [];
        if ($crumbs) {
            $meta['schema'][] = Seo::breadcrumbSchema($crumbs);
        }
        return View::render($template, $data + [
            'meta' => Seo::meta($meta),
            'mainNav' => NavigationRepository::tree('main'),
            'footerNav' => NavigationRepository::tree('footer'),
            'legalNav' => NavigationRepository::tree('legal'),
            'footerServices' => ServiceRepository::publishedTop(),
            'currentPath' => $request->path,
            'isPreview' => !empty($data['preview']),
        ], 'layouts/site');
    }

    /** Renders a CMS page (shared by the public controller and the admin preview). */
    public static function renderPage(array $page, Request $request, bool $preview = false, int $status = 200, array $extra = []): Response
    {
        $blocks = BlockRepository::forOwner('page', (int) $page['id'], $preview);
        $ctx = ['page' => $page, 'preview' => $preview, 'request' => $request] + $extra;
        $path = PageRepository::path($page);
        $crumbs = $page['system_key'] === 'home' || $page['template'] === 'landing' ? [] : [['Startseite', '/'], [$page['title'], $path]];
        $schema = $page['system_key'] === 'home' ? [Seo::organization()] : [];
        $html = self::layout('site/page', [
            'page' => $page,
            'blocksHtml' => BlockRenderer::render($blocks, $ctx),
            'preview' => $preview,
        ], [
            'title' => $page['meta_title'] !== '' ? $page['meta_title'] : $page['title'],
            'raw_title' => $page['meta_title'] !== '' && $page['system_key'] === 'home',
            'description' => $page['meta_description'],
            'og_media_id' => $page['og_media_id'],
            'noindex' => $preview || (int) $page['noindex'] === 1,
            'path' => $path,
            'breadcrumbs' => $crumbs,
            'schema' => $schema,
        ], $request);
        return Response::html($html, $status);
    }

    public static function renderError(Request $request, int $status): string
    {
        $titles = [
            404 => ['Seite nicht gefunden', 'Die angeforderte Seite existiert nicht oder wurde verschoben.'],
            403 => ['Kein Zugriff', 'Sie haben keine Berechtigung für diese Seite.'],
            405 => ['Nicht erlaubt', 'Diese Anfrage ist hier nicht möglich.'],
            429 => ['Zu viele Anfragen', 'Bitte versuchen Sie es in einigen Minuten erneut.'],
            500 => ['Ein Fehler ist aufgetreten', 'Bitte versuchen Sie es später erneut. Wir wurden informiert.'],
            503 => ['Wartungsarbeiten', 'Die Website ist vorübergehend nicht erreichbar.'],
        ];
        [$title, $text] = $titles[$status] ?? $titles[500];
        return self::layout('site/error', ['status' => $status, 'title' => $title, 'text' => $text], ['title' => $title, 'noindex' => true], $request);
    }
}
