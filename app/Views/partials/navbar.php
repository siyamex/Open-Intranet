<?php
use App\Core\Auth;
use App\Core\Notify;
use App\Core\Settings;

$siteName = (string) Settings::get('site_name', config('app.name'));
$logoPath = Settings::get('logo_path');
$authUser = Auth::user();
$unread = $authUser !== null ? Notify::unreadCount((int) $authUser['id']) : 0;
?>
<header class="navbar">
    <div class="navbar-left">
        <button type="button" class="icon-btn" id="sidebar-toggle" aria-label="Toggle navigation"><?= icon('menu-2') ?></button>
        <a class="brand" href="<?= e(url('home')) ?>">
            <?php if (is_string($logoPath) && $logoPath !== ''): ?>
                <img class="brand-logo" src="<?= e(base_url($logoPath)) ?>" alt="">
            <?php else: ?>
                <span class="brand-mark"><?= e(mb_substr($siteName, 0, 1)) ?></span>
            <?php endif; ?>
            <span class="brand-name"><?= e($siteName) ?></span>
        </a>
    </div>

    <form class="navbar-search" method="get" action="<?= e(url('search')) ?>" role="search">
        <?= icon('search', 'icon search-icon') ?>
        <input type="search" name="q" placeholder="Search news, documents, people…" value="<?= e((string) ($_GET['q'] ?? '')) ?>" autocomplete="off">
    </form>

    <div class="navbar-right">
        <?php if ($authUser !== null): ?>
        <div class="dropdown" id="notif-dropdown">
            <button type="button" class="icon-btn" id="notif-toggle" aria-label="Notifications"
                    data-url="<?= e(url('notifications.recent')) ?>" data-read-url="<?= e(url('notifications.read')) ?>" data-csrf="<?= e(csrf_token()) ?>">
                <?= icon('bell') ?>
                <span class="notif-badge <?= $unread === 0 ? 'hidden' : '' ?>" id="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
            </button>
            <div class="dropdown-menu dropdown-wide" hidden>
                <div class="dropdown-head">
                    <strong>Notifications</strong>
                    <button type="button" class="btn btn-sm btn-secondary" id="notif-mark-all">Mark all read</button>
                </div>
                <div id="notif-list"><p class="text-muted dropdown-empty">Loading…</p></div>
            </div>
        </div>

        <button type="button" class="icon-btn" id="dark-toggle" aria-label="Toggle dark mode">
            <span class="only-light"><?= icon('moon') ?></span>
            <span class="only-dark"><?= icon('sun') ?></span>
        </button>

        <div class="dropdown" id="user-dropdown">
            <button type="button" class="avatar-btn" aria-label="Account menu">
                <?php partial('partials/avatar', ['person' => $authUser, 'size' => 34]); ?>
            </button>
            <div class="dropdown-menu" hidden>
                <div class="dropdown-user">
                    <strong><?= e((string) $authUser['name']) ?></strong>
                    <span class="text-muted"><?= e((string) $authUser['email']) ?></span>
                </div>
                <a class="dropdown-item" href="<?= e(url('profile')) ?>"><?= icon('user') ?> My profile</a>
                <a class="dropdown-item" href="<?= e(url('profile.security')) ?>"><?= icon('shield') ?> Security</a>
                <?php if (Auth::can('users.manage') || Auth::can('settings.manage') || Auth::can('sso.manage') || Auth::can('news.publish') || Auth::can('docs.manage') || Auth::can('themes.manage') || Auth::can('links.manage') || Auth::can('menus.manage') || Auth::can('audit.view') || Auth::can('roles.manage')): ?>
                <a class="dropdown-item" href="<?= e(base_url('admin')) ?>"><?= icon('settings') ?> Admin panel</a>
                <?php endif; ?>
                <form method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="dropdown-item"><?= icon('logout') ?> Sign out</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</header>
