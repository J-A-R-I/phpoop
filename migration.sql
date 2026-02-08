CREATE TABLE IF NOT EXISTS auth_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_provider_connection (provider, provider_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_provider (user_id, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE posts 
ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS meta_title VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS meta_description TEXT DEFAULT NULL;
