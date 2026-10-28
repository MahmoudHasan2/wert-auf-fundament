<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;

final class PageRepository
{
    public const TEMPLATES = [
        'standard' => 'Standard (mit Brotkrumennavigation)',
        'landing'  => 'Landingpage (volle Breite, ohne Brotkrumen)',
        'legal'    => 'Rechtstext (schmale Lesespalte, Stand-Datum)',
    ];

    /** System pages: fixed slug, cannot be deleted. Legal pages cannot be unpublished. */
    public const SYSTEM = [
        'home' => 'Startseite', 'services' => 'Leistungsübersicht', 'projects' => 'Referenzen', 'about' => 'Unternehmen',
        'contact' => 'Kontakt', 'imprint' => 'Impressum', 'privacy' => 'Datenschutzerklärung',
    ];
    public const LEGAL_KEYS = ['imprint', 'privacy'];

    public static function find(int $id): ?array
    {
        return DB::row('SELECT * FROM pages WHERE id = ?', [$id]);
    }

    public static function system(string $key): ?array
    {
        return DB::row('SELECT * FROM pages WHERE system_key = ?', [$key]);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $p = DB::row('SELECT * FROM pages WHERE slug = ?', [$slug]);
        return $p && self::isLive($p) ? $p : null;
    }

    public static function isLive(array $p): bool
    {
        return $p['status'] === 'published' && ($p['published_at'] === null || strtotime((string) $p['published_at']) <= time());
    }

    public static function displayStatus(array $p): string
    {
        if ($p['status'] === 'published' && $p['published_at'] !== null && strtotime((string) $p['published_at']) > time()) {
            return 'scheduled';
        }
        return (string) $p['status'];
    }

    public static function path(array $p): string
    {
        return $p['system_key'] === 'home' ? '/' : '/' . $p['slug'];
    }

    public static function slugTaken(string $slug, int $exceptId = 0): bool
    {
        return (bool) DB::value('SELECT id FROM pages WHERE slug = ? AND id <> ?', [$slug, $exceptId]);
    }

    public static function isLegal(array $p): bool
    {
        return in_array($p['system_key'], self::LEGAL_KEYS, true);
    }

    /** All live pages (for sitemap / link pickers). */
    public static function live(): array
    {
        return DB::all("SELECT * FROM pages WHERE status = 'published' AND (published_at IS NULL OR published_at <= ?) ORDER BY title", [now()]);
    }

    public static function navReferences(int $id): array
    {
        return DB::all('SELECT id, label, menu FROM nav_items WHERE page_id = ?', [$id]);
    }
}
