CREATE TABLE banners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    message VARCHAR(500) NOT NULL,
    severity ENUM('info','warning','critical') NOT NULL DEFAULT 'warning',
    link_url VARCHAR(500) NULL,
    link_label VARCHAR(100) NULL,
    dismissible TINYINT(1) NOT NULL DEFAULT 1,
    require_ack TINYINT(1) NOT NULL DEFAULT 0,
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    visible_to JSON NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_banners_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE banner_acknowledgements (
    banner_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (banner_id, user_id),
    CONSTRAINT fk_banner_ack_banner FOREIGN KEY (banner_id) REFERENCES banners (id) ON DELETE CASCADE,
    CONSTRAINT fk_banner_ack_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('banners.manage', 'Manage emergency banners', 'System')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
