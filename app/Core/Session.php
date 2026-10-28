<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session handling for the admin area only. The public website never starts a session
 * and therefore sets no cookies.
 */
final class Session
{
    private static bool $started = false;

    public static function start(Request $request): void
    {
        if (self::$started || PHP_SAPI === 'cli' && !defined('WAF_TEST_SESSION')) {
            self::$started = true;
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        $savePath = storage_path('cache/sessions');
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0700, true);
        }
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
        }
        session_name('waf_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => url('/admin'),
            'secure'   => $request->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        self::$started = true;

        $now = time();
        $idle = (int) config('session.idle_timeout', 7200);
        $absolute = (int) config('session.absolute_timeout', 43200);
        if (isset($_SESSION['_last']) && ($now - (int) $_SESSION['_last'] > $idle || $now - (int) ($_SESSION['_created'] ?? $now) > $absolute)) {
            self::destroy();
            session_start();
            $_SESSION['_flash'] = ['warning' => ['Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.']];
        }
        $_SESSION['_created'] ??= $now;
        $_SESSION['_last'] = $now;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['_created'] = time();
        unset($_SESSION['_csrf']);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
            session_destroy();
        }
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /** @return array<string, string[]> */
    public static function pullFlash(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }

    /** Stores old form input + errors for one redirect. */
    public static function flashInput(array $input, array $errors = []): void
    {
        unset($input['_csrf'], $input['password'], $input['password_confirm'], $input['current_password'], $input['db_pass'], $input['token']);
        $_SESSION['_old'] = $input;
        $_SESSION['_errors'] = $errors;
    }

    public static function pullOld(): array
    {
        $o = ['old' => $_SESSION['_old'] ?? [], 'errors' => $_SESSION['_errors'] ?? []];
        unset($_SESSION['_old'], $_SESSION['_errors']);
        return $o;
    }
}
