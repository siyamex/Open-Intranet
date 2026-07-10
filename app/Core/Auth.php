<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;

    /** @var string[]|null */
    private static ?array $permissions = null;

    /** @var string[]|null */
    private static ?array $roles = null;

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $id = $_SESSION['user_id'] ?? null;
        if ($id !== null) {
            $user = DB::fetch("SELECT * FROM users WHERE id = ? AND status = 'active'", [(int) $id]);
            if ($user !== null) {
                self::$user = $user;
                return $user;
            }
            unset($_SESSION['user_id']);
        }
        $user = RememberToken::attempt();
        if ($user !== null) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            self::$user = $user;
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user === null ? null : (int) $user['id'];
    }

    /**
     * Log a user in: regenerate the session id and update last_login_at.
     */
    public static function login(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = $user;
        self::$resolved = true;
        self::$permissions = null;
        self::$roles = null;
        DB::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $user['id']]);
        if ($remember) {
            RememberToken::issue((int) $user['id']);
        }
    }

    public static function logout(): void
    {
        RememberToken::clear();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $p['path'],
                'domain' => $p['domain'],
                'secure' => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
        self::$user = null;
        self::$resolved = true;
        self::$permissions = null;
        self::$roles = null;
    }

    /**
     * @return string[] role slugs of the current user
     */
    public static function roles(): array
    {
        if (!self::check()) {
            return [];
        }
        if (self::$roles === null) {
            self::$roles = array_column(DB::fetchAll(
                'SELECT r.slug FROM roles r JOIN user_role ur ON ur.role_id = r.id WHERE ur.user_id = ?',
                [self::id()]
            ), 'slug');
        }
        return self::$roles;
    }

    public static function hasRole(string $slug): bool
    {
        return in_array($slug, self::roles(), true);
    }

    /**
     * @return string[] permission slugs of the current user
     */
    public static function permissions(): array
    {
        if (!self::check()) {
            return [];
        }
        if (self::$permissions === null) {
            self::$permissions = array_column(DB::fetchAll(
                'SELECT DISTINCT p.slug
                 FROM permissions p
                 JOIN role_permission rp ON rp.permission_id = p.id
                 JOIN user_role ur ON ur.role_id = rp.role_id
                 WHERE ur.user_id = ?',
                [self::id()]
            ), 'slug');
        }
        return self::$permissions;
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::hasRole('super_admin')) {
            return true;
        }
        return in_array($permission, self::permissions(), true);
    }

    /**
     * Forget the cached user so the next Auth::user() re-reads the DB
     * (used after impersonation or profile updates).
     */
    public static function refresh(): void
    {
        self::$user = null;
        self::$resolved = false;
        self::$permissions = null;
        self::$roles = null;
    }
}
