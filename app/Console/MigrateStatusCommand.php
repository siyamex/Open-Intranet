<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Migrator;

final class MigrateStatusCommand
{
    public const DESCRIPTION = 'Show which migrations have run';

    public static function run(array $args): int
    {
        foreach (Migrator::status() as $row) {
            printf("[%-7s] %s\n", $row['status'], $row['migration']);
        }
        return 0;
    }
}
