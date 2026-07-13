CREATE TABLE api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    selector CHAR(16) NOT NULL UNIQUE,
    scopes JSON NOT NULL,
    last_used_at DATETIME NULL,
    last_used_ip VARCHAR(45) NULL,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_api_tokens_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
