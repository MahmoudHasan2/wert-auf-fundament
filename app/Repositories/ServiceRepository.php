<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;

final class ServiceRepository
{
    private static ?array $all = null;

    /** All services keyed by id (small table – cached per request). */
    public static function all(): array
    {
        if (self::$all === null) {
            self::$all = [];
            foreach (DB::all('SELECT * FROM services ORDER BY sort_order, title') as $s) {
                self::$all[(int) $s['id']] = $s;
            }
        }
        return self::$all;
    }

    public static function flush(): void
    {
        self::$all = null;
    }

    public static function find(int $id): ?array
    {
        return self::all()[$id] ?? null;
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::all() as $s) {
            if ($s['slug'] === $slug) {
                return $s;
            }
        }
        return null;
    }

    public static function isPublished(?array $s): bool
    {
        if (!$s || $s['status'] !== 'published') {
            return false;
        }
        // A sub-service is only reachable if its parent is published, too.
        return $s['parent_id'] === null || self::isPublished(self::find((int) $s['parent_id']));
    }

    public static function publishedTop(): array
    {
        return array_values(array_filter(self::all(), fn ($s) => $s['parent_id'] === null && $s['status'] === 'published'));
    }

    public static function children(int $parentId, bool $publishedOnly = true): array
    {
        return array_values(array_filter(self::all(), fn ($s) => (int) $s['parent_id'] === $parentId && (!$publishedOnly || $s['status'] === 'published')));
    }

    public static function featured(): array
    {
        return array_values(array_filter(self::all(), fn ($s) => (int) $s['is_featured'] === 1 && self::isPublished($s)));
    }

    public static function byIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $s = self::find((int) $id);
            if ($s && self::isPublished($s)) {
                $out[] = $s;
            }
        }
        return $out;
    }

    public static function path(array $s): string
    {
        if ($s['parent_id'] !== null && ($p = self::find((int) $s['parent_id']))) {
            return '/leistungen/' . $p['slug'] . '/' . $s['slug'];
        }
        return '/leistungen/' . $s['slug'];
    }

    public static function relatedIds(int $id): array
    {
        return array_map('intval', DB::column('SELECT related_id FROM service_relations WHERE service_id = ?', [$id]));
    }

    public static function related(int $id): array
    {
        return self::byIds(self::relatedIds($id));
    }

    /** Ordered tree for the admin: [ [service, children[]], ... ] */
    public static function tree(): array
    {
        $tree = [];
        foreach (self::all() as $s) {
            if ($s['parent_id'] === null) {
                $tree[] = ['service' => $s, 'children' => self::children((int) $s['id'], false)];
            }
        }
        return $tree;
    }

    /** Reasons why a service cannot be deleted. */
    public static function deleteBlockers(int $id): array
    {
        $reasons = [];
        if (self::children($id, false)) {
            $reasons[] = 'Die Leistung besitzt Unterleistungen.';
        }
        foreach (DB::all('SELECT label, menu FROM nav_items WHERE service_id = ?', [$id]) as $n) {
            $reasons[] = 'Sie ist im Menü verlinkt („' . $n['label'] . '“).';
        }
        $cnt = (int) DB::value('SELECT COUNT(*) FROM projects WHERE service_id = ?', [$id]);
        if ($cnt) {
            $reasons[] = $cnt . ' Referenz(en) sind dieser Leistung zugeordnet (Zuordnung würde entfernt).';
        }
        return $reasons;
    }
}
