CREATE TABLE kudos_values (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(50) NOT NULL UNIQUE,
    emoji VARCHAR(16) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO kudos_values (label, emoji) VALUES
    ('Teamwork', '🤝'), ('Innovation', '💡'), ('Customer Focus', '🌟'),
    ('Going the Extra Mile', '🚀'), ('Mentorship', '🎓')
    ON DUPLICATE KEY UPDATE label = label;

CREATE TABLE kudos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NULL,
    recipient_id INT UNSIGNED NOT NULL,
    value_id INT UNSIGNED NULL,
    message VARCHAR(300) NOT NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kudos_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_kudos_recipient FOREIGN KEY (recipient_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_kudos_value FOREIGN KEY (value_id) REFERENCES kudos_values (id) ON DELETE SET NULL,
    INDEX idx_kudos_created (created_at),
    INDEX idx_kudos_recipient (recipient_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kudos_reactions (
    kudos_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    emoji VARCHAR(16) NOT NULL,
    PRIMARY KEY (kudos_id, user_id, emoji),
    CONSTRAINT fk_kudos_reactions_kudos FOREIGN KEY (kudos_id) REFERENCES kudos (id) ON DELETE CASCADE,
    CONSTRAINT fk_kudos_reactions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modules (slug, enabled) VALUES ('kudos', 1)
    ON DUPLICATE KEY UPDATE slug = slug;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('kudos.moderate', 'Moderate kudos', 'Content')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
