<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Response;
use App\Repositories\PageRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ServiceRepository;
use App\Services\Settings;

final class SeoController extends SiteController
{
    public function sitemap(): Response
    {
        $urls = [];
        foreach (PageRepository::live() as $p) {
            if ((int) $p['noindex'] === 0) {
                $urls[] = [PageRepository::path($p), $p['updated_at']];
            }
        }
        foreach (ServiceRepository::all() as $s) {
            if (ServiceRepository::isPublished($s)) {
                $urls[] = [ServiceRepository::path($s), $s['updated_at']];
            }
        }
        foreach (ProjectRepository::published() as $p) {
            $urls[] = [ProjectRepository::path($p), $p['updated_at']];
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        if (Settings::get('seo_indexing') === '1') {
            foreach ($urls as [$path, $mod]) {
                $xml .= '  <url><loc>' . e(abs_url($path)) . '</loc><lastmod>' . e(date('Y-m-d', strtotime((string) $mod) ?: time())) . "</lastmod></url>\n";
            }
        }
        $xml .= '</urlset>' . "\n";
        return Response::text($xml, 'application/xml');
    }

    public function robots(): Response
    {
        if (Settings::get('seo_indexing') !== '1') {
            return Response::text("# Indexierung ist in den Einstellungen deaktiviert\nUser-agent: *\nDisallow: /\n");
        }
        return Response::text("User-agent: *\nDisallow: /admin\nDisallow: /kontakt/anfrage\n\nSitemap: " . abs_url('/sitemap.xml') . "\n");
    }
}
