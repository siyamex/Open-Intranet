<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;
use App\Core\Settings;

final class SeedCommand
{
    public const DESCRIPTION = 'Seed roles, permissions, defaults and sample content (idempotent)';

    public static function run(array $args): int
    {
        $now = date('Y-m-d H:i:s');

        // ---- Permissions -------------------------------------------------
        $permissions = [
            ['users.manage', 'Manage users', 'Users'],
            ['roles.manage', 'Manage roles & permissions', 'Users'],
            ['news.create', 'Create news drafts', 'News'],
            ['news.publish', 'Publish news', 'News'],
            ['docs.upload', 'Upload documents', 'Documents'],
            ['docs.manage', 'Manage documents & categories', 'Documents'],
            ['themes.manage', 'Manage themes', 'Appearance'],
            ['settings.manage', 'Manage settings', 'System'],
            ['menus.manage', 'Manage menus', 'Appearance'],
            ['sso.manage', 'Manage SSO providers', 'System'],
            ['links.manage', 'Manage quick links', 'Content'],
            ['audit.view', 'View audit log', 'System'],
        ];
        foreach ($permissions as [$slug, $label, $group]) {
            DB::run(
                'INSERT INTO permissions (slug, label, group_name) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE label = VALUES(label), group_name = VALUES(group_name)',
                [$slug, $label, $group]
            );
        }
        echo "Permissions seeded (" . count($permissions) . ").\n";

        // ---- Roles + matrix ----------------------------------------------
        $roles = [
            ['super_admin', 'Super Admin', 'Full access to everything', 1],
            ['admin', 'Administrator', 'Manages users, content and settings', 1],
            ['editor', 'Editor', 'Creates and publishes content', 1],
            ['employee', 'Employee', 'Standard portal access', 1],
        ];
        foreach ($roles as [$slug, $name, $description, $isSystem]) {
            DB::run(
                'INSERT INTO roles (slug, name, description, is_system) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), is_system = VALUES(is_system)',
                [$slug, $name, $description, $isSystem]
            );
        }
        $roleIds = [];
        foreach (DB::fetchAll('SELECT id, slug FROM roles') as $row) {
            $roleIds[$row['slug']] = (int) $row['id'];
        }
        $permIds = [];
        foreach (DB::fetchAll('SELECT id, slug FROM permissions') as $row) {
            $permIds[$row['slug']] = (int) $row['id'];
        }
        $matrix = [
            'super_admin' => array_keys($permIds),
            'admin' => array_keys($permIds),
            'editor' => ['news.create', 'news.publish', 'docs.upload'],
            'employee' => [],
        ];
        foreach ($matrix as $roleSlug => $permSlugs) {
            foreach ($permSlugs as $permSlug) {
                DB::run(
                    'INSERT IGNORE INTO role_permission (role_id, permission_id) VALUES (?, ?)',
                    [$roleIds[$roleSlug], $permIds[$permSlug]]
                );
            }
        }
        echo "Roles + permission matrix seeded.\n";

        // ---- Departments ---------------------------------------------------
        $departmentTree = [
            'Executive' => [],
            'Human Resources' => [],
            'Finance' => [],
            'Information Technology' => ['Infrastructure', 'Software Development'],
            'Operations' => [],
        ];
        foreach ($departmentTree as $parent => $children) {
            $existing = DB::fetch('SELECT id FROM departments WHERE name = ? AND parent_id IS NULL', [$parent]);
            $parentId = $existing !== null
                ? (int) $existing['id']
                : DB::insert('departments', ['name' => $parent, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($children as $child) {
                $childRow = DB::fetch('SELECT id FROM departments WHERE name = ? AND parent_id = ?', [$child, $parentId]);
                if ($childRow === null) {
                    DB::insert('departments', ['name' => $child, 'parent_id' => $parentId, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        }
        echo "Departments seeded.\n";

        // ---- Default sidebar menu ------------------------------------------
        $menuItems = [
            ['Home', 'home', null, 'home', 10],
            ['Profile', 'user', '/profile', null, 20],
            ['Directory', 'users', '/directory', null, 30],
            ['Org Chart', 'hierarchy', '/org-chart', null, 40],
            ['News', 'news', '/news', null, 50],
            ['Documents', 'files', '/documents', null, 60],
            ['Events', 'calendar', '/events', null, 70],
            ['Wiki', 'book', '/wiki', null, 80],
        ];
        foreach ($menuItems as [$label, $icon, $url, $routeName, $sort]) {
            $exists = DB::fetch(
                'SELECT id FROM menu_items WHERE location = ? AND label = ? AND parent_id IS NULL',
                ['sidebar', $label]
            );
            if ($exists === null) {
                DB::insert('menu_items', [
                    'location' => 'sidebar',
                    'label' => $label,
                    'icon' => $icon,
                    'url' => $url,
                    'route_name' => $routeName,
                    'sort_order' => $sort,
                    'enabled' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        echo "Sidebar menu seeded.\n";

        // ---- Quick links -----------------------------------------------------
        $quickLinks = [
            ['Webmail', 'https://mail.example.com', 'Company email', 'mail', '#4f46e5', 10],
            ['HR Portal', 'https://hr.example.com', 'Leave, payslips & policies', 'users', '#0ea5e9', 20],
            ['Payroll', 'https://payroll.example.com', 'Salary & reimbursements', 'cash', '#16a34a', 30],
            ['Helpdesk', 'https://helpdesk.example.com', 'IT support tickets', 'lifebuoy', '#d97706', 40],
            ['Calendar', 'https://calendar.example.com', 'Shared company calendar', 'calendar', '#dc2626', 50],
            ['Company Wiki', 'https://wiki.example.com', 'Knowledge base', 'book', '#7c3aed', 60],
        ];
        foreach ($quickLinks as [$title, $url, $description, $icon, $color, $sort]) {
            $exists = DB::fetch('SELECT id FROM quick_links WHERE title = ?', [$title]);
            if ($exists === null) {
                DB::insert('quick_links', [
                    'title' => $title,
                    'url' => $url,
                    'description' => $description,
                    'icon_type' => 'library',
                    'icon_value' => $icon,
                    'bg_color' => $color,
                    'sort_order' => $sort,
                    'open_new_tab' => 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        echo "Quick links seeded.\n";

        // ---- News categories + sample posts ---------------------------------
        $categories = [
            ['Announcements', 'announcements', '#4f46e5'],
            ['Events', 'events', '#0ea5e9'],
        ];
        foreach ($categories as [$name, $slug, $color]) {
            DB::run(
                'INSERT INTO news_categories (name, slug, color) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color)',
                [$name, $slug, $color]
            );
        }

        $author = DB::fetch("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        if ($author === null) {
            $authorId = DB::insert('users', [
                'name' => 'OpenIntranet Bot',
                'email' => 'bot@openintranet.local',
                'password_hash' => null,
                'job_title' => 'System',
                'status' => 'active',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $authorId = (int) $author['id'];
        }
        $catIds = [];
        foreach (DB::fetchAll('SELECT id, slug FROM news_categories') as $row) {
            $catIds[$row['slug']] = (int) $row['id'];
        }
        $posts = [
            [
                'Welcome to OpenIntranet',
                'welcome-to-openintranet',
                'Your new company portal is live — here is what you can do with it.',
                '<p>Welcome aboard! This portal brings together company news, documents, the employee directory and your everyday tools in one place.</p><h2>Getting started</h2><ul><li>Update your profile and photo</li><li>Browse the document center</li><li>Check the latest announcements right here</li></ul>',
                'announcements',
                1,
            ],
            [
                'Quarterly all-hands meeting',
                'quarterly-all-hands-meeting',
                'Join us for the quarterly all-hands — agenda and logistics inside.',
                '<p>Our next all-hands takes place at the end of the month. We will cover business updates, team highlights and the roadmap ahead.</p><p>Submit your questions in advance to the leadership team.</p>',
                'events',
                0,
            ],
            [
                'New document center is open',
                'new-document-center-is-open',
                'Policies, forms and the official gazette are now available online.',
                '<p>The document center is now the single source of truth for company policies, HR forms and official gazette publications.</p><p>Documents are versioned, so you will always find the latest copy.</p>',
                'announcements',
                0,
            ],
        ];
        foreach ($posts as $i => [$title, $slug, $excerpt, $body, $catSlug, $pinned]) {
            $exists = DB::fetch('SELECT id FROM news WHERE slug = ?', [$slug]);
            if ($exists === null) {
                DB::insert('news', [
                    'title' => $title,
                    'slug' => $slug,
                    'excerpt' => $excerpt,
                    'body' => $body,
                    'category_id' => $catIds[$catSlug] ?? null,
                    'author_id' => $authorId,
                    'status' => 'published',
                    'is_pinned' => $pinned,
                    'allow_comments' => 1,
                    'published_at' => date('Y-m-d H:i:s', time() - (86400 * (3 - $i))),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        echo "News categories + sample posts seeded.\n";

        // ---- Default theme ---------------------------------------------------
        $auroraVariables = [
            'color-primary' => '#4f46e5',
            'color-primary-contrast' => '#ffffff',
            'color-accent' => '#0ea5e9',
            'color-bg' => '#f3f4f6',
            'color-surface' => '#ffffff',
            'color-surface-2' => '#f9fafb',
            'color-text' => '#111827',
            'color-text-muted' => '#6b7280',
            'color-border' => '#e5e7eb',
            'color-success' => '#16a34a',
            'color-warning' => '#d97706',
            'color-danger' => '#dc2626',
            'font-family' => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'font-size-base' => '16px',
            'radius-sm' => '6px',
            'radius-md' => '10px',
            'radius-lg' => '16px',
        ];
        $darkBase = [
            'color-bg' => '#0f172a',
            'color-surface' => '#1e293b',
            'color-surface-2' => '#273449',
            'color-text' => '#e2e8f0',
            'color-text-muted' => '#94a3b8',
            'color-border' => '#334155',
            'navbar-bg' => '#1e293b',
            'sidebar-bg' => '#1e293b',
        ];
        $builtinThemes = [
            ['Aurora', 'aurora', $auroraVariables, $darkBase, 1],
            ['Slate', 'slate', array_merge($auroraVariables, [
                'color-primary' => '#475569',
                'color-accent' => '#0284c7',
                'color-bg' => '#f8fafc',
                'radius-sm' => '4px', 'radius-md' => '6px', 'radius-lg' => '10px',
            ]), array_merge($darkBase, ['color-primary' => '#94a3b8']), 0],
            ['Forest', 'forest', array_merge($auroraVariables, [
                'color-primary' => '#15803d',
                'color-accent' => '#ca8a04',
                'color-bg' => '#f4f8f4',
                'color-surface-2' => '#f0f5f0',
            ]), array_merge($darkBase, [
                'color-bg' => '#0c1512',
                'color-surface' => '#16241e',
                'color-surface-2' => '#1d2f27',
                'color-border' => '#2d4438',
                'color-primary' => '#4ade80',
                'color-primary-contrast' => '#052e16',
                'navbar-bg' => '#16241e',
                'sidebar-bg' => '#16241e',
            ]), 0],
            ['Midnight', 'midnight', array_merge($auroraVariables, [
                'color-primary' => '#818cf8',
                'color-primary-contrast' => '#111827',
                'color-bg' => '#0b1220',
                'color-surface' => '#131c2e',
                'color-surface-2' => '#1b2740',
                'color-text' => '#dbe4f3',
                'color-text-muted' => '#8b9bb8',
                'color-border' => '#26334d',
                'navbar-bg' => '#131c2e',
                'sidebar-bg' => '#131c2e',
            ]), [
                'color-bg' => '#070c16',
                'color-surface' => '#0e1524',
                'color-surface-2' => '#151f33',
                'color-border' => '#1f2a41',
            ], 0],
        ];
        foreach ($builtinThemes as [$name, $slug, $vars, $darkVars, $isActive]) {
            $exists = DB::fetch('SELECT id, dark_variables FROM themes WHERE slug = ?', [$slug]);
            if ($exists === null) {
                DB::insert('themes', [
                    'name' => $name,
                    'slug' => $slug,
                    'source' => 'builtin',
                    'variables' => json_encode($vars, JSON_UNESCAPED_SLASHES),
                    'dark_variables' => json_encode($darkVars, JSON_UNESCAPED_SLASHES),
                    'supports_dark' => 1,
                    'is_active' => $isActive,
                    'author' => 'OpenIntranet',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ($exists['dark_variables'] === null) {
                DB::update('themes', [
                    'dark_variables' => json_encode($darkVars, JSON_UNESCAPED_SLASHES),
                    'supports_dark' => 1,
                ], 'id = ?', [(int) $exists['id']]);
            }
        }
        echo "Built-in themes seeded.\n";

        // ---- Settings defaults ----------------------------------------------
        $defaults = [
            ['site_name', 'OpenIntranet', 'string'],
            ['site_tagline', 'Your company portal', 'string'],
            ['logo_path', null, 'string'],
            ['favicon_path', null, 'string'],
            ['timezone', 'UTC', 'string'],
            ['date_format', 'j M Y', 'string'],
            ['homepage_sections', json_encode(['quick_links', 'news', 'events', 'gazette']), 'json'],
            ['news_dashboard_count', '6', 'int'],
            ['gazette_dashboard_count', '5', 'int'],
            ['allow_local_login', '1', 'bool'],
            ['session_lifetime_minutes', '120', 'int'],
            ['password_min_length', '10', 'int'],
            ['upload_max_mb', '20', 'int'],
            ['allowed_doc_types', json_encode(['pdf', 'docx', 'xlsx', 'pptx', 'png', 'jpg', 'zip']), 'json'],
            ['comments_enabled', '1', 'bool'],
            ['reactions_enabled', '1', 'bool'],
            ['maintenance_mode', '0', 'bool'],
            ['maintenance_message', 'We are doing some maintenance — back soon.', 'string'],
            ['audit_retention_days', '365', 'int'],
        ];
        foreach ($defaults as [$key, $value, $type]) {
            $exists = DB::fetch('SELECT `key` FROM settings WHERE `key` = ?', [$key]);
            if ($exists === null) {
                DB::run('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)', [$key, $value, $type]);
            }
        }
        Settings::forget();
        echo "Settings defaults seeded.\n";

        echo "Done.\n";
        return 0;
    }
}
