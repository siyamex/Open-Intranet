<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Personal access token authentication: "Authorization: Bearer {selector}.{secret}".
 * Only a SHA-256 hash of the secret is stored.
 */
final class ApiAuth
{
    private static ?array $token = null;
    private static ?array $user = null;

    public static function attempt(): bool
    {
        $header = self::authorizationHeader();
        if (!preg_match('/^Bearer\s+([A-Za-z0-9]{16})\.([A-Za-z0-9]{40,64})$/', trim($header), $m)) {
            return false;
        }
        [, $selector, $secret] = $m;
        $row = DB::fetch('SELECT * FROM api_tokens WHERE selector = ?', [$selector]);
        if ($row === null || $row['revoked_at'] !== null) {
            return false;
        }
        if ($row['expires_at'] !== null && strtotime((string) $row['expires_at']) < time()) {
            return false;
        }
        if (!hash_equals((string) $row['token_hash'], hash('sha256', $secret))) {
            return false;
        }
        $user = DB::fetch("SELECT * FROM users WHERE id = ? AND status = 'active'", [(int) $row['user_id']]);
        if ($user === null) {
            return false;
        }
        DB::update('api_tokens', [
            'last_used_at' => date('Y-m-d H:i:s'),
            'last_used_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ], 'id = ?', [(int) $row['id']]);
        self::$token = $row;
        self::$user = $user;
        return true;
    }

    public static function user(): ?array
    {
        return self::$user;
    }

    public static function userId(): ?int
    {
        return self::$user !== null ? (int) self::$user['id'] : null;
    }

    /**
     * @return string[]
     */
    public static function scopes(): array
    {
        if (self::$token === null) {
            return [];
        }
        $scopes = json_decode((string) self::$token['scopes'], true);
        return is_array($scopes) ? $scopes : [];
    }

    public static function can(string $scope): bool
    {
        $scopes = self::scopes();
        return in_array('admin', $scopes, true) || in_array($scope, $scopes, true);
    }

    /**
     * Generate a new token: returns [plainTextToken, dbRow-to-insert].
     *
     * @return array{plain: string, selector: string, hash: string}
     */
    public static function generate(): array
    {
        $selector = self::randomAlnum(16);
        $secret = self::randomAlnum(48);
        return [
            'plain' => $selector . '.' . $secret,
            'selector' => $selector,
            'hash' => hash('sha256', $secret),
        ];
    }

    /**
     * Some SAPI/webserver combinations (e.g. Apache + mod_php on XAMPP)
     * strip the Authorization header from $_SERVER; getallheaders() still
     * has it. Check both.
     */
    private static function authorizationHeader(): string
    {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key])) {
                return (string) $_SERVER[$key];
            }
        }
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    return (string) $value;
                }
            }
        }
        return '';
    }

    private static function randomAlnum(int $length): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes($length))), 0, $length);
    }
}
