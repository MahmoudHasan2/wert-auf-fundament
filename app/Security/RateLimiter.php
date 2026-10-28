<?php
declare(strict_types=1);

namespace App\Security;

use App\Core\DB;

/** Fixed-window rate limiter stored in MySQL (works on shared hosting without Redis). */
final class RateLimiter
{
    private static function key(string $key): string
    {
        return hash_hmac('sha256', $key, (string) config('app.key', 'waf'));
    }

    public static function tooMany(string $bucket, string $key, int $max, int $windowSeconds): bool
    {
        $row = DB::row('SELECT hits, window_start FROM rate_limits WHERE bucket = ? AND key_hash = ?', [$bucket, self::key($key)]);
        if (!$row) {
            return false;
        }
        if (strtotime($row['window_start']) + $windowSeconds < time()) {
            return false;
        }
        return (int) $row['hits'] >= $max;
    }

    public static function hit(string $bucket, string $key, int $windowSeconds): void
    {
        $hash = self::key($key);
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        DB::exec(
            'INSERT INTO rate_limits (bucket, key_hash, hits, window_start) VALUES (?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE
               hits = IF(window_start < ?, 1, hits + 1),
               window_start = IF(window_start < ?, VALUES(window_start), window_start)',
            [$bucket, $hash, now(), $cutoff, $cutoff]
        );
        // Opportunistic cleanup (1 % of requests) – no cron needed.
        if (random_int(1, 100) === 1) {
            DB::exec('DELETE FROM rate_limits WHERE window_start < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        }
    }

    public static function clear(string $bucket, string $key): void
    {
        DB::exec('DELETE FROM rate_limits WHERE bucket = ? AND key_hash = ?', [$bucket, self::key($key)]);
    }
}
