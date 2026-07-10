<?php

declare(strict_types=1);

namespace App\Core;

final class Audit
{
    public static function log(
        string $action,
        ?string $entityType = null,
        string|int|null $entityId = null,
        array $meta = [],
        ?int $userId = null
    ): void {
        try {
            DB::insert('audit_logs', [
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId === null ? null : (string) $entityId,
                'meta' => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                    ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255)
                    : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Auditing must never break the request.
        }
    }
}
