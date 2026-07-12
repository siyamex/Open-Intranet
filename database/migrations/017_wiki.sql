CREATE TABLE wiki_spaces (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    visible_to JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wiki_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    space_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    body_md MEDIUMTEXT NULL,
    owner_id INT UNSIGNED NULL,
    review_due DATE NULL,
    sort_order INT NOT NULL DEFAULT 0,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wiki_page (space_id, slug),
    CONSTRAINT fk_wiki_pages_space FOREIGN KEY (space_id) REFERENCES wiki_spaces (id) ON DELETE CASCADE,
    CONSTRAINT fk_wiki_pages_parent FOREIGN KEY (parent_id) REFERENCES wiki_pages (id) ON DELETE SET NULL,
    CONSTRAINT fk_wiki_pages_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE SET NULL,
    FULLTEXT INDEX ft_wiki (title, body_md)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wiki_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body_md MEDIUMTEXT NULL,
    edited_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wiki_versions_page FOREIGN KEY (page_id) REFERENCES wiki_pages (id) ON DELETE CASCADE,
    CONSTRAINT fk_wiki_versions_user FOREIGN KEY (edited_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_wiki_versions_page (page_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modules (slug, enabled) VALUES ('wiki', 1)
    ON DUPLICATE KEY UPDATE slug = slug;

INSERT INTO permissions (slug, label, group_name) VALUES
    ('wiki.manage', 'Manage wiki spaces', 'Content'),
    ('wiki.edit', 'Edit wiki pages', 'Content')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
