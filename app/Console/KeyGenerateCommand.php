<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Crypto;

final class KeyGenerateCommand
{
    public const DESCRIPTION = 'Generate an APP_KEY (pass --write to store it in .env)';

    public static function run(array $args): int
    {
        $key = Crypto::generateKey();
        if (!in_array('--write', $args, true)) {
            echo $key . "\n";
            echo "Add this to .env as APP_KEY=... (or re-run with --write)\n";
            return 0;
        }

        $envFile = BASE_PATH . '/.env';
        if (!is_file($envFile)) {
            fwrite(STDERR, ".env not found — copy .env.example to .env first.\n");
            return 1;
        }
        $contents = (string) file_get_contents($envFile);
        if (preg_match('/^APP_KEY=.+$/m', $contents)) {
            fwrite(STDERR, "APP_KEY is already set in .env — refusing to overwrite (existing encrypted secrets would become unreadable).\n");
            return 1;
        }
        if (preg_match('/^APP_KEY=\s*$/m', $contents)) {
            $contents = (string) preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY=' . $key, $contents, 1);
        } else {
            $contents = rtrim($contents, "\r\n") . "\nAPP_KEY=" . $key . "\n";
        }
        file_put_contents($envFile, $contents);
        echo "APP_KEY written to .env\n";
        return 0;
    }
}
