<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Migrator;
use App\Security\Auth;

/**
 * One-time installation: runs migrations, seeds initial content, creates the first administrator,
 * then writes storage/installed.lock. Once the lock exists the installer is permanently disabled.
 */
final class Installer
{
    public static function lockFile(): string
    {
        return storage_path('installed.lock');
    }

    public static function isInstalled(): bool
    {
        return is_file(self::lockFile());
    }

    /** @return string[] problems that prevent installation */
    public static function preflight(): array
    {
        $problems = [];
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $problems[] = 'PHP 8.2 oder neuer wird benötigt (aktuell ' . PHP_VERSION . ').';
        }
        foreach (['pdo_mysql', 'mbstring', 'gd', 'fileinfo', 'dom', 'openssl'] as $ext) {
            if (!extension_loaded($ext)) {
                $problems[] = "PHP-Erweiterung „{$ext}“ fehlt.";
            }
        }
        if (strlen((string) config('app.key')) < 32) {
            $problems[] = 'In der Konfiguration (env.php) fehlt app.key (mindestens 32 Zeichen).';
        }
        if (!DB::isConfigured()) {
            $problems[] = 'Die Datenbank-Zugangsdaten sind in env.php nicht eingetragen.';
        } else {
            try {
                DB::pdo();
            } catch (\Throwable) {
                $problems[] = 'Keine Verbindung zur Datenbank möglich. Bitte Zugangsdaten in env.php prüfen.';
            }
        }
        foreach (['', 'logs', 'cache', 'backups'] as $dir) {
            $path = storage_path($dir);
            if (!is_dir($path) || !is_writable($path)) {
                $problems[] = 'Verzeichnis „storage' . ($dir !== '' ? '/' . $dir : '') . '“ ist nicht beschreibbar.';
            }
        }
        if (!is_writable(public_path('uploads'))) {
            $problems[] = 'Verzeichnis „public/uploads“ ist nicht beschreibbar.';
        }
        return $problems;
    }

    /**
     * Writes env.php (setup wizard). Preferred location: ../waf-private/env.php, i.e. one level above
     * the project and therefore outside public_html. Fallback: config/env.php (protected by .htaccess).
     * @return string path of the written file
     */
    public static function writeConfig(array $values): string
    {
        $dir = dirname(BASE_PATH) . '/waf-private';
        if ((is_dir($dir) || @mkdir($dir, 0700, true)) && is_writable($dir)) {
            $target = $dir . '/env.php';
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        } elseif (is_writable(BASE_PATH . '/config')) {
            $target = BASE_PATH . '/config/env.php';
        } else {
            throw new \RuntimeException('Keine Schreibrechte für die Konfigurationsdatei. Bitte config/env.php manuell anlegen (Vorlage: config/env.example.php).');
        }
        $php = "<?php\n// Erzeugt vom Installationsassistenten am " . date('d.m.Y H:i') . ". Enthält Zugangsdaten – nicht weitergeben.\nreturn "
            . var_export($values, true) . ";\n";
        if (file_put_contents($target, $php, LOCK_EX) === false) {
            throw new \RuntimeException('Die Konfigurationsdatei konnte nicht geschrieben werden.');
        }
        @chmod($target, 0600);
        return $target;
    }

    public static function tokenConfigured(): bool
    {
        return strlen((string) config('install.token')) >= 16;
    }

    /**
     * @param array{name:string,email:string,password:string} $admin
     */
    public static function install(array $admin): void
    {
        if (self::isInstalled()) {
            throw new \RuntimeException('Bereits installiert.');
        }
        @set_time_limit(600); // image import of the seed content can take a while on shared hosting
        Migrator::migrate();
        if ((int) DB::value('SELECT COUNT(*) FROM admins') > 0) {
            throw new \RuntimeException('Es existiert bereits ein Administrator. Installation abgebrochen.');
        }
        $adminId = DB::insert('admins', [
            'name' => $admin['name'],
            'email' => mb_strtolower($admin['email']),
            'password_hash' => Auth::hash($admin['password']),
            'role' => 'admin',
            'is_active' => 1,
            'password_changed_at' => now(),
        ]);
        if ((int) DB::value('SELECT COUNT(*) FROM pages') === 0) {
            require_once BASE_PATH . '/database/seeds/InitialContent.php';
            (new \InitialContent($adminId))->run();
        }
        file_put_contents(self::lockFile(), 'installed ' . date('c') . "\n");
        DB::insert('audit_log', ['admin_id' => $adminId, 'admin_name' => $admin['name'], 'action' => 'install', 'summary' => 'Erstinstallation abgeschlossen', 'created_at' => now()]);
    }
}
