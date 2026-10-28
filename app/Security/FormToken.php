<?php
declare(strict_types=1);

namespace App\Security;

/**
 * Stateless, HMAC-signed token for PUBLIC forms (contact form).
 * Gives CSRF/replay-window protection and a minimum fill-in time without a session cookie,
 * so the public website does not need to set any cookie.
 */
final class FormToken
{
    public const MIN_SECONDS = 3;
    public const MAX_SECONDS = 7200;

    public static function issue(string $form): string
    {
        $payload = time() . '.' . bin2hex(random_bytes(8));
        return $payload . '.' . self::sign($form, $payload);
    }

    /** @return 'ok'|'invalid'|'too_fast'|'expired' */
    public static function check(string $form, string $token): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || !ctype_digit($parts[0])) {
            return 'invalid';
        }
        $payload = $parts[0] . '.' . $parts[1];
        if (!hash_equals(self::sign($form, $payload), $parts[2])) {
            return 'invalid';
        }
        $age = time() - (int) $parts[0];
        if ($age < self::MIN_SECONDS) {
            return 'too_fast';
        }
        if ($age > self::MAX_SECONDS) {
            return 'expired';
        }
        return 'ok';
    }

    private static function sign(string $form, string $payload): string
    {
        $key = (string) config('app.key');
        if (strlen($key) < 32) {
            throw new \RuntimeException('app.key is not configured (min. 32 characters).');
        }
        return hash_hmac('sha256', $form . '|' . $payload, $key);
    }
}
