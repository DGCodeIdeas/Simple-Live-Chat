CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(32) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Argon2id',
    status ENUM('online', 'offline') DEFAULT 'offline',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    avatar_data TEXT COMMENT 'Base64 small avatar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at DECIMAL(20,4) NOT NULL COMMENT 'Microtime for strict ordering',
    type ENUM('text', 'image') DEFAULT 'text',
    INDEX idx_sender (sender_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
