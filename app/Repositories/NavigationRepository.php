<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;

final class NavigationRepository
{
    public const MENUS = [
        'main'   => 'Hauptmenü',
        'footer' => 'Fußbereich – Unternehmen',
        'legal'  => 'Fußbereich – Rechtliches',
    ];
    public const TYPES = [
        'page'    => 'Seite',
        'service' => 'Leistung',
        'url'     => 'Eigener Link / externe Adresse',
    ];

    public static function rows(string $menu): array
    {
        return DB::all('SELECT n.*, p.title AS page_title, p.slug AS page_slug, s.title AS service_title
            FROM nav_items n LEFT JOIN pages p ON p.id = n.page_id LEFT JOIN services s ON s.id = n.service_id
            WHERE n.menu = ? ORDER BY n.sort_order, n.id', [$menu]);
    }

    /**
     * Resolved, visible menu tree for the public site. Items pointing to unpublished pages or
     * services are skipped automatically, so the navigation never links to a 404.
     * @return array<int, array{label:string, href:string, external:bool, new_tab:bool, path:string, children:array}>
     */
    public static function tree(string $menu): array
    {
        $rows = array_filter(self::rows($menu), fn ($r) => (int) $r['is_visible'] === 1);
        $byParent = [];
        foreach ($rows as $r) {
            $byParent[(int) ($r['parent_id'] ?? 0)][] = $r;
        }
        $build = function (int $parent) use (&$build, $byParent): array {
            $out = [];
            foreach ($byParent[$parent] ?? [] as $r) {
                $path = self::resolve($r);
                if ($path === null) {
                    continue;
                }
                $children = $build((int) $r['id']);
                if ((int) $r['auto_children'] === 1) {
                    foreach (ServiceRepository::publishedTop() as $s) {
                        $children[] = ['label' => $s['title'], 'href' => url(ServiceRepository::path($s)), 'path' => ServiceRepository::path($s), 'external' => false, 'new_tab' => false, 'children' => []];
                    }
                }
                $external = (bool) preg_match('#^https?://#i', $path);
                $out[] = [
                    'label' => $r['label'],
                    'href' => link_href($path),
                    'path' => $external ? '' : $path,
                    'external' => $external,
                    'new_tab' => (int) $r['new_tab'] === 1,
                    'children' => $children,
                ];
            }
            return $out;
        };
        return $build(0);
    }

    /** Internal path or URL for an item, or null if the target is not public. */
    public static function resolve(array $r): ?string
    {
        switch ($r['link_type']) {
            case 'page':
                $p = $r['page_id'] ? PageRepository::find((int) $r['page_id']) : null;
                return $p && PageRepository::isLive($p) ? PageRepository::path($p) : null;
            case 'service':
                $s = $r['service_id'] ? ServiceRepository::find((int) $r['service_id']) : null;
                return $s && ServiceRepository::isPublished($s) ? ServiceRepository::path($s) : null;
            default:
                return $r['url'] !== '' ? $r['url'] : null;
        }
    }
}
