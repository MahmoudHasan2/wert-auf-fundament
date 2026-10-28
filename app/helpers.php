<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Services\Settings;

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/** HTML-escape for element content and quoted attribute values. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Base path of the application (e.g. "" or "/subdir"), derived from app.url. */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        if (Config::envFile() === null && PHP_SAPI !== 'cli') {
            // Not configured yet (installer): derive the base path from the front controller location.
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            $dir = preg_replace('#/index\.php$#', '', $script) ?? '';
            $base = rtrim((string) preg_replace('#/public$#', '', $dir), '/');
        } else {
            $path = (string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?? '');
            $base = rtrim($path, '/');
        }
    }
    return $base;
}

function url(string $path = '/'): string
{
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . $path;
    }
    return base_path() . $path;
}

function abs_url(string $path = '/'): string
{
    $root = rtrim((string) config('app.url'), '/');
    $root = preg_replace('#^(https?://[^/]+).*$#', '$1', $root) ?? $root;
    return $root . url($path);
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr((string) filemtime($file), -6) : APP_VERSION;
    return url('/assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function setting(string $key, string $default = ''): string
{
    return Settings::get($key, $default);
}

function icon(string $name, string $class = 'icon'): string
{
    $name = preg_replace('/[^a-z0-9-]/', '', $name) ?: 'square';
    return '<svg class="' . e($class) . '" aria-hidden="true" focusable="false"><use href="' . e(url('/assets/icons.svg') . '#i-' . $name) . '"></use></svg>';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

/**
 * Resolve a CMS link value to an href. Allowed: internal paths ("/kontakt"), anchors,
 * http(s) URLs, mailto: and tel:. Anything else becomes "#".
 */
function link_href(?string $link): string
{
    $link = trim((string) $link);
    if ($link === '') {
        return '#';
    }
    if ($link[0] === '/' && !str_starts_with($link, '//')) {
        return url($link);
    }
    if ($link[0] === '#' || preg_match('#^(https?://|mailto:|tel:)#i', $link)) {
        return $link;
    }
    return '#';
}

function is_external(?string $link): bool
{
    return (bool) preg_match('#^https?://#i', (string) $link) && !str_starts_with((string) $link, rtrim((string) config('app.url'), '/'));
}

function tel_href(string $phone): string
{
    $digits = preg_replace('/[^0-9+]/', '', $phone) ?? '';
    if (str_starts_with($digits, '0') && !str_starts_with($digits, '00')) {
        $digits = '+49' . substr($digits, 1);
    }
    return 'tel:' . $digits;
}

function str_excerpt(?string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    return rtrim(mb_substr($cut, 0, $space ?: $length), ' ,.;:-') . ' …';
}

function format_date(?string $datetime, bool $withTime = false): string
{
    if (!$datetime) {
        return '–';
    }
    $ts = strtotime($datetime);
    return $ts ? date($withTime ? 'd.m.Y, H:i' : 'd.m.Y', $ts) : '–';
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function is_debug(): bool
{
    return (bool) config('app.debug', false);
}

/** Anonymised client IP (last octet / last 80 bits removed) for logging. */
function anon_ip(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip) ?? '';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $bin = inet_pton($ip);
        return $bin === false ? '' : (string) inet_ntop(substr($bin, 0, 6) . str_repeat("\0", 10));
    }
    return '';
}

function storage_path(string $path = ''): string
{
    $root = rtrim((string) (config('paths.storage') ?: BASE_PATH . '/storage'), '/\\');
    return $root . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function public_path(string $path = ''): string
{
    return BASE_PATH . '/public' . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function status_label(string $status): string
{
    return [
        'draft' => 'Entwurf', 'published' => 'Veröffentlicht', 'archived' => 'Archiviert', 'scheduled' => 'Geplant',
        'new' => 'Neu', 'in_progress' => 'In Bearbeitung', 'answered' => 'Beantwortet', 'closed' => 'Abgeschlossen',
    ][$status] ?? $status;
}
