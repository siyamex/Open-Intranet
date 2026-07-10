<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\DB;
use App\Core\Router;
use App\Core\Validator;
use App\Core\View;
use App\Models\MenuItem;

final class MenuController
{
    private const LOCATIONS = ['sidebar', 'navbar', 'footer'];

    public function index(): void
    {
        $location = in_array($_GET['location'] ?? '', self::LOCATIONS, true) ? (string) $_GET['location'] : 'sidebar';
        View::render('admin/menus/index', [
            'title' => 'Menus',
            'location' => $location,
            'locations' => self::LOCATIONS,
            'tree' => MenuItem::adminTree($location),
            'roles' => DB::fetchAll('SELECT slug, name FROM roles ORDER BY name'),
            'routeOptions' => $this->routeOptions(),
            'icons' => $this->iconList(),
        ], 'admin');
    }

    public function store(): void
    {
        $data = $this->validated();
        if ($data === null) {
            redirect('admin/menus?location=' . ($_POST['location'] ?? 'sidebar'));
        }
        $data['sort_order'] = (int) (DB::scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM menu_items WHERE location = ?',
            [$data['location']]
        ) ?? 10);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::insert('menu_items', $data);
        Audit::log('menu.created', 'menu_item', $id, ['label' => $data['label']]);
        flash('success', 'Menu item added.');
        redirect('admin/menus?location=' . $data['location']);
    }

    public function update(string $id): void
    {
        $item = DB::fetch('SELECT * FROM menu_items WHERE id = ?', [(int) $id]);
        if ($item === null) {
            flash('error', 'Menu item not found.');
            redirect('admin/menus');
        }
        $data = $this->validated((int) $id);
        if ($data === null) {
            redirect('admin/menus?location=' . $item['location']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::update('menu_items', $data, 'id = ?', [(int) $id]);
        Audit::log('menu.updated', 'menu_item', (int) $id, ['label' => $data['label']]);
        flash('success', 'Menu item updated.');
        redirect('admin/menus?location=' . $data['location']);
    }

    public function destroy(string $id): void
    {
        $item = DB::fetch('SELECT * FROM menu_items WHERE id = ?', [(int) $id]);
        if ($item !== null) {
            DB::delete('menu_items', 'id = ?', [(int) $id]); // children cascade
            Audit::log('menu.deleted', 'menu_item', (int) $id, ['label' => $item['label']]);
            flash('success', 'Menu item deleted.');
        }
        redirect('admin/menus?location=' . ($item['location'] ?? 'sidebar'));
    }

    /**
     * Accepts JSON: {"items": [{"id": 1, "parent_id": null, "sort": 10}, ...]}
     */
    public function reorder(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $existingIds = array_map('intval', array_column(DB::fetchAll('SELECT id FROM menu_items'), 'id'));
        foreach ($items as $entry) {
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $parentId = isset($entry['parent_id']) && $entry['parent_id'] !== null ? (int) $entry['parent_id'] : null;
            if ($parentId !== null && (!in_array($parentId, $existingIds, true) || $parentId === $id)) {
                $parentId = null;
            }
            DB::update('menu_items', [
                'parent_id' => $parentId,
                'sort_order' => (int) ($entry['sort'] ?? 0),
            ], 'id = ?', [$id]);
        }
        Audit::log('menu.reordered', 'menu_item', null);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validated(?int $ignoreId = null): ?array
    {
        $v = new Validator($_POST, [
            'label' => 'required|max:100',
            'location' => 'required|in:' . implode(',', self::LOCATIONS),
            'target' => 'in:_self,_blank',
        ]);
        if ($v->fails()) {
            flash('error', (string) $v->firstError());
            return null;
        }
        $routeName = trim((string) ($_POST['route_name'] ?? ''));
        $urlValue = trim((string) ($_POST['url'] ?? ''));
        if ($routeName === '' && $urlValue === '') {
            flash('error', 'Provide a URL or pick an internal page.');
            return null;
        }
        $visibleTo = array_values(array_filter((array) ($_POST['visible_to'] ?? []), 'is_string'));
        $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        if ($parentId !== null && $ignoreId !== null && $parentId === $ignoreId) {
            $parentId = null;
        }
        return [
            'location' => (string) $_POST['location'],
            'label' => trim((string) $_POST['label']),
            'icon' => trim((string) ($_POST['icon'] ?? '')) ?: null,
            'url' => $urlValue !== '' ? $urlValue : null,
            'route_name' => $routeName !== '' ? $routeName : null,
            'parent_id' => $parentId,
            'target' => (string) ($_POST['target'] ?? '_self'),
            'visible_to' => $visibleTo === [] ? null : json_encode($visibleTo),
            'enabled' => !empty($_POST['enabled']) ? 1 : 0,
        ];
    }

    /**
     * Named GET routes an admin may link to.
     *
     * @return array<string, string> name => label
     */
    private function routeOptions(): array
    {
        $friendly = [
            'home' => 'Home / Dashboard',
            'profile' => 'My profile',
            'profile.security' => 'Security settings',
            'search' => 'Search',
        ];
        $options = [];
        foreach ($friendly as $name => $label) {
            if (Router::instance()->hasRoute($name)) {
                $options[$name] = $label;
            }
        }
        return $options;
    }

    /**
     * @return string[]
     */
    private function iconList(): array
    {
        $files = glob(BASE_PATH . '/public/assets/icons/*.svg') ?: [];
        return array_map(static fn (string $f): string => basename($f, '.svg'), $files);
    }
}
