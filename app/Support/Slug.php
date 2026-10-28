<?php
declare(strict_types=1);

namespace App\Support;

final class Slug
{
    /** Single-segment slugs that may never be used by CMS pages (routes / files of the application). */
    public const RESERVED = [
        'admin', 'install', 'assets', 'uploads', 'leistungen', 'referenzen', 'kontakt', 'impressum', 'datenschutz',
        'unternehmen', 'sitemap-xml', 'robots-txt', 'favicon-ico', 'index', 'index-php', 'api', 'login', 'logout',
        'storage', 'config', 'app', 'public', 'vendor', 'feed', 'suche', 'search', 'wp-admin', 'wp-login-php',
    ];

    public static function make(string $text, int $max = 80): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'à' => 'a', '&' => ' und ']);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim(substr($text, 0, $max), '-');
        return $text;
    }

    public static function valid(string $slug): bool
    {
        return strlen($slug) >= 1 && strlen($slug) <= 120 && (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    public static function reserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED, true);
    }
}
