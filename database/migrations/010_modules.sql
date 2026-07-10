CREATE TABLE modules (
    slug VARCHAR(50) NOT NULL PRIMARY KEY,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modules (slug, enabled) VALUES
    ('news', 1),
    ('documents', 1),
    ('directory', 1),
    ('org_chart', 1),
    ('comments', 1),
    ('reactions', 1);
