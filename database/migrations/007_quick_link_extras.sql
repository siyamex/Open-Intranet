-- Per-user preferences (quick-link order now; dark mode & more later)
CREATE TABLE user_prefs (
    user_id INT UNSIGNED NOT NULL,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, `key`),
    CONSTRAINT fk_user_prefs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_quick_link_pins (
    user_id INT UNSIGNED NOT NULL,
    quick_link_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, quick_link_id),
    CONSTRAINT fk_ql_pins_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_ql_pins_link FOREIGN KEY (quick_link_id) REFERENCES quick_links (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily click rollup for per-tile analytics sparklines
CREATE TABLE quick_link_clicks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quick_link_id INT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    clicks INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_link_day (quick_link_id, day),
    CONSTRAINT fk_ql_clicks_link FOREIGN KEY (quick_link_id) REFERENCES quick_links (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
