<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Settings;
use App\Core\WidgetService;

final class WidgetController
{
    /**
     * Lazy-load fragment for one dashboard widget.
     */
    public function show(string $slug): void
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            http_response_code(404);
            exit;
        }
        $html = WidgetService::render($slug, (int) Auth::id());
        header('Content-Type: text/html; charset=UTF-8');
        echo $html ?? '';
        exit;
    }

    /**
     * Catalog of active widgets for the "+ Add widget" picker.
     */
    public function catalog(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['widgets' => DB::fetchAll(
            "SELECT slug, name FROM widgets WHERE is_active = 1 ORDER BY name"
        )]);
        exit;
    }

    /**
     * Personal layout: add/remove/reorder, persisted per user.
     */
    public function saveLayout(): void
    {
        if (!(bool) Settings::get('allow_widget_personalization', true)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Personalization is disabled by your administrator.']);
            exit;
        }
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $allowed = WidgetService::availableSlugs();
        DB::delete('user_layouts', 'user_id = ?', [Auth::id()]);
        $sort = 10;
        foreach ($items as $item) {
            $slug = (string) ($item['slug'] ?? '');
            if (!in_array($slug, $allowed, true)) {
                continue;
            }
            $width = ($item['width'] ?? 'full') === 'half' ? 'half' : 'full';
            DB::run(
                'INSERT INTO user_layouts (user_id, widget_slug, sort_order, width) VALUES (?, ?, ?, ?)',
                [Auth::id(), $slug, $sort, $width]
            );
            $sort += 10;
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function resetLayout(): void
    {
        DB::delete('user_layouts', 'user_id = ?', [Auth::id()]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
