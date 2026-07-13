<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Settings;
use App\Core\WebPush;

final class VapidGenerateCommand
{
    public const DESCRIPTION = 'Generate VAPID keys for web push (writes to settings)';

    public static function run(array $args): int
    {
        $keys = WebPush::generateVapidKeys();
        Settings::set('vapid_public_key', $keys['public']);
        Settings::set('vapid_private_key', $keys['private']);
        echo "VAPID keys generated and saved.\n";
        echo "Public key: {$keys['public']}\n";
        return 0;
    }
}
