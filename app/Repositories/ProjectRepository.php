<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;

final class ProjectRepository
{
    public static function find(int $id): ?array
    {
        return DB::row('SELECT * FROM projects WHERE id = ?', [$id]);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return DB::row("SELECT * FROM projects WHERE slug = ? AND status = 'published'", [$slug]);
    }

    public static function published(int $limit = 0, bool $featuredOnly = false, int $serviceId = 0): array
    {
        $sql = "SELECT p.*, s.title AS service_title, s.slug AS service_slug FROM projects p LEFT JOIN services s ON s.id = p.service_id WHERE p.status = 'published'";
        $params = [];
        if ($featuredOnly) {
            $sql .= ' AND p.is_featured = 1';
        }
        if ($serviceId > 0) {
            $sql .= ' AND p.service_id = ?';
            $params[] = $serviceId;
        }
        $sql .= ' ORDER BY p.sort_order, p.created_at DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return DB::all($sql, $params);
    }

    public static function path(array $p): string
    {
        return '/referenzen/' . $p['slug'];
    }

    public static function slugTaken(string $slug, int $exceptId = 0): bool
    {
        return (bool) DB::value('SELECT id FROM projects WHERE slug = ? AND id <> ?', [$slug, $exceptId]);
    }
}
