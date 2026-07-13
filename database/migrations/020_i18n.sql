CREATE TABLE languages (
    code VARCHAR(10) NOT NULL PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    native_name VARCHAR(60) NOT NULL,
    is_rtl TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO languages (code, name, native_name, is_rtl) VALUES
    ('en', 'English', 'English', 0),
    ('dv', 'Dhivehi', 'ދިވެހި', 1),
    ('ar', 'Arabic', 'العربية', 1)
    ON DUPLICATE KEY UPDATE name = VALUES(name);

CREATE TABLE news_translations (
    news_id INT UNSIGNED NOT NULL,
    locale VARCHAR(10) NOT NULL,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    body MEDIUMTEXT NULL,
    PRIMARY KEY (news_id, locale),
    CONSTRAINT fk_news_translations_news FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
