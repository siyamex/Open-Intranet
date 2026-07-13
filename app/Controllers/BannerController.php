<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;

final class BannerController
{
    public function dismiss(string $id): void
    {
        $banner = DB::fetch('SELECT id, dismissible FROM banners WHERE id = ?', [(int) $id]);
        if ($banner !== null && (int) $banner['dismissible'] === 1) {
            $dismissed = (array) ($_SESSION['dismissed_banners'] ?? []);
            $dismissed[] = (int) $id;
            $_SESSION['dismissed_banners'] = array_values(array_unique($dismissed));
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    public function acknowledge(string $id): void
    {
        $banner = DB::fetch('SELECT id FROM banners WHERE id = ?', [(int) $id]);
        if ($banner !== null) {
            DB::run(
                'INSERT IGNORE INTO banner_acknowledgements (banner_id, user_id) VALUES (?, ?)',
                [(int) $id, Auth::id()]
            );
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
