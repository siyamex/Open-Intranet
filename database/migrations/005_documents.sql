CREATE TABLE doc_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL UNIQUE,
    parent_id INT UNSIGNED NULL,
    visible_to JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_categories_parent FOREIGN KEY (parent_id) REFERENCES doc_categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id INT UNSIGNED NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime VARCHAR(150) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    parent_doc_id INT UNSIGNED NULL,
    visible_to JSON NULL,
    uploaded_by INT UNSIGNED NULL,
    download_count INT UNSIGNED NOT NULL DEFAULT 0,
    is_gazette TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_category FOREIGN KEY (category_id) REFERENCES doc_categories (id) ON DELETE SET NULL,
    CONSTRAINT fk_documents_parent FOREIGN KEY (parent_doc_id) REFERENCES documents (id) ON DELETE CASCADE,
    CONSTRAINT fk_documents_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL,
    INDEX idx_documents_gazette (is_gazette, published_at),
    INDEX idx_documents_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
