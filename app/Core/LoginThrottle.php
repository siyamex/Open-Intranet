<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Login rate limiting backed by the login_attempts table:
 * max 5 failures per email+IP within 15 minutes.
 */
final class LoginThrottle
{
    private const MAX_FAILURES = 5;
    private const WINDOW_MINUTES = 15;

    public static function tooMany(string $email, string $ip): bool
    {
        return self::failureCount($email, $ip) >= self::MAX_FAILURES;
    }

    public static function minutesLeft(string $email, string $ip): int
    {
        $oldest = DB::scalar(
            'SELECT MIN(created_at) FROM (
                SELECT created_at FROM login_attempts
                WHERE email = ? AND ip = ? AND succeeded = 0 AND created_at > ?
                ORDER BY created_at DESC LIMIT ?
            ) recent',
            [$email, $ip, self::cutoff(), self::MAX_FAILURES]
        );
        if ($oldest === null) {
            return 0;
        }
        $left = (int) ceil((strtotime((string) $oldest) + self::WINDOW_MINUTES * 60 - time()) / 60);
        return max(1, $left);
    }

    public static function record(string $email, string $ip, bool $succeeded): void
    {
        DB::insert('login_attempts', [
            'email' => substr($email, 0, 190),
            'ip' => substr($ip, 0, 45),
            'succeeded' => $succeeded ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($succeeded) {
            // A successful login clears the failure window for this pair.
            DB::delete('login_attempts', 'email = ? AND ip = ? AND succeeded = 0', [$email, $ip]);
        }
    }

    private static function failureCount(string $email, string $ip): int
    {
        return (int) DB::scalar(
            'SELECT COUNT(*) FROM login_attempts
             WHERE email = ? AND ip = ? AND succeeded = 0 AND created_at > ?',
            [$email, $ip, self::cutoff()]
        );
    }

    private static function cutoff(): string
    {
        return date('Y-m-d H:i:s', time() - self::WINDOW_MINUTES * 60);
    }
}
