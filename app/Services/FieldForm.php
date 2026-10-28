<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Security\HtmlSanitizer;

/**
 * Parses and validates schema-defined form input (used by content blocks).
 * Unknown keys are ignored; every value is normalised to a safe type.
 */
final class FieldForm
{
    /**
     * @return array{0: array, 1: array<string,string>} [data, errors]
     */
    public static function parse(array $fields, array $input): array
    {
        $data = [];
        $errors = [];
        foreach ($fields as $f) {
            $name = $f['name'];
            $raw = $input[$name] ?? null;
            [$value, $error] = self::value($f, $raw);
            if ($error === null && !empty($f['required']) && ($value === '' || $value === 0 || $value === [])) {
                $error = 'Dieses Feld ist erforderlich.';
            }
            if ($error !== null) {
                $errors[$name] = $error;
            }
            $data[$name] = $value;
        }
        return [$data, $errors];
    }

    /** @return array{0:mixed, 1:?string} */
    private static function value(array $f, mixed $raw): array
    {
        $str = is_string($raw) ? trim(str_replace("\0", '', $raw)) : '';
        switch ($f['type']) {
            case 'text':
                return [mb_substr(preg_replace('/\s+/u', ' ', $str) ?? '', 0, 255), null];
            case 'textarea':
                return [mb_substr(str_replace("\r\n", "\n", $str), 0, 3000), null];
            case 'richtext':
                return [HtmlSanitizer::clean($str), null];
            case 'link':
                if ($str === '') {
                    return ['', null];
                }
                return self::validLink($str) ? [mb_substr($str, 0, 500), null] : ['', 'Ungültiges Linkziel. Erlaubt: interne Pfade wie „/kontakt“, https://…, mailto: oder tel:.'];
            case 'media':
                $id = (int) $raw;
                if ($id === 0) {
                    return [0, null];
                }
                return DB::value('SELECT id FROM media WHERE id = ?', [$id]) ? [$id, null] : [0, 'Das gewählte Bild existiert nicht mehr.'];
            case 'select':
                $opts = $f['options'] ?? [];
                $key = (string) $raw;
                return array_key_exists($key, $opts) ? [$key, null] : [(string) ($f['default'] ?? array_key_first($opts)), null];
            case 'bool':
                return [!empty($raw) ? '1' : '', null];
            case 'number':
                $n = is_numeric($raw) ? (int) $raw : (int) ($f['default'] ?? 0);
                return [(string) max($f['min'] ?? 0, min($f['max'] ?? 100000, $n)), null];
            case 'services':
                $ids = array_values(array_unique(array_filter(array_map('intval', is_array($raw) ? $raw : []))));
                if ($ids) {
                    $ids = array_map('intval', DB::column('SELECT id FROM services WHERE id IN (' . DB::placeholders($ids) . ')', $ids));
                }
                return [$ids, null];
            case 'gallery':
                $id = (int) $raw;
                return [$id && DB::value('SELECT id FROM galleries WHERE id = ?', [$id]) ? $id : 0, null];
            case 'faq_category':
                $id = (int) $raw;
                return [$id && DB::value('SELECT id FROM faq_categories WHERE id = ?', [$id]) ? $id : 0, null];
            case 'library':
                $id = (int) $raw;
                $ok = $id && DB::value("SELECT id FROM content_blocks WHERE id = ? AND library_name IS NOT NULL AND type <> 'library_ref'", [$id]);
                return [$ok ? $id : 0, null];
            case 'repeater':
                return self::repeater($f, is_array($raw) ? $raw : []);
        }
        return ['', null];
    }

    /** @return array{0:array, 1:?string} */
    private static function repeater(array $f, array $rows): array
    {
        $items = [];
        $error = null;
        foreach ($rows as $row) {
            if (!is_array($row) || !empty($row['_remove'])) {
                continue;
            }
            $item = [];
            $empty = true;
            foreach ($f['fields'] as $sub) {
                [$v, $e] = self::value($sub, $row[$sub['name']] ?? null);
                if ($e !== null) {
                    $error = $e;
                }
                $item[$sub['name']] = $v;
                if ($sub['type'] !== 'select' && $v !== '' && $v !== 0) {
                    $empty = false;
                }
            }
            if (!$empty) {
                $items[] = ['_order' => is_numeric($row['_order'] ?? null) ? (int) $row['_order'] : 999] + $item;
            }
        }
        usort($items, fn ($a, $b) => $a['_order'] <=> $b['_order']);
        $items = array_map(function ($i) {
            unset($i['_order']);
            return $i;
        }, array_slice($items, 0, (int) ($f['max'] ?? 50)));
        return [$items, $error];
    }

    public static function validLink(string $link): bool
    {
        if (str_contains($link, '\\') || preg_match('/[\x00-\x1f\s]/', $link)) {
            return false;
        }
        if ($link[0] === '/' && !str_starts_with($link, '//')) {
            return (bool) preg_match('~^/[A-Za-z0-9\-._/?=&%#]*$~', $link);
        }
        if ($link[0] === '#') {
            return (bool) preg_match('/^#[A-Za-z0-9\-_]*$/', $link);
        }
        if (preg_match('#^https?://#i', $link)) {
            return filter_var($link, FILTER_VALIDATE_URL) !== false;
        }
        if (preg_match('#^mailto:[^@\s]+@[^@\s]+$#i', $link)) {
            return true;
        }
        return (bool) preg_match('#^tel:\+?[0-9 ()/-]{3,30}$#', $link);
    }

    /** All media ids referenced by a data set (for usage tracking). */
    public static function mediaIds(array $fields, array $data): array
    {
        $ids = [];
        foreach ($fields as $f) {
            if ($f['type'] === 'media' && !empty($data[$f['name']])) {
                $ids[] = (int) $data[$f['name']];
            }
            if ($f['type'] === 'repeater' && !empty($data[$f['name']]) && is_array($data[$f['name']])) {
                foreach ($data[$f['name']] as $item) {
                    $ids = array_merge($ids, self::mediaIds($f['fields'], $item));
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /** Default values for a new block. */
    public static function defaults(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $out[$f['name']] = match ($f['type']) {
                'repeater', 'services' => [],
                'media', 'gallery', 'faq_category', 'library' => 0,
                default => (string) ($f['default'] ?? ''),
            };
        }
        return $out;
    }
}
