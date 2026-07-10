<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

final class AuthMiddleware
{
    public function handle(?string $param = null): void
    {
        if (!Auth::check()) {
            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                $intended = \App\Core\Router::instance()->currentPath();
                $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
                if ($query !== '') {
                    $intended .= '?' . $query;
                }
                // Only same-origin relative paths may be remembered.
                if (str_starts_with($intended, '/') && !str_starts_with($intended, '//')) {
                    $_SESSION['intended'] = $intended;
                }
            }
            redirect('login');
        }

        // Idle timeout from settings (session_lifetime_minutes)
        $lifetime = (int) \App\Core\Settings::get('session_lifetime_minutes', 120);
        $last = (int) ($_SESSION['last_activity'] ?? 0);
        if ($last > 0 && (time() - $last) > $lifetime * 60) {
            Auth::logout();
            session_start();
            flash('warning', 'You were signed out after a period of inactivity.');
            redirect('login');
        }
        $_SESSION['last_activity'] = time();

        $user = Auth::user();
        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
            if (!str_contains($path, '/password/change') && !str_contains($path, '/logout')) {
                flash('warning', 'You must set a new password before continuing.');
                redirect('password/change');
            }
        }
    }
}
