<?php
declare(strict_types=1);

namespace App\Core;

/** Logs every error to storage/logs and never shows internals to visitors (unless app.debug). */
final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', is_debug() ? '1' : '0');
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity) || in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler(static function (\Throwable $e): void {
            self::log($e);
            self::renderFatal();
        });
        register_shutdown_function(static function (): void {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::log(sprintf('FATAL %s in %s:%d', $err['message'], $err['file'], $err['line']));
            }
        });
    }

    public static function log(\Throwable|string $e, string $level = 'ERROR'): void
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $msg = $e instanceof \Throwable
            ? sprintf('%s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()) . "\n" . $e->getTraceAsString()
            : $e;
        // Never log full request bodies or passwords – only the message & trace.
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', '[' . date('c') . "] $level $msg\n", FILE_APPEND | LOCK_EX);
    }

    public static function renderFatal(): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        $file = BASE_PATH . '/app/Views/errors/500-static.php';
        if (is_file($file)) {
            require $file;
        } else {
            echo 'Ein interner Fehler ist aufgetreten.';
        }
    }
}
