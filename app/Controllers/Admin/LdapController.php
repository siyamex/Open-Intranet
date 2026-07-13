<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Audit;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\Ldap\LdapClient;
use App\Core\Ldap\LdapSync;
use App\Core\View;

final class LdapController
{
    public function index(): void
    {
        View::render('admin/ldap/index', [
            'title' => 'LDAP / Active Directory',
            'config' => $this->config(),
            'available' => LdapClient::available(),
            'roles' => DB::fetchAll('SELECT id, name FROM roles ORDER BY name'),
        ], 'admin');
    }

    public function save(): void
    {
        $existing = $this->config();
        $password = (string) ($_POST['bind_password'] ?? '');
        $groupMap = [];
        foreach ((array) ($_POST['group_dn'] ?? []) as $i => $dn) {
            $dn = trim((string) $dn);
            $roleId = (int) ($_POST['group_role'][$i] ?? 0);
            if ($dn !== '' && $roleId > 0) {
                $groupMap[$dn] = $roleId;
            }
        }
        $data = [
            'enabled' => !empty($_POST['enabled']) ? 1 : 0,
            'host' => trim((string) ($_POST['host'] ?? '')),
            'port' => max(1, min(65535, (int) ($_POST['port'] ?? 389))),
            'use_tls' => !empty($_POST['use_tls']) ? 1 : 0,
            'bind_dn' => trim((string) ($_POST['bind_dn'] ?? '')) ?: null,
            'base_dn' => trim((string) ($_POST['base_dn'] ?? '')),
            'user_filter' => trim((string) ($_POST['user_filter'] ?? '')) ?: '(objectClass=person)',
            'attr_name' => trim((string) ($_POST['attr_name'] ?? '')) ?: 'cn',
            'attr_email' => trim((string) ($_POST['attr_email'] ?? '')) ?: 'mail',
            'attr_title' => trim((string) ($_POST['attr_title'] ?? '')) ?: 'title',
            'attr_department' => trim((string) ($_POST['attr_department'] ?? '')) ?: 'department',
            'attr_phone' => trim((string) ($_POST['attr_phone'] ?? '')) ?: 'telephoneNumber',
            'attr_manager' => trim((string) ($_POST['attr_manager'] ?? '')) ?: 'manager',
            'attr_uid' => trim((string) ($_POST['attr_uid'] ?? '')) ?: 'sAMAccountName',
            'group_role_map' => $groupMap === [] ? null : json_encode($groupMap),
            'allow_ldap_bind_login' => !empty($_POST['allow_ldap_bind_login']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($password !== '') {
            $data['bind_password_encrypted'] = Crypto::encrypt($password);
        }
        if ($existing === null) {
            DB::insert('ldap_config', $data);
        } else {
            DB::update('ldap_config', $data, 'id = ?', [(int) $existing['id']]);
        }
        Audit::log('ldap.config_updated', 'ldap_config', null);
        flash('success', 'LDAP settings saved.');
        redirect('admin/ldap');
    }

    /**
     * "Test connection & preview 10 users" button.
     */
    public function test(): void
    {
        header('Content-Type: application/json');
        $config = $this->config();
        if ($config === null) {
            echo json_encode(['ok' => false, 'error' => 'Save the configuration first.']);
            exit;
        }
        $result = LdapSync::run($config, true, 10);
        echo json_encode([
            'ok' => $result['errors'] === [],
            'errors' => $result['errors'],
            'preview' => array_slice($result['changes'], 0, 10),
        ]);
        exit;
    }

    public function sync(): void
    {
        $config = $this->config();
        if ($config === null) {
            flash('error', 'Configure LDAP first.');
            redirect('admin/ldap');
        }
        $dryRun = !empty($_POST['dry_run']);
        $result = LdapSync::run($config, $dryRun);
        $_SESSION['ldap_sync_report'] = $result;
        flash($result['errors'] === [] ? 'success' : 'error',
            $dryRun
                ? 'Dry run complete — ' . $result['created'] . ' to create, ' . $result['updated'] . ' to update, ' . $result['deactivated'] . ' to deactivate.'
                : 'Sync complete — ' . $result['created'] . ' created, ' . $result['updated'] . ' updated, ' . $result['deactivated'] . ' deactivated.'
        );
        redirect('admin/ldap');
    }

    private function config(): ?array
    {
        return DB::fetch('SELECT * FROM ldap_config ORDER BY id LIMIT 1');
    }
}
