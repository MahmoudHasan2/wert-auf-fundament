<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Security\Auth;
use App\Security\RateLimiter;
use App\Services\Installer;

/**
 * Two-step web installer.
 *  Step 1 (only while no env.php exists): database details → env.php is written automatically.
 *  Step 2: create tables, content and the first administrator.
 * Protection: disabled forever once storage/installed.lock exists; step 2 needs either the browser
 * session that completed step 1 or the install token from env.php; CSRF on every POST.
 */
final class InstallController extends AdminController
{
    private function guard(): void
    {
        if (Installer::isInstalled()) {
            throw HttpException::notFound();
        }
    }

    private function authorized(): bool
    {
        return Session::get('install_authorized') === true;
    }

    public function form(): Response
    {
        $this->guard();
        if (Config::envFile() === null) {
            $https = $this->request->isHttps();
            $host = preg_replace('/[^a-z0-9.:-]/i', '', (string) ($this->request->server['HTTP_HOST'] ?? 'localhost'));
            return $this->view('install-config', ['detectedUrl' => ($https ? 'https://' : 'http://') . $host . base_path()], 'Installation – Schritt 1');
        }
        return $this->view('install', [
            'problems' => Installer::preflight(),
            'needToken' => !$this->authorized(),
            'tokenConfigured' => Installer::tokenConfigured(),
        ], 'Installation – Schritt 2');
    }

    public function config(): Response
    {
        $this->guard();
        if (Config::envFile() !== null) {
            return $this->redirect('/admin/install');
        }
        $r = $this->request;
        $url = rtrim($r->str('app_url', 300), '/');
        $db = [
            'host' => $r->str('db_host', 190) ?: 'localhost',
            'port' => $r->int('db_port', 3306) ?: 3306,
            'name' => $r->str('db_name', 64),
            'user' => $r->str('db_user', 64),
            'pass' => (string) $r->input('db_pass', ''),
            'charset' => 'utf8mb4',
        ];
        $errors = [];
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            $errors['app_url'] = 'Bitte die vollständige Adresse angeben, z. B. https://www.wertauffundament.de';
        }
        if ($db['name'] === '' || $db['user'] === '') {
            $errors['db_name'] = 'Bitte Datenbankname und Benutzer angeben.';
        }
        if (!$errors) {
            try {
                new \PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']), $db['user'], $db['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 8]);
            } catch (\PDOException $e) {
                $errors['db_name'] = 'Verbindung fehlgeschlagen: ' . mb_substr(preg_replace('/\s+/', ' ', $e->getMessage()) ?? '', 0, 200)
                    . ' – Bitte Angaben aus hPanel → Datenbanken prüfen (Host ist bei Hostinger meist „localhost“).';
            }
        }
        if ($errors) {
            return $this->invalid('/admin/install', $errors);
        }
        try {
            $path = Installer::writeConfig([
                'app' => ['url' => $url, 'env' => 'production', 'debug' => false, 'key' => bin2hex(random_bytes(32)), 'force_https' => str_starts_with($url, 'https://'), 'hsts' => false],
                'db' => $db,
                'mail' => ['transport' => 'none', 'host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl', 'username' => '', 'password' => '', 'from_address' => '', 'from_name' => 'Website Wert auf Fundament'],
                'install' => ['token' => ''],
            ]);
        } catch (\Throwable $e) {
            return $this->redirect('/admin/install', 'error', $e->getMessage());
        }
        Session::regenerate();
        Session::set('install_authorized', true);
        $where = str_contains(str_replace('\\', '/', $path), '/waf-private/') ? 'außerhalb des öffentlichen Verzeichnisses (Ordner „waf-private“)' : 'unter config/env.php';
        return $this->redirect('/admin/install', 'success', 'Datenbankverbindung erfolgreich. Die Konfiguration wurde ' . $where . ' gespeichert.');
    }

    public function run(): Response
    {
        $this->guard();
        if (Config::envFile() === null) {
            return $this->redirect('/admin/install');
        }
        $problems = Installer::preflight();
        if ($problems || (!$this->authorized() && !Installer::tokenConfigured())) {
            return $this->redirect('/admin/install', 'error', 'Die Voraussetzungen für die Installation sind nicht erfüllt.');
        }
        $r = $this->request;
        $errors = [];
        if (!$this->authorized()) {
            try {
                if (RateLimiter::tooMany('install', $r->ip(), 10, 900)) {
                    return $this->redirect('/admin/install', 'error', 'Zu viele Versuche. Bitte später erneut versuchen.');
                }
            } catch (\Throwable) {
                // rate_limits table does not exist before the first migration – token check still applies
            }
            if (!hash_equals((string) config('install.token'), $r->str('token', 200))) {
                try {
                    RateLimiter::hit('install', $r->ip(), 900);
                } catch (\Throwable) {
                }
                $errors['token'] = 'Das Installations-Token ist nicht korrekt.';
            }
        }
        $name = $r->str('name', 120);
        $email = mb_strtolower($r->str('email', 190));
        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Bitte einen Namen angeben.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse angeben.';
        }
        $pw = (string) $r->input('password', '');
        $pwErrors = Auth::validatePassword($pw, (string) $r->input('password_confirm', ''), $email, $name);
        if ($pwErrors) {
            $errors['password'] = implode(' ', $pwErrors);
        }
        if ($errors) {
            return $this->invalid('/admin/install', $errors);
        }
        try {
            DB::reset();
            Installer::install(['name' => $name, 'email' => $email, 'password' => $pw]);
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $this->redirect('/admin/install', 'error', 'Die Installation ist fehlgeschlagen. Details stehen im Fehlerprotokoll (storage/logs).');
        }
        Session::forget('install_authorized');
        return $this->redirect('/admin/login', 'success', 'Installation abgeschlossen. Bitte melden Sie sich jetzt an.');
    }
}
