<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Migrator;

final class MigrateCommand
{
    public const DESCRIPTION = 'Run pending database migrations';

    public static function run(array $args): int
    {
        $applied = Migrator::migrate();
        if ($applied === []) {
            echo "Nothing to migrate.\n";
            return 0;
        }
        foreach ($applied as $migration) {
            echo "Migrated: {$migration}\n";
        }
        echo count($applied) . " migration(s) applied.\n";
        return 0;
    }
}
