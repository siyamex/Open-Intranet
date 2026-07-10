<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\View;

final class CsrfMiddleware
{
    public function handle(?string $param = null): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return;
        }
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (Csrf::verify(is_string($token) ? $token : null)) {
            return;
        }
        // Send an explicit status line — Apache turns a bare non-standard
        // http_response_code(419) into a 500.
        header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 419 Page Expired');
        View::render('errors/419', [], null);
        exit;
    }
}
