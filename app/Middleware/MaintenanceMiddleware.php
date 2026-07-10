<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Router;
use App\Core\Settings;
use App\Core\View;

/**
 * Global maintenance-mode gate: admins (settings.manage) bypass; the login
 * flow stays reachable so admins can get in.
 */
final class MaintenanceMiddleware
{
    private const OPEN_PATHS = ['/login', '/logout', '/password/forgot', '/password/reset'];

    public function handle(?string $param = null): void
    {
        if (!(bool) Settings::get('maintenance_mode', false)) {
            return;
        }
        $path = Router::instance()->currentPath();
        foreach (self::OPEN_PATHS as $open) {
            if ($path === $open || str_starts_with($path, $open . '/')) {
                return;
            }
        }
        if (str_starts_with($path, '/auth/')) {
            return; // SSO callbacks
        }
        if (Auth::check() && Auth::can('settings.manage')) {
            return;
        }
        header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 503 Service Unavailable');
        header('Retry-After: 3600');
        View::render('errors/503', [
            'message' => (string) Settings::get('maintenance_message', 'We are doing some maintenance — back soon.'),
        ], null);
        exit;
    }
}
