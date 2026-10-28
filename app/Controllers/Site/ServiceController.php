<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\BlockRepository;
use App\Repositories\PageRepository;
use App\Repositories\ServiceRepository;
use App\Services\BlockRenderer;

final class ServiceController extends SiteController
{
    public function show(string $slug): Response
    {
        $s = ServiceRepository::findBySlug($slug);
        if (!$s || !ServiceRepository::isPublished($s)) {
            throw HttpException::notFound();
        }
        if ($s['parent_id'] !== null) {
            return Response::redirect(ServiceRepository::path($s), 301);
        }
        return self::renderService($s, $this->request);
    }

    public function child(string $parent, string $child): Response
    {
        $s = ServiceRepository::findBySlug($child);
        $p = ServiceRepository::findBySlug($parent);
        if (!$s || !$p || (int) $s['parent_id'] !== (int) $p['id'] || !ServiceRepository::isPublished($s)) {
            throw HttpException::notFound();
        }
        return self::renderService($s, $this->request);
    }

    public static function renderService(array $s, Request $request, bool $preview = false): Response
    {
        $parent = $s['parent_id'] ? ServiceRepository::find((int) $s['parent_id']) : null;
        $overview = PageRepository::system('services');
        $crumbs = [['Startseite', '/']];
        if ($overview && PageRepository::isLive($overview)) {
            $crumbs[] = [$overview['title'], '/' . $overview['slug']];
        }
        if ($parent) {
            $crumbs[] = [$parent['title'], ServiceRepository::path($parent)];
        }
        $crumbs[] = [$s['title'], ServiceRepository::path($s)];

        $blocks = BlockRepository::forOwner('service', (int) $s['id'], $preview);
        $html = self::layout('site/service', [
            'service' => $s,
            'parent' => $parent,
            'blocksHtml' => BlockRenderer::render($blocks, ['service' => $s, 'preview' => $preview, 'request' => $request]),
            'children' => $blocks ? [] : ServiceRepository::children((int) $s['id']),
            'related' => ServiceRepository::related((int) $s['id']),
            'preview' => $preview,
            'hideBreadcrumbBar' => true,
        ], [
            'title' => $s['meta_title'] !== '' ? $s['meta_title'] : $s['title'],
            'description' => $s['meta_description'] !== '' ? $s['meta_description'] : ($s['teaser'] ?: $s['intro']),
            'og_media_id' => $s['image_media_id'],
            'noindex' => $preview,
            'path' => ServiceRepository::path($s),
            'breadcrumbs' => $crumbs,
        ], $request);
        return Response::html($html);
    }
}
