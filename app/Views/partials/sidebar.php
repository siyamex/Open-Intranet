<?php
use App\Core\Auth;
use App\Models\MenuItem;

$menuTree = MenuItem::tree('sidebar');
$authUser = Auth::user();
$navLabels = [
    'Home' => __('nav.home'), 'Profile' => __('nav.profile'), 'Directory' => __('nav.directory'),
    'Org Chart' => __('nav.org_chart'), 'News' => __('nav.news'), 'Documents' => __('nav.documents'),
    'Events' => __('nav.events'), 'Wiki' => __('nav.wiki'), 'Requests' => __('nav.requests'), 'Kudos' => __('nav.kudos'),
];
$navLabel = static fn (string $label): string => $navLabels[$label] ?? $label;
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav" aria-label="Main navigation">
        <ul class="sidebar-menu">
            <?php foreach ($menuTree as $item): ?>
            <li class="<?= $item['is_active'] ? 'active' : '' ?> <?= $item['children'] !== [] ? 'has-children' : '' ?>">
                <?php if ($item['children'] !== []): ?>
                    <button type="button" class="sidebar-link submenu-toggle <?= $item['is_active'] ? 'open' : '' ?>">
                        <?= icon((string) ($item['icon'] ?? 'link')) ?>
                        <span class="sidebar-label"><?= e($navLabel((string) $item['label'])) ?></span>
                        <?= icon('chevron-down', 'icon chevron') ?>
                    </button>
                    <ul class="sidebar-submenu" <?= $item['is_active'] ? '' : 'hidden' ?>>
                        <?php foreach ($item['children'] as $child): ?>
                        <li class="<?= $child['is_active'] ? 'active' : '' ?>">
                            <a class="sidebar-link" href="<?= e((string) $child['resolved_url']) ?>" target="<?= e((string) $child['target']) ?>">
                                <?= icon((string) ($child['icon'] ?? 'link')) ?>
                                <span class="sidebar-label"><?= e($navLabel((string) $child['label'])) ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <a class="sidebar-link" href="<?= e((string) $item['resolved_url']) ?>" target="<?= e((string) $item['target']) ?>">
                        <?= icon((string) ($item['icon'] ?? 'link')) ?>
                        <span class="sidebar-label"><?= e($navLabel((string) $item['label'])) ?></span>
                    </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <?php if ($authUser !== null): ?>
    <div class="sidebar-footer">
        <a class="sidebar-profile" href="<?= e(url('profile')) ?>">
            <?php partial('partials/avatar', ['person' => $authUser, 'size' => 36]); ?>
            <span class="sidebar-profile-text">
                <strong><?= e((string) $authUser['name']) ?></strong>
                <span class="text-muted"><?= e((string) ($authUser['job_title'] ?? '')) ?></span>
            </span>
        </a>
    </div>
    <?php endif; ?>
</aside>
<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
