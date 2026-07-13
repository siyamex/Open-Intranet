<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require __DIR__ . '/app/bootstrap.php';

/** @var array<string, class-string> $commands */
$commands = [
    'migrate' => \App\Console\MigrateCommand::class,
    'migrate:status' => \App\Console\MigrateStatusCommand::class,
    'seed' => \App\Console\SeedCommand::class,
    'seed:demo' => \App\Console\SeedDemoCommand::class,
    'make:admin' => \App\Console\MakeAdminCommand::class,
    'key:generate' => \App\Console\KeyGenerateCommand::class,
    'svg:test' => \App\Console\SvgTestCommand::class,
    'publish:due' => \App\Console\PublishDueCommand::class,
    'audit:prune' => \App\Console\AuditPruneCommand::class,
    'backup' => \App\Console\BackupCommand::class,
    'routes:audit' => \App\Console\RoutesAuditCommand::class,
    'digest:send' => \App\Console\DigestSendCommand::class,
    'wiki:reminders' => \App\Console\WikiRemindersCommand::class,
    'forms:prune' => \App\Console\FormsPruneCommand::class,
    'ldap:sync' => \App\Console\LdapSyncCommand::class,
    'vapid:generate' => \App\Console\VapidGenerateCommand::class,
];

$name = $argv[1] ?? 'help';
$args = array_slice($argv, 2);

if ($name === 'help' || $name === '--help' || $name === '-h') {
    echo "OpenIntranet CLI\n\nUsage: php cli.php <command> [args]\n\nCommands:\n";
    foreach ($commands as $cmd => $class) {
        $desc = defined($class . '::DESCRIPTION') ? constant($class . '::DESCRIPTION') : '';
        printf("  %-22s %s\n", $cmd, $desc);
    }
    exit(0);
}

if (!isset($commands[$name])) {
    fwrite(STDERR, "Unknown command: {$name}\nRun `php cli.php help` for a list.\n");
    exit(1);
}

try {
    exit((int) $commands[$name]::run($args));
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
