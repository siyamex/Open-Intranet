CREATE TABLE widgets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    type ENUM('builtin','html','rss') NOT NULL DEFAULT 'builtin',
    config JSON NULL,
    module VARCHAR(50) NULL,
    permission VARCHAR(60) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default layout per role: which widgets, in what order, for users with
-- that role (role_id NULL = fallback layout for everyone else)
CREATE TABLE role_layouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NULL,
    widget_slug VARCHAR(60) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    width ENUM('full','half') NOT NULL DEFAULT 'full',
    CONSTRAINT fk_role_layouts_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_role_layouts_widget FOREIGN KEY (widget_slug) REFERENCES widgets (slug) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_layouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    widget_slug VARCHAR(60) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    width ENUM('full','half') NOT NULL DEFAULT 'full',
    UNIQUE KEY uq_user_widget (user_id, widget_slug),
    CONSTRAINT fk_user_layouts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_layouts_widget FOREIGN KEY (widget_slug) REFERENCES widgets (slug) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO widgets (slug, name, type, module, permission) VALUES
    ('quick_links', 'Apps / quick links', 'builtin', NULL, NULL),
    ('news', 'News', 'builtin', 'news', NULL),
    ('gazette', 'Gazette documents', 'builtin', 'documents', NULL),
    ('events', 'Upcoming events', 'builtin', 'events', NULL),
    ('poll', 'Active poll', 'builtin', 'polls', NULL),
    ('kudos', 'Latest kudos', 'builtin', 'kudos', NULL),
    ('celebrations', 'Birthdays & anniversaries', 'builtin', 'celebrations', NULL)
    ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO permissions (slug, label, group_name)
    VALUES ('widgets.manage', 'Manage dashboard widgets', 'System')
    ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Global fallback layout (role_id NULL) used until an admin customizes it
INSERT INTO role_layouts (role_id, widget_slug, sort_order, width) VALUES
    (NULL, 'quick_links', 10, 'full'),
    (NULL, 'celebrations', 20, 'full'),
    (NULL, 'poll', 30, 'half'),
    (NULL, 'kudos', 40, 'half'),
    (NULL, 'news', 50, 'full'),
    (NULL, 'events', 60, 'half'),
    (NULL, 'gazette', 70, 'half');
