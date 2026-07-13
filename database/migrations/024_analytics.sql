CREATE TABLE page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(255) NOT NULL,
    user_id INT UNSIGNED NULL,
    user_hash CHAR(16) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_views_path (path, created_at),
    INDEX idx_page_views_created (created_at),
    INDEX idx_page_views_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE search_queries_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query VARCHAR(190) NOT NULL,
    result_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_log_created (created_at),
    INDEX idx_search_log_query (query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analytics_daily (
    day DATE NOT NULL,
    metric VARCHAR(50) NOT NULL,
    dimension VARCHAR(190) NULL,
    value INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (day, metric, dimension)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('analytics.view', 'View internal analytics', 'System')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
