CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location ENUM('sidebar','navbar','footer') NOT NULL DEFAULT 'sidebar',
    label VARCHAR(100) NOT NULL,
    icon VARCHAR(100) NULL,
    url VARCHAR(500) NULL,
    route_name VARCHAR(100) NULL,
    parent_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    target ENUM('_self','_blank') NOT NULL DEFAULT '_self',
    visible_to JSON NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_items_parent FOREIGN KEY (parent_id) REFERENCES menu_items (id) ON DELETE CASCADE,
    INDEX idx_menu_location (location, enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE quick_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    url VARCHAR(500) NOT NULL,
    description VARCHAR(255) NULL,
    icon_type ENUM('library','upload') NOT NULL DEFAULT 'library',
    icon_value VARCHAR(255) NULL,
    bg_color VARCHAR(20) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    visible_to JSON NULL,
    open_new_tab TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    click_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_quick_links_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
