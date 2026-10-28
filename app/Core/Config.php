<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];
    private static ?string $envFile = null;

    public static function load(): void
    {
        $defaults = require BASE_PATH . '/config/app.php';
        $env = [];
        foreach (self::candidates() as $file) {
            if ($file !== '' && is_file($file)) {
                $loaded = require $file;
                if (is_array($loaded)) {
                    $env = $loaded;
                    self::$envFile = $file;
                }
                break;
            }
        }
        self::$items = array_replace_recursive($defaults, $env);
    }

    /** @return string[] */
    public static function candidates(): array
    {
        return [
            (string) (getenv('WAF_ENV_FILE') ?: ''),
            dirname(BASE_PATH) . '/waf-private/env.php',
            BASE_PATH . '/config/env.php',
        ];
    }

    public static function envFile(): ?string
    {
        return self::$envFile;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    /** Used by tests only. */
    public static function set(string $key, mixed $value): void
    {
        $ref = &self::$items;
        foreach (explode('.', $key) as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref = &$ref[$part];
        }
        $ref = $value;
    }
}
