<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * Database dump/restore in pure PHP (no mysqldump/shell needed) and a ZIP archive of uploads.
 * Files live in storage/backups (denied via .htaccess, downloads only through the admin).
 *
 * Dump format: one SQL statement per line (newlines inside values are escaped), so restore can
 * stream the file line by line without a SQL parser.
 */
final class Backup
{
    private const NAME_PATTERN = '/^(db|media)-\d{8}-\d{6}-[a-f0-9]{8}\.(sql\.gz|sql|zip)$/';

    public static function dir(): string
    {
        return storage_path('backups');
    }

    public static function list(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/*') ?: [] as $f) {
            $name = basename($f);
            if (preg_match(self::NAME_PATTERN, $name)) {
                $out[] = ['name' => $name, 'size' => filesize($f), 'time' => filemtime($f), 'type' => str_starts_with($name, 'db-') ? 'db' : 'media'];
            }
        }
        usort($out, fn ($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /** Validates a backup name and returns its absolute path – prevents directory traversal. */
    public static function path(string $name): ?string
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            return null;
        }
        $path = self::dir() . '/' . $name;
        return is_file($path) ? $path : null;
    }

    private static function newName(string $prefix, string $ext): string
    {
        return $prefix . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    }

    public static function dumpDatabase(): string
    {
        @set_time_limit(300);
        $gz = function_exists('gzopen');
        $name = self::newName('db', $gz ? 'sql.gz' : 'sql');
        $path = self::dir() . '/' . $name;
        $fh = $gz ? gzopen($path, 'wb6') : fopen($path, 'wb');
        if (!$fh) {
            throw new \RuntimeException('Backup-Datei konnte nicht angelegt werden.');
        }
        $write = $gz ? fn ($s) => gzwrite($fh, $s) : fn ($s) => fwrite($fh, $s);
        $pdo = DB::pdo();
        $write('-- Wert auf Fundament CMS backup ' . date('c') . "\n");
        $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
        $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            if (!preg_match('/^[a-z0-9_]+$/i', $table)) {
                continue;
            }
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_NUM)[1];
            $write("DROP TABLE IF EXISTS `$table`;\n" . str_replace(["\r", "\n"], ' ', $create) . ";\n");
            if ($table === 'rate_limits') {
                continue; // transient data
            }
            $stmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $vals = array_map([self::class, 'quote'], array_values($row));
                $write("INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n");
            }
        }
        $write("SET FOREIGN_KEY_CHECKS=1;\n");
        $gz ? gzclose($fh) : fclose($fh);
        return $name;
    }

    private static function quote(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }
        return "'" . strtr((string) $v, ['\\' => '\\\\', "\0" => '\\0', "\n" => '\\n', "\r" => '\\r', "'" => "\\'", '"' => '\\"', "\x1a" => '\\Z']) . "'";
    }

    public static function restoreDatabase(string $name): void
    {
        $path = self::path($name);
        if (!$path || !str_starts_with($name, 'db-')) {
            throw new \RuntimeException('Backup nicht gefunden.');
        }
        @set_time_limit(600);
        $gz = str_ends_with($name, '.gz');
        $fh = $gz ? gzopen($path, 'rb') : fopen($path, 'rb');
        if (!$fh) {
            throw new \RuntimeException('Backup konnte nicht gelesen werden.');
        }
        $first = $gz ? gzgets($fh) : fgets($fh);
        if (!is_string($first) || !str_starts_with($first, '-- Wert auf Fundament CMS backup')) {
            $gz ? gzclose($fh) : fclose($fh);
            throw new \RuntimeException('Die Datei ist kein gültiges Backup dieser Anwendung.');
        }
        $pdo = DB::pdo();
        try {
            while (($line = $gz ? gzgets($fh) : fgets($fh)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '' || str_starts_with($line, '--')) {
                    continue;
                }
                // Only statement kinds that our own dump produces are executed.
                if (!preg_match('/^(SET (NAMES|FOREIGN_KEY_CHECKS)|DROP TABLE IF EXISTS `[a-z0-9_]+`|CREATE TABLE `[a-z0-9_]+`|INSERT INTO `[a-z0-9_]+` VALUES)/i', $line)) {
                    throw new \RuntimeException('Unerwartete Anweisung im Backup – Wiederherstellung abgebrochen.');
                }
                $pdo->exec(rtrim($line, ';'));
            }
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            $gz ? gzclose($fh) : fclose($fh);
        }
        Settings::flush();
    }

    public static function archiveMedia(): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Die PHP-Erweiterung „zip“ ist nicht verfügbar. Bitte Medien per FTP/Dateimanager sichern.');
        }
        @set_time_limit(600);
        $name = self::newName('media', 'zip');
        $zip = new \ZipArchive();
        if ($zip->open(self::dir() . '/' . $name, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('ZIP-Archiv konnte nicht angelegt werden.');
        }
        $root = public_path('uploads');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && preg_match('/\.(webp|jpg|png)$/', $file->getFilename())) {
                $zip->addFile($file->getPathname(), 'uploads/' . str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1)));
            }
        }
        $zip->close();
        return $name;
    }

    public static function delete(string $name): bool
    {
        $path = self::path($name);
        return $path !== null && @unlink($path);
    }
}
