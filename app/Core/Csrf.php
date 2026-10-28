<?php
declare(strict_types=1);

namespace App\Core;

/** Synchroniser-token CSRF protection for the (session based) admin area. */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(Request $request): bool
    {
        $sent = $request->post['_csrf'] ?? $request->header('X-CSRF-Token');
        $known = $_SESSION['_csrf'] ?? '';
        return is_string($sent) && is_string($known) && $known !== '' && hash_equals($known, $sent);
    }
}
