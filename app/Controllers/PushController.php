<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Settings;

final class PushController
{
    public function publicKey(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['key' => (string) Settings::get('vapid_public_key', '')]);
        exit;
    }

    public function subscribe(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $endpoint = (string) ($payload['endpoint'] ?? '');
        $p256dh = (string) ($payload['keys']['p256dh'] ?? '');
        $auth = (string) ($payload['keys']['auth'] ?? '');
        header('Content-Type: application/json');
        if ($endpoint === '' || $p256dh === '' || $auth === '' || !preg_match('#^https://#i', $endpoint)) {
            echo json_encode(['ok' => false]);
            exit;
        }
        DB::run(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, created_at) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)',
            [Auth::id(), $endpoint, $p256dh, $auth, date('Y-m-d H:i:s')]
        );
        echo json_encode(['ok' => true]);
        exit;
    }

    public function unsubscribe(): void
    {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $endpoint = (string) ($payload['endpoint'] ?? '');
        if ($endpoint !== '') {
            DB::delete('push_subscriptions', 'user_id = ? AND endpoint = ?', [Auth::id(), $endpoint]);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
