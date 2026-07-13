CREATE TABLE forms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    fields JSON NOT NULL,
    approver_type ENUM('user','manager','role') NOT NULL DEFAULT 'manager',
    approver_user_id INT UNSIGNED NULL,
    approver_role_id INT UNSIGNED NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    retention_days INT UNSIGNED NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_forms_approver_user FOREIGN KEY (approver_user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_forms_approver_role FOREIGN KEY (approver_role_id) REFERENCES roles (id) ON DELETE SET NULL,
    CONSTRAINT fk_forms_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    status ENUM('submitted','in_review','approved','rejected') NOT NULL DEFAULT 'submitted',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_submissions_form FOREIGN KEY (form_id) REFERENCES forms (id) ON DELETE CASCADE,
    CONSTRAINT fk_submissions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_submissions_status (form_id, status),
    INDEX idx_submissions_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE form_submission_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_events_submission FOREIGN KEY (submission_id) REFERENCES form_submissions (id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_events_actor FOREIGN KEY (actor_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modules (slug, enabled) VALUES ('forms', 1)
    ON DUPLICATE KEY UPDATE slug = slug;

INSERT INTO permissions (slug, label, group_name)
    VALUES ('forms.manage', 'Manage request forms', 'Content')
    ON DUPLICATE KEY UPDATE label = VALUES(label);
