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
