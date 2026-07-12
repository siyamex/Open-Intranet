<?php

declare(strict_types=1);

use App\Controllers\Admin\AuditController as AdminAuditController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Controllers\Admin\EventController as AdminEventController;
use App\Controllers\Admin\KudosController as AdminKudosController;
use App\Controllers\Admin\PollController as AdminPollController;
use App\Controllers\EventController;
use App\Controllers\KudosController;
use App\Controllers\PollController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Admin\ThemeController as AdminThemeController;
use App\Middleware\ModuleMiddleware;
use App\Controllers\Admin\NewsController as AdminNewsController;
use App\Controllers\Admin\OrgQualityController;
use App\Controllers\OrgChartController;
use App\Controllers\DirectoryController;
use App\Controllers\DocumentController;
use App\Controllers\FileController;
use App\Controllers\Admin\QuickLinkController as AdminQuickLinkController;
use App\Controllers\NewsController;
use App\Controllers\Admin\RoleController;
use App\Controllers\QuickLinkController;
use App\Controllers\Admin\SsoProviderController;
use App\Controllers\NotificationController;
use App\Controllers\SearchController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\UserImportController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\MediaController;
use App\Controllers\PasswordController;
use App\Controllers\PeopleController;
use App\Controllers\ProfileController;
use App\Controllers\ProfileSecurityController;
use App\Controllers\SsoController;
use App\Controllers\ThemeCssController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RateLimitMiddleware;

return static function (Router $r): void {
    // Guest-only (login + password reset)
    $r->group(['middleware' => [GuestMiddleware::class]], static function (Router $r): void {
        $r->get('/login', [AuthController::class, 'showLogin'], 'login');
        $r->group(['middleware' => [RateLimitMiddleware::class . ':login,10,300']], static function (Router $r): void {
            $r->post('/login', [AuthController::class, 'login'], 'login.post');
        });
        $r->get('/password/forgot', [PasswordController::class, 'forgotForm'], 'password.forgot');
        $r->group(['middleware' => [RateLimitMiddleware::class . ':pwreset,5,900']], static function (Router $r): void {
            $r->post('/password/forgot', [PasswordController::class, 'sendReset'], 'password.email');
            $r->post('/password/reset', [PasswordController::class, 'doReset'], 'password.update');
        });
        $r->get('/password/reset/{token}', [PasswordController::class, 'resetForm'], 'password.reset');
    });

    $r->post('/logout', [AuthController::class, 'logout'], 'logout');

    // Personal calendar feed for external calendar apps (signed token, no session)
    $r->get('/calendar/feed/{userId}/{token}', [EventController::class, 'feed'], 'events.feed');

    // First-run installer (404s itself once storage/installed.lock exists)
    $r->get('/install', [InstallController::class, 'step'], 'install');
    $r->post('/install', [InstallController::class, 'step'], 'install.post');

    // Compiled theme stylesheet + uploaded theme assets (public — login page)
    $r->get('/theme.css', [ThemeCssController::class, 'css'], 'theme.css');
    $r->get('/theme-assets/{slug}/{file}', [MediaController::class, 'themeAsset'], 'theme.asset');
    $r->get('/theme-assets/{slug}/{sub}/{file}', [MediaController::class, 'themeAssetSub'], 'theme.asset.sub');

    // SSO flow (works for guests and for logged-in users linking accounts)
    $r->group(['middleware' => [RateLimitMiddleware::class . ':sso,15,300']], static function (Router $r): void {
        $r->get('/auth/{slug}/redirect', [SsoController::class, 'redirect'], 'sso.redirect');
        $r->get('/auth/{slug}/callback', [SsoController::class, 'callback'], 'sso.callback');
    });

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
        $r->group(['middleware' => [RateLimitMiddleware::class . ':search,40,60']], static function (Router $r): void {
            $r->get('/search', [SearchController::class, 'index'], 'search');
        });
        $r->post('/prefs/theme-mode', [ProfileController::class, 'saveThemeMode'], 'prefs.theme-mode');
        $r->get('/theme-preview', [AdminThemeController::class, 'preview'], 'theme.preview');
        $r->get('/notifications/recent', [NotificationController::class, 'recent'], 'notifications.recent');
        $r->post('/notifications/read', [NotificationController::class, 'markRead'], 'notifications.read');
        $r->get('/qlicons/{file}', [MediaController::class, 'quickLinkIcon'], 'qlicon');
        $r->post('/quick-links/{id}/click', [QuickLinkController::class, 'click'], 'quick-links.click');
        $r->post('/quick-links/{id}/pin', [QuickLinkController::class, 'pin'], 'quick-links.pin');
        $r->post('/quick-links/order', [QuickLinkController::class, 'order'], 'quick-links.order');
        $r->group(['middleware' => [ModuleMiddleware::class . ':news']], static function (Router $r): void {
            $r->get('/news', [NewsController::class, 'index'], 'news.index');
            $r->get('/news/{slug}', [NewsController::class, 'show'], 'news.show');
            $r->post('/news/{slug}/comments', [NewsController::class, 'comment'], 'news.comment');
            $r->post('/news/{slug}/react', [NewsController::class, 'react'], 'news.react');
            $r->get('/news-media/{file}', [MediaController::class, 'newsMedia'], 'news.media');
        });
        $r->group(['middleware' => [ModuleMiddleware::class . ':documents']], static function (Router $r): void {
            $r->get('/documents', [DocumentController::class, 'index'], 'documents.index');
            $r->get('/files/{uuid}', [FileController::class, 'serve'], 'files.serve');
        });
        $r->group(['middleware' => [ModuleMiddleware::class . ':directory']], static function (Router $r): void {
            $r->get('/directory', [DirectoryController::class, 'index'], 'directory.index');
            $r->get('/api/directory', [DirectoryController::class, 'api'], 'directory.api');
            $r->get('/directory/department/{id}', [DirectoryController::class, 'department'], 'directory.department');
            $r->get('/people/{id}/vcard', [DirectoryController::class, 'vcard'], 'people.vcard');
        });
        $r->group(['middleware' => [ModuleMiddleware::class . ':org_chart']], static function (Router $r): void {
            $r->get('/org-chart', [OrgChartController::class, 'index'], 'orgchart.index');
            $r->get('/api/org-chart', [OrgChartController::class, 'api'], 'orgchart.api');
        });
        $r->group(['middleware' => [ModuleMiddleware::class . ':polls']], static function (Router $r): void {
            $r->post('/polls/{id}/vote', [PollController::class, 'vote'], 'polls.vote');
        });
        $r->group(['middleware' => [ModuleMiddleware::class . ':kudos']], static function (Router $r): void {
            $r->get('/kudos', [KudosController::class, 'index'], 'kudos.index');
            $r->post('/kudos', [KudosController::class, 'store'], 'kudos.store');
            $r->post('/kudos/{id}/react', [KudosController::class, 'react'], 'kudos.react');
        });
        $r->group(['middleware' => [ModuleMiddleware::class . ':events']], static function (Router $r): void {
            $r->get('/events', [EventController::class, 'index'], 'events.index');
            $r->get('/api/events', [EventController::class, 'api'], 'events.api');
            $r->get('/events/{id}', [EventController::class, 'show'], 'events.show');
            $r->post('/events/{id}/rsvp', [EventController::class, 'rsvp'], 'events.rsvp');
            $r->get('/events/{id}/ics', [EventController::class, 'ics'], 'events.ics');
        });
        $r->post('/profile/skills', [ProfileController::class, 'addSkill'], 'profile.skills.add');
        $r->post('/profile/skills/remove', [ProfileController::class, 'removeSkill'], 'profile.skills.remove');
    });

    // Admin
    $r->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], static function (Router $r): void {
        $r->get('/', [AdminDashboardController::class, 'index'], 'admin.dashboard');

        $r->group(['middleware' => [PermissionMiddleware::class . ':settings.manage']], static function (Router $r): void {
            $r->get('/settings', [AdminSettingsController::class, 'index'], 'admin.settings');
            $r->post('/settings', [AdminSettingsController::class, 'save'], 'admin.settings.save');
            $r->post('/settings/test-mail', [AdminSettingsController::class, 'testMail'], 'admin.settings.test-mail');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':audit.view']], static function (Router $r): void {
            $r->get('/audit', [AdminAuditController::class, 'index'], 'admin.audit');
        });

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
            $r->get('/org-quality', [OrgQualityController::class, 'index'], 'admin.org-quality');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':roles.manage']], static function (Router $r): void {
            $r->get('/roles', [RoleController::class, 'index'], 'admin.roles');
            $r->post('/roles', [RoleController::class, 'store'], 'admin.roles.store');
            $r->post('/roles/matrix', [RoleController::class, 'saveMatrix'], 'admin.roles.matrix');
            $r->delete('/roles/{id}', [RoleController::class, 'destroy'], 'admin.roles.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':news.create']], static function (Router $r): void {
            $r->get('/news', [AdminNewsController::class, 'index'], 'admin.news');
            $r->get('/news/create', [AdminNewsController::class, 'create'], 'admin.news.create');
            $r->post('/news', [AdminNewsController::class, 'store'], 'admin.news.store');
            $r->group(['middleware' => [RateLimitMiddleware::class . ':upload,30,3600']], static function (Router $r): void {
                $r->post('/news/upload-image', [AdminNewsController::class, 'uploadImage'], 'admin.news.upload-image');
            });
            $r->post('/news/categories', [AdminNewsController::class, 'storeCategory'], 'admin.news.category.store');
            $r->get('/news/{id}/edit', [AdminNewsController::class, 'edit'], 'admin.news.edit');
            $r->put('/news/{id}', [AdminNewsController::class, 'update'], 'admin.news.update');
            $r->delete('/news/{id}', [AdminNewsController::class, 'destroy'], 'admin.news.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':news.publish']], static function (Router $r): void {
            $r->post('/news/{id}/archive', [AdminNewsController::class, 'archive'], 'admin.news.archive');
            $r->post('/news/{id}/pin', [AdminNewsController::class, 'pin'], 'admin.news.pin');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':docs.upload']], static function (Router $r): void {
            $r->get('/documents', [AdminDocumentController::class, 'index'], 'admin.documents');
            $r->group(['middleware' => [RateLimitMiddleware::class . ':upload,30,3600']], static function (Router $r): void {
                $r->post('/documents', [AdminDocumentController::class, 'store'], 'admin.documents.store');
            });
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':docs.manage']], static function (Router $r): void {
            $r->post('/documents/bulk', [AdminDocumentController::class, 'bulk'], 'admin.documents.bulk');
            $r->put('/documents/{id}', [AdminDocumentController::class, 'update'], 'admin.documents.update');
            $r->delete('/documents/{id}', [AdminDocumentController::class, 'destroy'], 'admin.documents.destroy');
            $r->get('/documents/{id}/versions', [AdminDocumentController::class, 'versions'], 'admin.documents.versions');
            $r->post('/documents/{id}/version', [AdminDocumentController::class, 'newVersion'], 'admin.documents.version');
            $r->post('/documents/versions/{id}/restore', [AdminDocumentController::class, 'restore'], 'admin.documents.restore');
            $r->post('/doc-categories', [AdminDocumentController::class, 'storeCategory'], 'admin.doc-categories.store');
            $r->delete('/doc-categories/{id}', [AdminDocumentController::class, 'destroyCategory'], 'admin.doc-categories.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':kudos.moderate']], static function (Router $r): void {
            $r->get('/kudos', [AdminKudosController::class, 'index'], 'admin.kudos');
            $r->post('/kudos/settings', [AdminKudosController::class, 'saveSettings'], 'admin.kudos.settings');
            $r->post('/kudos/{id}/hide', [AdminKudosController::class, 'toggleHide'], 'admin.kudos.hide');
            $r->post('/kudos/values/{id}/toggle', [AdminKudosController::class, 'toggleValue'], 'admin.kudos.value.toggle');
            $r->delete('/kudos/{id}', [AdminKudosController::class, 'destroy'], 'admin.kudos.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':polls.manage']], static function (Router $r): void {
            $r->get('/polls', [AdminPollController::class, 'index'], 'admin.polls');
            $r->post('/polls', [AdminPollController::class, 'store'], 'admin.polls.store');
            $r->get('/polls/{id}/results', [AdminPollController::class, 'results'], 'admin.polls.results');
            $r->post('/polls/{id}/close', [AdminPollController::class, 'close'], 'admin.polls.close');
            $r->delete('/polls/{id}', [AdminPollController::class, 'destroy'], 'admin.polls.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':events.manage']], static function (Router $r): void {
            $r->get('/events', [AdminEventController::class, 'index'], 'admin.events');
            $r->post('/events', [AdminEventController::class, 'store'], 'admin.events.store');
            $r->put('/events/{id}', [AdminEventController::class, 'update'], 'admin.events.update');
            $r->delete('/events/{id}', [AdminEventController::class, 'destroy'], 'admin.events.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':links.manage']], static function (Router $r): void {
            $r->get('/quick-links', [AdminQuickLinkController::class, 'index'], 'admin.quick-links');
            $r->post('/quick-links', [AdminQuickLinkController::class, 'store'], 'admin.quick-links.store');
            $r->post('/quick-links/reorder', [AdminQuickLinkController::class, 'reorder'], 'admin.quick-links.reorder');
            $r->put('/quick-links/{id}', [AdminQuickLinkController::class, 'update'], 'admin.quick-links.update');
            $r->delete('/quick-links/{id}', [AdminQuickLinkController::class, 'destroy'], 'admin.quick-links.destroy');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':themes.manage']], static function (Router $r): void {
            $r->get('/themes', [AdminThemeController::class, 'index'], 'admin.themes');
            $r->post('/themes', [AdminThemeController::class, 'store'], 'admin.themes.store');
            $r->post('/themes/upload-login-bg', [AdminThemeController::class, 'uploadLoginBg'], 'admin.themes.upload-login-bg');
            $r->get('/themes/{id}/edit', [AdminThemeController::class, 'edit'], 'admin.themes.edit');
            $r->put('/themes/{id}', [AdminThemeController::class, 'update'], 'admin.themes.update');
            $r->post('/themes/{id}/activate', [AdminThemeController::class, 'activate'], 'admin.themes.activate');
            $r->delete('/themes/{id}', [AdminThemeController::class, 'destroy'], 'admin.themes.destroy');
            $r->get('/themes/{id}/export', [AdminThemeController::class, 'export'], 'admin.themes.export');
            $r->group(['middleware' => [RateLimitMiddleware::class . ':theme-install,10,3600']], static function (Router $r): void {
                $r->post('/themes/install', [AdminThemeController::class, 'install'], 'admin.themes.install');
            });
            $r->post('/themes/{id}/rollback', [AdminThemeController::class, 'rollback'], 'admin.themes.rollback');
        });

        $r->group(['middleware' => [PermissionMiddleware::class . ':menus.manage']], static function (Router $r): void {
            $r->get('/menus', [MenuController::class, 'index'], 'admin.menus');
            $r->post('/menus', [MenuController::class, 'store'], 'admin.menus.store');
            $r->post('/menus/reorder', [MenuController::class, 'reorder'], 'admin.menus.reorder');
            $r->put('/menus/{id}', [MenuController::class, 'update'], 'admin.menus.update');
            $r->delete('/menus/{id}', [MenuController::class, 'destroy'], 'admin.menus.destroy');
        });
    });
};
