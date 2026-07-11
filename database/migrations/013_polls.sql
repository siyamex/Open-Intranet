CREATE TABLE polls (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    type ENUM('single','multiple') NOT NULL DEFAULT 'single',
    is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
    opens_at DATETIME NULL,
    closes_at DATETIME NULL,
    visible_to JSON NULL,
    department_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_polls_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL,
    CONSTRAINT fk_polls_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE poll_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poll_id INT UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_poll_options_poll FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE poll_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poll_id INT UNSIGNED NOT NULL,
    option_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    voter_hash CHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vote_user (option_id, user_id),
    UNIQUE KEY uq_vote_hash (option_id, voter_hash),
    CONSTRAINT fk_poll_votes_poll FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE,
    CONSTRAINT fk_poll_votes_option FOREIGN KEY (option_id) REFERENCES poll_options (id) ON DELETE CASCADE,
    CONSTRAINT fk_poll_votes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modules (slug, enabled) VALUES ('polls', 1)
    ON DUPLICATE KEY UPDATE slug = slug;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('polls.manage', 'Manage polls', 'Content')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
