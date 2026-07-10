<?php

declare(strict_types=1);

namespace App\Core;

final class Visibility
{
    /**
     * Check a visible_to JSON column (array of role slugs; null/empty = everyone)
     * against the current user's roles. Super admins always pass.
     */
    public static function allowed(?string $visibleToJson): bool
    {
        if ($visibleToJson === null || $visibleToJson === '') {
            return true;
        }
        if (Auth::hasRole('super_admin')) {
            return true;
        }
        $allowed = json_decode($visibleToJson, true);
        if (!is_array($allowed) || $allowed === []) {
            return true;
        }
        return array_intersect($allowed, Auth::roles()) !== [];
    }
}
