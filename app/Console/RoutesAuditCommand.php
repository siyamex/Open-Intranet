<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Router;

/**
 * Prints every registered route with its protection: auth, permission,
 * module, rate limit — and whether CSRF applies (all state-changing routes
 * get the global CSRF check unless explicitly exempted).
 */
final class RoutesAuditCommand
{
    public const DESCRIPTION = 'Security audit table of every route (auth/permission/CSRF/rate limit)';

    public static function run(array $args): int
    {
        $router = Router::instance();
        $register = require BASE_PATH . '/config/routes.php';
        $register($router);

        $reflection = new \ReflectionProperty(Router::class, 'routes');
        $routes = $reflection->getValue($router);

        printf("%-7s %-46s %-6s %-9s %-26s %s\n", 'METHOD', 'PATH', 'CSRF', 'AUTH', 'PERMISSION/MODULE', 'RATE LIMIT');
        echo str_repeat('-', 118) . "\n";
        $unprotectedWrites = 0;
        foreach ($routes as $route) {
            $auth = 'guest';
            $permission = '';
            $rate = '';
            foreach ($route['middleware'] as $mw) {
                $param = null;
                if (str_contains($mw, ':')) {
                    [$mw, $param] = explode(':', $mw, 2);
                }
                $short = substr((string) strrchr($mw, '\\'), 1) ?: $mw;
                if ($short === 'AuthMiddleware') {
                    $auth = 'user';
                } elseif ($short === 'GuestMiddleware') {
                    $auth = 'guest*';
                } elseif ($short === 'PermissionMiddleware') {
                    $auth = 'user';
                    $permission = (string) $param;
                } elseif ($short === 'ModuleMiddleware') {
                    $permission = trim($permission . ' [mod:' . $param . ']');
                } elseif ($short === 'RateLimitMiddleware') {
                    $rate = (string) $param;
                }
            }
            $isWrite = in_array($route['method'], ['POST', 'PUT', 'DELETE', 'PATCH'], true);
            $csrf = $isWrite ? 'yes' : '—';
            if ($isWrite && $auth === 'guest' && !in_array($route['path'], ['/login', '/logout', '/password/forgot', '/password/reset'], true)) {
                $unprotectedWrites++;
            }
            printf(
                "%-7s %-46s %-6s %-9s %-26s %s\n",
                $route['method'],
                $route['path'],
                $csrf,
                $auth,
                $permission,
                $rate
            );
        }
        echo str_repeat('-', 118) . "\n";
        echo "guest* = guest-only. All POST/PUT/DELETE routes pass the global CSRF middleware.\n";
        echo $unprotectedWrites === 0
            ? "OK: no unexpected unauthenticated state-changing routes.\n"
            : "WARNING: {$unprotectedWrites} state-changing route(s) without auth!\n";
        return $unprotectedWrites === 0 ? 0 : 1;
    }
}
