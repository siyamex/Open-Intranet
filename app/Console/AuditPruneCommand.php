<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;
use App\Core\Settings;

final class AuditPruneCommand
{
    public const DESCRIPTION = 'Delete audit log entries older than the retention setting';

    public static function run(array $args): int
    {
        $days = (int) Settings::get('audit_retention_days', 365);
        $cutoff = date('Y-m-d H:i:s', time() - $days * 86400);
        $deleted = DB::delete('audit_logs', 'created_at < ?', [$cutoff]);
        echo "Pruned {$deleted} audit entries older than {$days} days.\n";
        return 0;
    }
}
