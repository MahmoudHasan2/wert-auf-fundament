<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Runs database/migrations/*.sql in order and records each in `migrations`.
 * Migration files must terminate every statement with ";" at the end of a line.
 */
final class Migrator
{
    public static function dir(): string
    {
        return BASE_PATH . '/database/migrations';
    }

    /** @return string[] */
    public static function available(): array
    {
        $files = glob(self::dir() . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return array_map(fn ($f) => basename($f, '.sql'), $files);
    }

    /** @return string[] */
    public static function applied(): array
    {
        try {
            return DB::column('SELECT version FROM migrations ORDER BY version');
        } catch (\PDOException) {
            return [];
        }
    }

    /** @return string[] */
    public static function pending(): array
    {
        return array_values(array_diff(self::available(), self::applied()));
    }

    /** @return string[] applied versions */
    public static function migrate(): array
    {
        DB::pdo()->exec('CREATE TABLE IF NOT EXISTS migrations (version VARCHAR(100) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $done = [];
        foreach (self::pending() as $version) {
            $sql = (string) file_get_contents(self::dir() . '/' . $version . '.sql');
            foreach (self::split($sql) as $statement) {
                DB::pdo()->exec($statement);
            }
            DB::insert('migrations', ['version' => $version, 'applied_at' => now()]);
            $done[] = $version;
        }
        return $done;
    }

    /** @return string[] */
    public static function split(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
        $parts = preg_split('/;\s*$/m', $sql) ?: [];
        return array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
    }
}
