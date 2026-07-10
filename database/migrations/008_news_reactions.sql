CREATE TABLE news_reactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    emoji VARCHAR(16) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reaction (news_id, user_id, emoji),
    CONSTRAINT fk_news_reactions_news FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE,
    CONSTRAINT fk_news_reactions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
