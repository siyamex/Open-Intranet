<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\DB;
use App\Core\HtmlSanitizer;
use App\Core\Settings;
use App\Core\View;

final class WidgetController
{
    public function index(): void
    {
        View::render('admin/widgets/index', [
            'title' => 'Dashboard Widgets',
            'widgets' => DB::fetchAll('SELECT * FROM widgets ORDER BY type, name'),
            'allowPersonalization' => (bool) Settings::get('allow_widget_personalization', true),
        ], 'admin');
    }

    public function createCustom(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = ($_POST['widget_type'] ?? '') === 'rss' ? 'rss' : 'html';
        if ($name === '' || mb_strlen($name) > 100) {
            flash('error', 'Widget name is required.');
            redirect('admin/widgets');
        }
        $slug = 'custom-' . strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
        while (DB::fetch('SELECT slug FROM widgets WHERE slug = ?', [$slug]) !== null) {
            $slug .= '-2';
        }
        $config = $type === 'rss'
            ? ['url' => trim((string) ($_POST['rss_url'] ?? '')), 'limit' => max(1, min(15, (int) ($_POST['rss_limit'] ?? 5)))]
            : ['html' => HtmlSanitizer::sanitize((string) ($_POST['html'] ?? ''))];
        if ($type === 'rss' && !preg_match('#^https://#i', $config['url'])) {
            flash('error', 'RSS widgets require an https:// feed URL.');
            redirect('admin/widgets');
        }
        DB::insert('widgets', [
            'slug' => $slug,
            'name' => $name,
            'type' => $type,
            'config' => json_encode($config),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('widget.created', 'widget', null, ['slug' => $slug, 'type' => $type]);
        flash('success', 'Widget created — add it to a layout below.');
        redirect('admin/widgets');
    }

    public function toggle(string $slug): void
    {
        $widget = DB::fetch('SELECT * FROM widgets WHERE slug = ?', [$slug]);
        if ($widget !== null) {
            DB::update('widgets', ['is_active' => (int) $widget['is_active'] === 1 ? 0 : 1], 'slug = ?', [$slug]);
        }
        redirect('admin/widgets');
    }

    public function destroy(string $slug): void
    {
        $widget = DB::fetch('SELECT * FROM widgets WHERE slug = ? AND type != "builtin"', [$slug]);
        if ($widget !== null) {
            DB::delete('widgets', 'slug = ?', [$slug]);
            Audit::log('widget.deleted', 'widget', null, ['slug' => $slug]);
            flash('success', 'Widget deleted.');
        }
        redirect('admin/widgets');
    }

    public function saveSettings(): void
    {
        Settings::set('allow_widget_personalization', !empty($_POST['allow_widget_personalization']), 'bool');
        flash('success', 'Widget settings saved.');
        redirect('admin/widgets');
    }

    /**
     * Default-layout builder for a role (or the global fallback when
     * role_id is empty/"default").
     */
    public function layout(): void
    {
        $roleId = ($_GET['role'] ?? 'default') === 'default' ? null : (int) $_GET['role'];
        $current = $roleId === null
            ? DB::fetchAll('SELECT rl.*, w.name FROM role_layouts rl JOIN widgets w ON w.slug = rl.widget_slug WHERE rl.role_id IS NULL ORDER BY rl.sort_order')
            : DB::fetchAll('SELECT rl.*, w.name FROM role_layouts rl JOIN widgets w ON w.slug = rl.widget_slug WHERE rl.role_id = ? ORDER BY rl.sort_order', [$roleId]);
        View::render('admin/widgets/layout', [
            'title' => 'Default dashboard layout',
            'roleId' => $roleId,
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
            'widgets' => DB::fetchAll('SELECT slug, name FROM widgets WHERE is_active = 1 ORDER BY name'),
            'current' => $current,
        ], 'admin');
    }

    public function saveLayout(): void
    {
        $roleId = empty($_POST['role_id']) ? null : (int) $_POST['role_id'];
        $items = json_decode((string) ($_POST['items'] ?? '[]'), true);
        $items = is_array($items) ? $items : [];
        if ($roleId === null) {
            DB::delete('role_layouts', 'role_id IS NULL');
        } else {
            DB::delete('role_layouts', 'role_id = ?', [$roleId]);
        }
        $sort = 10;
        foreach ($items as $item) {
            $slug = (string) ($item['slug'] ?? '');
            if (DB::fetch('SELECT slug FROM widgets WHERE slug = ?', [$slug]) === null) {
                continue;
            }
            $width = ($item['width'] ?? 'full') === 'half' ? 'half' : 'full';
            DB::insert('role_layouts', ['role_id' => $roleId, 'widget_slug' => $slug, 'sort_order' => $sort, 'width' => $width]);
            $sort += 10;
        }
        Audit::log('widget.layout_saved', 'role_layout', $roleId);
        flash('success', 'Default layout saved.');
        redirect('admin/widgets/layout?role=' . ($roleId ?? 'default'));
    }
}
