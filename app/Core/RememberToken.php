<?php

declare(strict_types=1);

namespace App\Core;

/**
 * "Remember me" split-token cookie: selector (lookup) + validator (secret).
 * Only a SHA-256 hash of the validator is stored; the token is rotated on
 * every successful use and cleared on logout.
 */
final class RememberToken
{
    private const COOKIE = 'openintranet_remember';
    private const LIFETIME_DAYS = 30;

    public static function issue(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        DB::insert('remember_tokens', [
            'user_id' => $userId,
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at' => date('Y-m-d H:i:s', time() + self::LIFETIME_DAYS * 86400),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        self::setCookie($selector . ':' . $validator, time() + self::LIFETIME_DAYS * 86400);
    }

    /**
     * Try to authenticate from the cookie. Rotates the token on success.
     */
    public static function attempt(): ?array
    {
        $raw = $_COOKIE[self::COOKIE] ?? null;
        if (!is_string($raw) || !str_contains($raw, ':')) {
            return null;
        }
        [$selector, $validator] = explode(':', $raw, 2);
        if ($selector === '' || $validator === '') {
            return null;
        }
        $row = DB::fetch('SELECT * FROM remember_tokens WHERE selector = ?', [$selector]);
        if ($row === null) {
            self::setCookie('', time() - 3600);
            return null;
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            DB::delete('remember_tokens', 'id = ?', [(int) $row['id']]);
            self::setCookie('', time() - 3600);
            return null;
        }
        if (!hash_equals((string) $row['validator_hash'], hash('sha256', $validator))) {
            // Possible cookie theft — invalidate every token for this user.
            DB::delete('remember_tokens', 'user_id = ?', [(int) $row['user_id']]);
            self::setCookie('', time() - 3600);
            return null;
        }
        $user = DB::fetch("SELECT * FROM users WHERE id = ? AND status = 'active'", [(int) $row['user_id']]);
        if ($user === null) {
            DB::delete('remember_tokens', 'id = ?', [(int) $row['id']]);
            self::setCookie('', time() - 3600);
            return null;
        }
        // Rotate on every use
        DB::delete('remember_tokens', 'id = ?', [(int) $row['id']]);
        self::issue((int) $user['id']);
        return $user;
    }

    public static function clear(): void
    {
        $raw = $_COOKIE[self::COOKIE] ?? null;
        if (is_string($raw) && str_contains($raw, ':')) {
            [$selector] = explode(':', $raw, 2);
            if ($selector !== '') {
                try {
                    DB::delete('remember_tokens', 'selector = ?', [$selector]);
                } catch (\Throwable) {
                    // ignore — cookie is cleared regardless
                }
            }
        }
        self::setCookie('', time() - 3600);
    }

    public static function clearAllFor(int $userId): void
    {
        DB::delete('remember_tokens', 'user_id = ?', [$userId]);
    }

    private static function setCookie(string $value, int $expires): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
