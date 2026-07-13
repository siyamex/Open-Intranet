<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\DB;
use App\Core\Router;
use App\Core\Settings;

/**
 * Logs a page view for GET requests (privacy-friendly: optional anonymize
 * hashes the user id instead of storing it). Runs fire-and-forget — never
 * blocks or fails the request.
 */
final class AnalyticsMiddleware
{
    public function handle(?string $param = null): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }
        $path = Router::instance()->currentPath();
        // Skip asset-ish and API/webhook paths
        if (preg_match('#^/(assets|theme-assets|api|sw\.js|manifest|files|avatars|qlicons|news-media)#', $path)) {
            return;
        }
        try {
            $anonymize = (bool) Settings::get('analytics_anonymize', false);
            $userId = Auth::id();
            DB::insert('page_views', [
                'path' => mb_substr($path, 0, 255),
                'user_id' => $anonymize ? null : $userId,
                'user_hash' => $userId !== null
                    ? substr(hash_hmac('sha256', (string) $userId, (string) Config::env('APP_KEY', '')), 0, 16)
                    : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // analytics must never break a request
        }
    }
}
