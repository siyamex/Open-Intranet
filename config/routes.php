<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PasswordController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

return static function (Router $r): void {
    // Guest-only (login + password reset)
    $r->group(['middleware' => [GuestMiddleware::class]], static function (Router $r): void {
        $r->get('/login', [AuthController::class, 'showLogin'], 'login');
        $r->post('/login', [AuthController::class, 'login'], 'login.post');
        $r->get('/password/forgot', [PasswordController::class, 'forgotForm'], 'password.forgot');
        $r->post('/password/forgot', [PasswordController::class, 'sendReset'], 'password.email');
        $r->get('/password/reset/{token}', [PasswordController::class, 'resetForm'], 'password.reset');
        $r->post('/password/reset', [PasswordController::class, 'doReset'], 'password.update');
    });

    $r->post('/logout', [AuthController::class, 'logout'], 'logout');

    // Authenticated app
    $r->group(['middleware' => [AuthMiddleware::class]], static function (Router $r): void {
        $r->get('/', [HomeController::class, 'index'], 'home');
        $r->get('/password/change', [PasswordController::class, 'changeForm'], 'password.change');
        $r->post('/password/change', [PasswordController::class, 'change'], 'password.change.post');
    });
};
