<?php
use App\Core\Auth;
use App\Core\Router;

$adminNav = [
    ['label' => 'Dashboard', 'icon' => 'dashboard', 'url' => base_url('admin'), 'permission' => null],
    ['label' => 'Users', 'icon' => 'users', 'url' => base_url('admin/users'), 'permission' => 'users.manage'],
    ['label' => 'Roles', 'icon' => 'key', 'url' => base_url('admin/roles'), 'permission' => 'roles.manage'],
    ['label' => 'Menus', 'icon' => 'menu-2', 'url' => base_url('admin/menus'), 'permission' => 'menus.manage'],
    ['label' => 'Quick Links', 'icon' => 'bolt', 'url' => base_url('admin/quick-links'), 'permission' => 'links.manage'],
    ['label' => 'News', 'icon' => 'news', 'url' => base_url('admin/news'), 'permission' => 'news.create'],
    ['label' => 'Events', 'icon' => 'calendar', 'url' => base_url('admin/events'), 'permission' => 'events.manage'],
    ['label' => 'Polls', 'icon' => 'chart-line', 'url' => base_url('admin/polls'), 'permission' => 'polls.manage'],
    ['label' => 'Documents', 'icon' => 'files', 'url' => base_url('admin/documents'), 'permission' => 'docs.upload'],
    ['label' => 'Themes', 'icon' => 'palette', 'url' => base_url('admin/themes'), 'permission' => 'themes.manage'],
    ['label' => 'SSO', 'icon' => 'shield', 'url' => base_url('admin/sso'), 'permission' => 'sso.manage'],
    ['label' => 'Settings', 'icon' => 'settings', 'url' => base_url('admin/settings'), 'permission' => 'settings.manage'],
    ['label' => 'Audit Log', 'icon' => 'eye', 'url' => base_url('admin/audit'), 'permission' => 'audit.view'],
];
$currentPath = Router::instance()->currentPath();
$base = rtrim((string) parse_url((string) \App\Core\Config::env('APP_URL', ''), PHP_URL_PATH), '/');
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav" aria-label="Admin navigation">
        <ul class="sidebar-menu">
            <li>
                <a class="sidebar-link" href="<?= e(url('home')) ?>">
                    <?= icon('home') ?><span class="sidebar-label">Back to portal</span>
                </a>
            </li>
            <li class="sidebar-heading">Administration</li>
            <?php foreach ($adminNav as $item): ?>
                <?php if ($item['permission'] !== null && !Auth::can($item['permission'])) continue; ?>
                <?php
                $itemPath = (string) parse_url($item['url'], PHP_URL_PATH);
                if ($base !== '' && str_starts_with($itemPath, $base)) {
                    $itemPath = substr($itemPath, strlen($base));
                }
                $itemPath = rtrim('/' . ltrim($itemPath, '/'), '/');
                $active = $itemPath === '/admin'
                    ? $currentPath === '/admin'
                    : ($currentPath === $itemPath || str_starts_with($currentPath, $itemPath . '/'));
                ?>
            <li class="<?= $active ? 'active' : '' ?>">
                <a class="sidebar-link" href="<?= e((string) $item['url']) ?>">
                    <?= icon((string) $item['icon']) ?><span class="sidebar-label"><?= e((string) $item['label']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
