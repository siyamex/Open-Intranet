CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    all_day TINYINT(1) NOT NULL DEFAULT 0,
    color VARCHAR(20) NULL,
    created_by INT UNSIGNED NULL,
    visible_to JSON NULL,
    rsvp_enabled TINYINT(1) NOT NULL DEFAULT 1,
    recurrence ENUM('none','weekly','monthly') NOT NULL DEFAULT 'none',
    series_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_events_series FOREIGN KEY (series_id) REFERENCES events (id) ON DELETE CASCADE,
    INDEX idx_events_range (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_rsvps (
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    response ENUM('going','maybe','no') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id, user_id),
    CONSTRAINT fk_rsvps_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
    CONSTRAINT fk_rsvps_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modules (slug, enabled) VALUES ('events', 1)
    ON DUPLICATE KEY UPDATE slug = slug;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('events.manage', 'Manage events', 'Content')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
