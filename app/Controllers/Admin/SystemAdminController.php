<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\Migrator;
use App\Core\Response;
use App\Services\AuditLog;
use App\Services\Compliance;
use App\Services\Mailer;
use App\Services\Settings;

final class SystemAdminController extends AdminController
{
    public function index(): Response
    {
        $checks = [];
        $add = function (string $label, bool $ok, string $detail, bool $warnOnly = false) use (&$checks): void {
            $checks[] = compact('label', 'ok', 'detail', 'warnOnly');
        };
        $add('PHP-Version', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION . ' (benötigt: 8.2 oder neuer)');
        foreach (['pdo_mysql', 'mbstring', 'gd', 'fileinfo', 'dom', 'openssl'] as $ext) {
            $add('Erweiterung ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'vorhanden' : 'fehlt');
        }
        $add('WebP-Unterstützung', function_exists('imagewebp'), function_exists('imagewebp') ? 'Bilder werden als WebP gespeichert' : 'Bilder werden als JPEG/PNG gespeichert', true);
        $add('ZIP-Erweiterung', class_exists(\ZipArchive::class), class_exists(\ZipArchive::class) ? 'Medien-Archive möglich' : 'Medien-Backups nur per FTP', true);
        $add('EXIF-Erweiterung', function_exists('exif_read_data'), function_exists('exif_read_data') ? 'Handyfotos werden automatisch gedreht' : 'automatische Drehung von Handyfotos nicht möglich', true);
        foreach (['storage/logs' => storage_path('logs'), 'storage/cache' => storage_path('cache'), 'storage/backups' => storage_path('backups'), 'public/uploads' => public_path('uploads')] as $dir => $abs) {
            $w = is_writable($abs);
            $add('Schreibrechte ' . $dir, $w, $w ? 'beschreibbar' : 'nicht beschreibbar');
        }
        $https = $this->request->isHttps();
        $add('HTTPS', $https, $https ? 'aktive Verbindung ist verschlüsselt' : 'Verbindung ist NICHT verschlüsselt', true);
        $add('Debug-Modus', !is_debug(), is_debug() ? 'AKTIV – auf dem Live-Server deaktivieren' : 'deaktiviert');
        $envFile = Config::envFile();
        $insidePublic = $envFile !== null && str_starts_with(str_replace('\\', '/', (string) realpath($envFile)), str_replace('\\', '/', (string) realpath(BASE_PATH . '/public')));
        $add('Konfigurationsdatei', $envFile !== null && !$insidePublic, $envFile === null ? 'keine env.php gefunden' : ($insidePublic ? 'liegt im öffentlichen Verzeichnis!' : (str_starts_with(str_replace('\\', '/', $envFile), str_replace('\\', '/', BASE_PATH)) ? 'im Projekt (per .htaccess geschützt)' : 'außerhalb des Projekts (empfohlen)')));
        $add('Upload-Limit (PHP)', true, 'upload_max_filesize ' . ini_get('upload_max_filesize') . ', post_max_size ' . ini_get('post_max_size'));

        $logs = [];
        foreach (array_slice(array_reverse(glob(storage_path('logs/app-*.log')) ?: []), 0, 7) as $f) {
            $logs[] = ['name' => basename($f), 'size' => filesize($f)];
        }

        return $this->view('system', [
            'checks' => $checks,
            'dbVersion' => (string) DB::value('SELECT VERSION()'),
            'pending' => Migrator::pending(),
            'applied' => Migrator::applied(),
            'mailOk' => Mailer::isConfigured(),
            'mailTransport' => Mailer::transport(),
            'compliance' => Compliance::checks(),
            'logs' => $logs,
            'appUrl' => (string) config('app.url'),
        ], 'Systemstatus');
    }

    public function migrate(): Response
    {
        try {
            $done = Migrator::migrate();
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $this->redirect('/admin/system', 'error', 'Die Datenbankaktualisierung ist fehlgeschlagen. Bitte Backup einspielen und Fehlerprotokoll prüfen.');
        }
        $this->audit('system.migrate', 'system', null, $done ? 'Migrationen ausgeführt: ' . implode(', ', $done) : 'Keine offenen Migrationen');
        return $this->redirect('/admin/system', 'success', $done ? count($done) . ' Datenbankaktualisierung(en) ausgeführt.' : 'Die Datenbank ist bereits aktuell.');
    }

    public function testMail(): Response
    {
        $to = $this->user['email'];
        try {
            Mailer::send($to, 'Testnachricht – ' . Settings::get('company_name'), "Diese Testnachricht bestätigt, dass der E-Mail-Versand der Website funktioniert.\n\nGesendet: " . date('d.m.Y H:i:s') . "\n");
        } catch (\Throwable $e) {
            return $this->redirect('/admin/system', 'error', $e->getMessage());
        }
        $this->audit('system.testmail', 'system', null, 'Testmail versendet');
        return $this->redirect('/admin/system', 'success', 'Testnachricht an ' . $to . ' versendet. Bitte Posteingang (und Spam-Ordner) prüfen.');
    }

    public function auditLog(): Response
    {
        $action = $this->request->q('aktion');
        $where = '';
        $params = [];
        if ($action !== '') {
            $where = ' WHERE action LIKE ?';
            $params[] = $action . '%';
        }
        $pager = $this->paginator((int) DB::value('SELECT COUNT(*) FROM audit_log' . $where, $params), 50);
        $rows = DB::all('SELECT * FROM audit_log' . $where . ' ORDER BY id DESC LIMIT ' . $pager->perPage . ' OFFSET ' . $pager->offset, $params);
        $groups = DB::column("SELECT DISTINCT SUBSTRING_INDEX(action, '.', 1) FROM audit_log ORDER BY 1");
        return $this->view('audit', ['rows' => $rows, 'pager' => $pager, 'action' => $action, 'groups' => $groups, 'labels' => AuditLog::LABELS], 'Protokoll');
    }
}
