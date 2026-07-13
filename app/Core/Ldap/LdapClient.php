<?php

declare(strict_types=1);

namespace App\Core\Ldap;

use App\Core\Crypto;

/**
 * Thin wrapper over ext-ldap. All calls are guarded with extension_loaded()
 * so the app runs fine on hosts without php-ldap — the admin UI just
 * reports it as unavailable.
 */
final class LdapClient
{
    private $conn = null; // \LDAP\Connection (PHP 8.1+) or resource

    public function __construct(private array $config)
    {
    }

    public static function available(): bool
    {
        return extension_loaded('ldap');
    }

    /**
     * @throws \RuntimeException
     */
    public function connect(): void
    {
        if (!self::available()) {
            throw new \RuntimeException('The PHP ldap extension is not enabled on this server.');
        }
        $uri = 'ldap://' . $this->config['host'] . ':' . $this->config['port'];
        $conn = @ldap_connect($uri);
        if ($conn === false) {
            throw new \RuntimeException('Could not initialize LDAP connection.');
        }
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 8);
        if (!empty($this->config['use_tls'])) {
            if (!@ldap_start_tls($conn)) {
                throw new \RuntimeException('STARTTLS negotiation failed: ' . ldap_error($conn));
            }
        }
        $bindDn = (string) ($this->config['bind_dn'] ?? '');
        $password = '';
        if (!empty($this->config['bind_password_encrypted'])) {
            $password = Crypto::decrypt((string) $this->config['bind_password_encrypted']);
        }
        $bound = $bindDn !== ''
            ? @ldap_bind($conn, $bindDn, $password)
            : @ldap_bind($conn); // anonymous bind
        if (!$bound) {
            throw new \RuntimeException('Bind failed: ' . ldap_error($conn));
        }
        $this->conn = $conn;
    }

    /**
     * Bind as a specific user (used for LDAP password login).
     */
    public function bindAs(string $dn, string $password): bool
    {
        if (!self::available() || $password === '') {
            return false;
        }
        $uri = 'ldap://' . $this->config['host'] . ':' . $this->config['port'];
        $conn = ldap_connect($uri);
        if ($conn === false) {
            return false;
        }
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, 8);
        if (!empty($this->config['use_tls'])) {
            @ldap_start_tls($conn);
        }
        $ok = @ldap_bind($conn, $dn, $password);
        @ldap_unbind($conn);
        return (bool) $ok;
    }

    /**
     * @return array<int, array<string, mixed>> raw entries (attribute => [values])
     */
    public function search(string $filter, array $attributes, int $limit = 0): array
    {
        if ($this->conn === null) {
            throw new \RuntimeException('Not connected.');
        }
        $result = @ldap_search($this->conn, (string) $this->config['base_dn'], $filter, $attributes, 0, $limit);
        if ($result === false) {
            throw new \RuntimeException('Search failed: ' . ldap_error($this->conn));
        }
        $entries = ldap_get_entries($this->conn, $result);
        if ($entries === false) {
            return [];
        }
        $rows = [];
        for ($i = 0; $i < (int) $entries['count']; $i++) {
            $rows[] = $entries[$i];
        }
        return $rows;
    }

    public function close(): void
    {
        if ($this->conn !== null) {
            @ldap_unbind($this->conn);
            $this->conn = null;
        }
    }
}
