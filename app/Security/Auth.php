<?php
declare(strict_types=1);

namespace App\Security;

use App\Core\DB;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditLog;

final class Auth
{
    /** Role => permissions. "*" = everything. */
    public const ROLES = [
        'admin'  => ['*'],
        'editor' => ['content', 'media', 'enquiries'],
    ];

    public const ROLE_LABELS = [
        'admin'  => 'Administrator (alle Rechte)',
        'editor' => 'Redakteur (Inhalte, Medien, Anfragen)',
    ];

    public const MIN_PASSWORD_LENGTH = 12;

    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = (int) Session::get('admin_id', 0);
            if ($id > 0) {
                $u = DB::row('SELECT id, name, email, role, is_active, password_changed_at FROM admins WHERE id = ?', [$id]);
                // Invalidate sessions of deactivated users or after a password change elsewhere.
                if ($u && (int) $u['is_active'] === 1 && (string) $u['password_changed_at'] === (string) Session::get('pw_stamp', '')) {
                    self::$user = $u;
                } else {
                    Session::destroy();
                }
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function can(string $perm): bool
    {
        $u = self::user();
        if (!$u) {
            return false;
        }
        $perms = self::ROLES[$u['role']] ?? [];
        return in_array('*', $perms, true) || in_array($perm, $perms, true);
    }

    /**
     * @return array{ok:bool, error?:string}
     */
    public static function attempt(Request $request, string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $ipKey = $request->ip();
        $comboKey = $email . '|' . $request->ip();
        if (RateLimiter::tooMany('login_ip', $ipKey, 20, 900) || RateLimiter::tooMany('login', $comboKey, 5, 900)) {
            AuditLog::record('login.throttled', 'admin', null, 'Anmeldung gesperrt (zu viele Versuche)', $request);
            return ['ok' => false, 'error' => 'Zu viele Anmeldeversuche. Bitte warten Sie 15 Minuten und versuchen Sie es erneut.'];
        }

        $u = DB::row('SELECT * FROM admins WHERE email = ?', [$email]);
        // Always run password_verify to keep timing similar for unknown accounts.
        $hash = $u['password_hash'] ?? '$2y$10$1i8tGFd8r6jOHRQvJDbNT.XxXtHCfFQdaV2Q2V4fDKiJx/msqsrMG';
        $valid = password_verify($password, $hash);

        if (!$u || !$valid || (int) $u['is_active'] !== 1) {
            RateLimiter::hit('login_ip', $ipKey, 900);
            RateLimiter::hit('login', $comboKey, 900);
            AuditLog::record('login.failed', 'admin', $u ? (int) $u['id'] : null, 'Fehlgeschlagene Anmeldung', $request, $u ? $u['name'] : '');
            return ['ok' => false, 'error' => 'E-Mail-Adresse oder Passwort ist nicht korrekt.'];
        }

        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        }
        RateLimiter::clear('login', $comboKey);
        self::loginAs($u);
        DB::update('admins', ['last_login_at' => now()], 'id = :id', ['id' => $u['id']]);
        AuditLog::record('login.success', 'admin', (int) $u['id'], 'Anmeldung erfolgreich', $request);
        return ['ok' => true];
    }

    public static function loginAs(array $u): void
    {
        Session::regenerate(); // prevents session fixation
        Session::set('admin_id', (int) $u['id']);
        Session::set('pw_stamp', (string) $u['password_changed_at']);
        self::$loaded = false;
        self::$user = null;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }

    /** Re-reads the current user's password stamp after they changed their own password. */
    public static function refreshStamp(): void
    {
        $id = (int) Session::get('admin_id', 0);
        $stamp = (string) DB::value('SELECT password_changed_at FROM admins WHERE id = ?', [$id]);
        Session::regenerate();
        Session::set('admin_id', $id);
        Session::set('pw_stamp', $stamp);
        self::$loaded = false;
    }

    /** @return string[] validation errors */
    public static function validatePassword(string $password, string $confirm, string $email = '', string $name = ''): array
    {
        $errors = [];
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors[] = 'Das Passwort muss mindestens ' . self::MIN_PASSWORD_LENGTH . ' Zeichen lang sein.';
        }
        if (mb_strlen($password) > 200) {
            $errors[] = 'Das Passwort ist zu lang.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Die Passwörter stimmen nicht überein.';
        }
        $lower = mb_strtolower($password);
        foreach (array_filter([mb_strtolower($email), mb_strtolower(strtok($email, '@') ?: ''), mb_strtolower($name)]) as $part) {
            if (mb_strlen($part) >= 4 && str_contains($lower, $part)) {
                $errors[] = 'Das Passwort darf nicht den Namen oder die E-Mail-Adresse enthalten.';
                break;
            }
        }
        if (in_array($lower, ['passwort1234', 'password1234', '123456789012', 'qwertzuiopü1'], true)) {
            $errors[] = 'Dieses Passwort ist zu leicht zu erraten.';
        }
        return $errors;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
