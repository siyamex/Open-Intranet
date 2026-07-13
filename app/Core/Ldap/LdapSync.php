<?php

declare(strict_types=1);

namespace App\Core\Ldap;

use App\Core\Audit;
use App\Core\DB;

/**
 * create/update/deactivate-missing sync driven by ldap_config's attribute
 * mapping and group->role map. --dry-run only computes and reports the
 * changes without writing.
 */
final class LdapSync
{
    /**
     * @return array{created: int, updated: int, deactivated: int, errors: string[], changes: array<int, array>}
     */
    public static function run(array $config, bool $dryRun = false, int $previewLimit = 0): array
    {
        $result = ['created' => 0, 'updated' => 0, 'deactivated' => 0, 'errors' => [], 'changes' => []];
        $client = new LdapClient($config);
        try {
            $client->connect();
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
            return $result;
        }

        $map = [
            'name' => (string) $config['attr_name'],
            'email' => (string) $config['attr_email'],
            'title' => (string) $config['attr_title'],
            'department' => (string) $config['attr_department'],
            'phone' => (string) $config['attr_phone'],
            'manager' => (string) $config['attr_manager'],
            'uid' => (string) $config['attr_uid'],
        ];
        $attrs = array_values(array_unique(array_merge(array_values($map), ['dn', 'memberof'])));

        try {
            $entries = $client->search((string) $config['user_filter'], $attrs, $previewLimit);
        } catch (\Throwable $e) {
            $client->close();
            $result['errors'][] = $e->getMessage();
            return $result;
        }
        $client->close();

        $groupRoleMap = is_array($config['group_role_map'] ?? null) ? $config['group_role_map'] : [];
        $seenDns = [];
        $now = date('Y-m-d H:i:s');

        foreach ($entries as $entry) {
            $dn = (string) ($entry['dn'] ?? '');
            if ($dn === '') {
                continue;
            }
            $seenDns[] = $dn;
            $value = static fn (string $attr): ?string => isset($entry[strtolower($attr)][0]) ? (string) $entry[strtolower($attr)][0] : null;

            $email = $value($map['email']);
            $name = $value($map['name']);
            if ($email === null || $name === null) {
                $result['errors'][] = "Skipped {$dn}: missing name or email attribute.";
                continue;
            }
            $existing = DB::fetch('SELECT * FROM users WHERE ldap_dn = ? OR email = ?', [$dn, $email]);
            $changeSet = [
                'name' => $name,
                'email' => strtolower($email),
                'job_title' => $value($map['title']),
                'phone' => $value($map['phone']),
                'ldap_dn' => $dn,
            ];

            if ($existing === null) {
                $result['changes'][] = ['action' => 'create', 'dn' => $dn, 'email' => $email, 'name' => $name];
                $result['created']++;
                if (!$dryRun) {
                    $userId = DB::insert('users', array_merge($changeSet, [
                        'password_hash' => null,
                        'status' => 'active',
                        'email_verified_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]));
                    self::applyRoles($userId, $entry, $groupRoleMap);
                }
            } else {
                $diff = array_diff_assoc(array_filter($changeSet, static fn ($v) => $v !== null), array_intersect_key($existing, $changeSet));
                if ($diff !== []) {
                    $result['changes'][] = ['action' => 'update', 'dn' => $dn, 'email' => $email, 'name' => $name, 'fields' => array_keys($diff)];
                    $result['updated']++;
                    if (!$dryRun) {
                        DB::update('users', array_merge($changeSet, ['updated_at' => $now]), 'id = ?', [(int) $existing['id']]);
                        self::applyRoles((int) $existing['id'], $entry, $groupRoleMap);
                    }
                }
            }
        }

        // deactivate users previously synced from LDAP but no longer present
        $missing = DB::fetchAll(
            "SELECT id, name, email, ldap_dn FROM users WHERE ldap_dn IS NOT NULL AND status = 'active'" .
            ($seenDns !== [] ? ' AND ldap_dn NOT IN (' . implode(',', array_fill(0, count($seenDns), '?')) . ')' : ''),
            $seenDns
        );
        foreach ($missing as $user) {
            $result['changes'][] = ['action' => 'deactivate', 'dn' => $user['ldap_dn'], 'email' => $user['email'], 'name' => $user['name']];
            $result['deactivated']++;
            if (!$dryRun) {
                DB::update('users', ['status' => 'inactive'], 'id = ?', [(int) $user['id']]);
            }
        }

        if (!$dryRun) {
            Audit::log('ldap.sync', 'ldap', null, [
                'created' => $result['created'], 'updated' => $result['updated'], 'deactivated' => $result['deactivated'],
            ]);
        }
        return $result;
    }

    private static function applyRoles(int $userId, array $entry, array $groupRoleMap): void
    {
        if ($groupRoleMap === []) {
            return;
        }
        $memberOf = array_map('strtolower', (array) ($entry['memberof'] ?? []));
        foreach ($groupRoleMap as $groupDn => $roleId) {
            if (in_array(strtolower((string) $groupDn), $memberOf, true)) {
                DB::run('INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)', [$userId, (int) $roleId]);
            }
        }
    }
}
