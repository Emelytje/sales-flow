<?php
/**
 * Database-backed rate limiter (works on shared hosting without APCu/Redis).
 */

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    /**
     * Returns true when the action is allowed, false when the limit is hit.
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $db = Database::instance();
        $now = time();
        $hash = hash('sha256', $key);

        $row = $db->first('SELECT * FROM rate_limits WHERE bucket = ?', [$hash]);

        if ($row === null) {
            $db->insert('rate_limits', [
                'bucket'      => $hash,
                'hits'        => 1,
                'reset_at'    => $now + $decaySeconds,
            ]);
            return true;
        }

        if ((int) $row['reset_at'] <= $now) {
            $db->update('rate_limits', ['hits' => 1, 'reset_at' => $now + $decaySeconds], ['id' => $row['id']]);
            return true;
        }

        if ((int) $row['hits'] >= $maxAttempts) {
            return false;
        }

        $db->update('rate_limits', ['hits' => (int) $row['hits'] + 1], ['id' => $row['id']]);
        return true;
    }

    public static function retriesLeft(string $key, int $maxAttempts): int
    {
        $hash = hash('sha256', $key);
        $row = Database::instance()->first('SELECT hits, reset_at FROM rate_limits WHERE bucket = ?', [$hash]);
        if ($row === null || (int) $row['reset_at'] <= time()) {
            return $maxAttempts;
        }
        return max(0, $maxAttempts - (int) $row['hits']);
    }

    public static function clear(string $key): void
    {
        Database::instance()->delete('rate_limits', ['bucket' => hash('sha256', $key)]);
    }
}
