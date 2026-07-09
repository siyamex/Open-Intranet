CREATE TABLE news_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    color VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    body MEDIUMTEXT NULL,
    cover_path VARCHAR(255) NULL,
    category_id INT UNSIGNED NULL,
    author_id INT UNSIGNED NULL,
    status ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft',
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    allow_comments TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATETIME NULL,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_news_category FOREIGN KEY (category_id) REFERENCES news_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_news_status_published (status, published_at),
    INDEX idx_news_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_news_comments_news FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE,
    CONSTRAINT fk_news_comments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_news_comments_news (news_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
