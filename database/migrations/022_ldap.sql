CREATE TABLE ldap_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    host VARCHAR(255) NOT NULL DEFAULT '',
    port INT UNSIGNED NOT NULL DEFAULT 389,
    use_tls TINYINT(1) NOT NULL DEFAULT 1,
    bind_dn VARCHAR(255) NULL,
    bind_password_encrypted TEXT NULL,
    base_dn VARCHAR(255) NOT NULL DEFAULT '',
    user_filter VARCHAR(255) NOT NULL DEFAULT '(objectClass=person)',
    attr_name VARCHAR(60) NOT NULL DEFAULT 'cn',
    attr_email VARCHAR(60) NOT NULL DEFAULT 'mail',
    attr_title VARCHAR(60) NOT NULL DEFAULT 'title',
    attr_department VARCHAR(60) NOT NULL DEFAULT 'department',
    attr_phone VARCHAR(60) NOT NULL DEFAULT 'telephoneNumber',
    attr_manager VARCHAR(60) NOT NULL DEFAULT 'manager',
    attr_uid VARCHAR(60) NOT NULL DEFAULT 'sAMAccountName',
    group_role_map JSON NULL,
    allow_ldap_bind_login TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE users ADD COLUMN ldap_dn VARCHAR(500) NULL AFTER email;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('ldap.manage', 'Manage LDAP / AD sync', 'System')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
