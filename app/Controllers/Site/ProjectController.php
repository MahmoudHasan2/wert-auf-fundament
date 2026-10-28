<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;
use App\Repositories\PageRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ServiceRepository;

final class ProjectController extends SiteController
{
    public function show(string $slug): Response
    {
        $p = ProjectRepository::findPublishedBySlug($slug);
        if (!$p) {
            throw HttpException::notFound();
        }
        return self::renderProject($p, $this->request);
    }

    public static function renderProject(array $p, Request $request, bool $preview = false): Response
    {
        $overview = PageRepository::system('projects');
        $crumbs = [['Startseite', '/']];
        if ($overview && PageRepository::isLive($overview)) {
            $crumbs[] = [$overview['title'], '/' . $overview['slug']];
        }
        $crumbs[] = [$p['title'], ProjectRepository::path($p)];
        $service = $p['service_id'] ? ServiceRepository::find((int) $p['service_id']) : null;
        $html = self::layout('site/project', [
            'project' => $p,
            'service' => $service && ServiceRepository::isPublished($service) ? $service : null,
            'images' => $p['gallery_id'] ? ContentRepository::galleryItems((int) $p['gallery_id']) : [],
            'more' => array_values(array_filter(ProjectRepository::published(4), fn ($o) => (int) $o['id'] !== (int) $p['id'])),
            'overview' => $overview,
            'preview' => $preview,
            'hideBreadcrumbBar' => true,
        ], [
            'title' => $p['meta_title'] !== '' ? $p['meta_title'] : $p['title'],
            'description' => $p['meta_description'] !== '' ? $p['meta_description'] : (string) $p['summary'],
            'og_media_id' => $p['cover_media_id'],
            'noindex' => $preview,
            'path' => ProjectRepository::path($p),
            'breadcrumbs' => $crumbs,
            'type' => 'article',
        ], $request);
        return Response::html($html);
    }
}
