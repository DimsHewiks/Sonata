CREATE DATABASE IF NOT EXISTS sonata;
USE sonata;

DROP FUNCTION IF EXISTS UUID_TO_BIN;
DROP FUNCTION IF EXISTS BIN_TO_UUID;

CREATE FUNCTION UUID_TO_BIN(_uuid CHAR(36)) RETURNS BINARY(16)
DETERMINISTIC
SQL SECURITY INVOKER
RETURN UNHEX(REPLACE(_uuid, '-', ''));

DELIMITER $$
CREATE FUNCTION BIN_TO_UUID(b BINARY(16)) RETURNS CHAR(36)
BEGIN
    DECLARE hexStr CHAR(32);
    SET hexStr = HEX(b);
    RETURN LOWER(CONCAT(
        SUBSTR(hexStr, 1, 8), '-',
        SUBSTR(hexStr, 9, 4), '-',
        SUBSTR(hexStr, 13, 4), '-',
        SUBSTR(hexStr, 17, 4), '-',
        SUBSTR(hexStr, 21)
    ));
END$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS users (
                                     uuid BINARY(16) PRIMARY KEY,
                                     email VARCHAR(255) NOT NULL UNIQUE,
                                     password_hash VARCHAR(255) NOT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS refresh_tokens (
                                     user_uuid BINARY(16) NOT NULL,
                                     token_hash CHAR(64) NOT NULL,
                                     expires_at DATETIME NOT NULL,
                                     revoked TINYINT(1) NOT NULL DEFAULT 0,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     PRIMARY KEY (token_hash),
                                     INDEX idx_refresh_user_uuid (user_uuid),
                                     CONSTRAINT fk_refresh_tokens_user
                                         FOREIGN KEY (user_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE
);

INSERT INTO users (uuid, email, password_hash) VALUES (
                                                    UUID_TO_BIN('00000000-0000-4000-8000-000000000001'),
                                                    'user@example.com',
                                                    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' -- bcrypt('password123')
                                                ) ON DUPLICATE KEY UPDATE email = email;
