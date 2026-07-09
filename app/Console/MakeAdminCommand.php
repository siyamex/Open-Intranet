<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\DB;

final class MakeAdminCommand
{
    public const DESCRIPTION = 'Create a super admin: make:admin email password [name]';

    public static function run(array $args): int
    {
        $email = strtolower(trim($args[0] ?? ''));
        $password = (string) ($args[1] ?? '');
        $name = trim($args[2] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            fwrite(STDERR, "Usage: php cli.php make:admin email password [name]\n");
            return 1;
        }
        if (strlen($password) < 10) {
            fwrite(STDERR, "Password must be at least 10 characters.\n");
            return 1;
        }
        if ($name === '') {
            $name = ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]));
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $now = date('Y-m-d H:i:s');

        $existing = DB::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing !== null) {
            $userId = (int) $existing['id'];
            DB::update('users', [
                'password_hash' => $hash,
                'status' => 'active',
                'must_change_password' => 0,
                'updated_at' => $now,
            ], 'id = ?', [$userId]);
            echo "Existing user #{$userId} updated with the new password.\n";
        } else {
            $userId = DB::insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'status' => 'active',
                'must_change_password' => 0,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            echo "User #{$userId} created.\n";
        }

        $role = DB::fetch("SELECT id FROM roles WHERE slug = 'super_admin'");
        if ($role === null) {
            fwrite(STDERR, "Warning: super_admin role not found — run `php cli.php migrate` and `php cli.php seed` first.\n");
            return 1;
        }
        DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, (int) $role['id']]);
        echo "super_admin role assigned to {$email}.\n";
        return 0;
    }
}
