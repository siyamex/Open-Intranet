<?php

declare(strict_types=1);

use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SsoProviderController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\UserImportController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\MediaController;
use App\Controllers\PasswordController;
use App\Controllers\PeopleController;
use App\Controllers\ProfileController;
use App\Controllers\ProfileSecurityController;
use App\Controllers\SsoController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;

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

    // SSO flow (works for guests and for logged-in users linking accounts)
    $r->get('/auth/{slug}/redirect', [SsoController::class, 'redirect'], 'sso.redirect');
    $r->get('/auth/{slug}/callback', [SsoController::class, 'callback'], 'sso.callback');

    // Authenticated app
    $r->group(['middleware' => [AuthMiddleware::class]], static function (Router $r): void {
        $r->get('/', [HomeController::class, 'index'], 'home');
        $r->get('/password/change', [PasswordController::class, 'changeForm'], 'password.change');
        $r->post('/password/change', [PasswordController::class, 'change'], 'password.change.post');
        $r->get('/profile/security', [ProfileSecurityController::class, 'index'], 'profile.security');
        $r->post('/profile/security/unlink/{id}', [ProfileSecurityController::class, 'unlink'], 'profile.security.unlink');
        $r->get('/avatars/{file}', [MediaController::class, 'avatar'], 'avatar');
        $r->get('/profile', [ProfileController::class, 'edit'], 'profile');
        $r->post('/profile', [ProfileController::class, 'update'], 'profile.update');
        $r->get('/people/{id}', [PeopleController::class, 'show'], 'people.show');
        $r->post('/impersonate/stop', [UserController::class, 'stopImpersonate'], 'impersonate.stop');
    });

    // Admin
    $r->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], static function (Router $r): void {
        $r->group(['middleware' => [PermissionMiddleware::class . ':sso.manage']], static function (Router $r): void {
            $r->get('/sso', [SsoProviderController::class, 'index'], 'admin.sso');
            $r->get('/sso/create', [SsoProviderController::class, 'create'], 'admin.sso.create');
            $r->post('/sso', [SsoProviderController::class, 'store'], 'admin.sso.store');
            $r->post('/sso/order', [SsoProviderController::class, 'order'], 'admin.sso.order');
            $r->post('/sso/settings', [SsoProviderController::class, 'saveSettings'], 'admin.sso.settings');
            $r->get('/sso/{id}/edit', [SsoProviderController::class, 'edit'], 'admin.sso.edit');
            $r->put('/sso/{id}', [SsoProviderController::class, 'update'], 'admin.sso.update');
            $r->delete('/sso/{id}', [SsoProviderController::class, 'destroy'], 'admin.sso.destroy');
            $r->post('/sso/{id}/toggle', [SsoProviderController::class, 'toggle'], 'admin.sso.toggle');
            $r->get('/sso/{id}/test', [SsoProviderController::class, 'test'], 'admin.sso.test');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':users.manage']], static function (Router $r): void {
            $r->get('/users', [UserController::class, 'index'], 'admin.users');
            $r->get('/users/create', [UserController::class, 'create'], 'admin.users.create');
            $r->post('/users', [UserController::class, 'store'], 'admin.users.store');
            $r->get('/users/import', [UserImportController::class, 'form'], 'admin.users.import');
            $r->get('/users/import/template', [UserImportController::class, 'template'], 'admin.users.import.template');
            $r->post('/users/import/preview', [UserImportController::class, 'preview'], 'admin.users.import.preview');
            $r->post('/users/import/commit', [UserImportController::class, 'commit'], 'admin.users.import.commit');
            $r->get('/users/{id}/edit', [UserController::class, 'edit'], 'admin.users.edit');
            $r->put('/users/{id}', [UserController::class, 'update'], 'admin.users.update');
            $r->delete('/users/{id}', [UserController::class, 'destroy'], 'admin.users.destroy');
            $r->post('/users/{id}/toggle', [UserController::class, 'toggleStatus'], 'admin.users.toggle');
            $r->post('/users/{id}/force-reset', [UserController::class, 'forceReset'], 'admin.users.force-reset');
            $r->post('/users/{id}/impersonate', [UserController::class, 'impersonate'], 'admin.users.impersonate');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':roles.manage']], static function (Router $r): void {
            $r->get('/roles', [RoleController::class, 'index'], 'admin.roles');
            $r->post('/roles', [RoleController::class, 'store'], 'admin.roles.store');
            $r->post('/roles/matrix', [RoleController::class, 'saveMatrix'], 'admin.roles.matrix');
            $r->delete('/roles/{id}', [RoleController::class, 'destroy'], 'admin.roles.destroy');
        });
    });
};
