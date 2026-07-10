<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Router;

final class MenuItem
{
    /**
     * Menu tree for a location, filtered by the current user's roles,
     * with resolved URLs and active-state detection.
     *
     * @return array<int, array> root items, each with a 'children' array
     */
    public static function tree(string $location): array
    {
        $rows = DB::fetchAll(
            'SELECT * FROM menu_items WHERE location = ? AND enabled = 1 ORDER BY sort_order, id',
            [$location]
        );
        $currentPath = Router::instance()->currentPath();
        $isSuperAdmin = Auth::hasRole('super_admin');
        $userRoles = Auth::roles();

        $items = [];
        foreach ($rows as $row) {
            if (!self::visible($row, $userRoles, $isSuperAdmin)) {
                continue;
            }
            $row['resolved_url'] = self::resolveUrl($row);
            // Hide links into disabled modules
            $module = \App\Core\Modules::forPath(self::appPath($row['resolved_url']));
            if ($module !== null && !\App\Core\Modules::enabled($module)) {
                continue;
            }
            $row['is_active'] = self::isActive($row, $currentPath);
            $row['children'] = [];
            $items[(int) $row['id']] = $row;
        }
        $tree = [];
        foreach ($items as $id => &$item) {
            $parentId = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
            if ($parentId !== null && isset($items[$parentId])) {
                $items[$parentId]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        unset($item);
        // bubble child activity up to the parent
        foreach ($tree as &$root) {
            foreach ($root['children'] as $child) {
                if ($child['is_active']) {
                    $root['is_active'] = true;
                }
            }
        }
        unset($root);
        return $tree;
    }

    /**
     * All items for the admin manager (unfiltered), grouped as a tree.
     */
    public static function adminTree(string $location): array
    {
        $rows = DB::fetchAll(
            'SELECT * FROM menu_items WHERE location = ? ORDER BY sort_order, id',
            [$location]
        );
        $items = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $items[(int) $row['id']] = $row;
        }
        $tree = [];
        foreach ($items as $id => &$item) {
            $parentId = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
            if ($parentId !== null && isset($items[$parentId])) {
                $items[$parentId]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        unset($item);
        return $tree;
    }

    private static function visible(array $row, array $userRoles, bool $isSuperAdmin): bool
    {
        if ($row['visible_to'] === null || $row['visible_to'] === '') {
            return true;
        }
        if ($isSuperAdmin) {
            return true;
        }
        $allowed = json_decode((string) $row['visible_to'], true);
        if (!is_array($allowed) || $allowed === []) {
            return true;
        }
        return array_intersect($allowed, $userRoles) !== [];
    }

    private static function resolveUrl(array $row): string
    {
        $routeName = (string) ($row['route_name'] ?? '');
        if ($routeName !== '' && Router::instance()->hasRoute($routeName)) {
            return url($routeName);
        }
        $url = (string) ($row['url'] ?? '');
        if ($url === '') {
            return '#';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return base_url($url);
    }

    /**
     * App-relative path of a resolved menu URL ('/news', '/documents', ...).
     */
    private static function appPath(string $url): string
    {
        if (str_starts_with($url, '#')) {
            return '#';
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        $base = rtrim((string) parse_url((string) \App\Core\Config::env('APP_URL', ''), PHP_URL_PATH), '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . ltrim($path, '/');
        return $path !== '/' ? rtrim($path, '/') : '/';
    }

    private static function isActive(array $row, string $currentPath): bool
    {
        $path = self::appPath($row['resolved_url']);
        if ($path === '#') {
            return false;
        }
        if ($path === '/') {
            return $currentPath === '/';
        }
        return $currentPath === $path || str_starts_with($currentPath, $path . '/');
    }
}
