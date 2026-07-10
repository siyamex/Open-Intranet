<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Flash;
use App\Core\Router;

set_exception_handler(static function (Throwable $e): void {
    \App\Core\Logger::error($e->getMessage(), [
        'type' => get_class($e),
        'file' => $e->getFile() . ':' . $e->getLine(),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
    ]);
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (\App\Core\Config::env('APP_ENV', 'production') === 'local') {
        echo '<pre style="padding:1rem;">' . htmlspecialchars((string) $e, ENT_QUOTES) . '</pre>';
    } else {
        \App\Core\View::render('errors/500', [], null);
    }
    exit;
});

$sessionPath = BASE_PATH . '/storage/sessions';
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}
session_name('openintranet_session');
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
Flash::ageInput();

$router = Router::instance();
$registerRoutes = require BASE_PATH . '/config/routes.php';
$registerRoutes($router);

(new \App\Middleware\SecurityHeadersMiddleware())->handle();
(new \App\Middleware\MaintenanceMiddleware())->handle();

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
