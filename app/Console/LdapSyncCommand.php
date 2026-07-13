<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;
use App\Core\Ldap\LdapSync;

final class LdapSyncCommand
{
    public const DESCRIPTION = 'Sync users from LDAP/AD: create/update/deactivate-missing (--dry-run supported)';

    public static function run(array $args): int
    {
        $config = DB::fetch('SELECT * FROM ldap_config WHERE enabled = 1 ORDER BY id LIMIT 1');
        if ($config === null) {
            fwrite(STDERR, "LDAP sync is not configured or not enabled — see Admin > LDAP.\n");
            return 1;
        }
        $dryRun = in_array('--dry-run', $args, true);
        $result = LdapSync::run($config, $dryRun);

        foreach ($result['changes'] as $change) {
            printf("[%s] %s <%s> (%s)\n", strtoupper($change['action']), $change['name'], $change['email'], $change['dn']);
        }
        foreach ($result['errors'] as $error) {
            fwrite(STDERR, "ERROR: {$error}\n");
        }
        printf(
            "%s: %d created, %d updated, %d deactivated.\n",
            $dryRun ? 'DRY RUN' : 'SYNC',
            $result['created'],
            $result['updated'],
            $result['deactivated']
        );
        return $result['errors'] === [] ? 0 : 1;
    }
}
