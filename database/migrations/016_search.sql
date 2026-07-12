ALTER TABLE news ADD FULLTEXT INDEX ft_news (title, body);
ALTER TABLE documents ADD FULLTEXT INDEX ft_documents (title, description);
ALTER TABLE users ADD FULLTEXT INDEX ft_users (name, job_title);
ALTER TABLE quick_links ADD FULLTEXT INDEX ft_quick_links (title);

CREATE TABLE search_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    query VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_history_user (user_id, created_at),
    CONSTRAINT fk_search_history_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
