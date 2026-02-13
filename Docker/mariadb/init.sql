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
                                     name VARCHAR(255) NOT NULL,
                                     age INT NOT NULL,
                                     login VARCHAR(100) NOT NULL UNIQUE,
                                     email VARCHAR(255) NULL UNIQUE,
                                     password_hash VARCHAR(255) NOT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users_avatars (
                                     id BIGINT AUTO_INCREMENT PRIMARY KEY,
                                     user_uuid BINARY(16) NOT NULL,
                                     original_name VARCHAR(255) NOT NULL,
                                     saved_name VARCHAR(255) NOT NULL,
                                     full_path VARCHAR(1024) NOT NULL,
                                     relative_path VARCHAR(1024) NOT NULL,
                                     size BIGINT NOT NULL,
                                     extension VARCHAR(20) NOT NULL,
                                     uploaded TINYINT(1) NOT NULL DEFAULT 0,
                                     errors TEXT NULL,
                                     status TINYINT(1) NOT NULL DEFAULT 1,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     INDEX idx_users_avatars_user_uuid (user_uuid),
                                     INDEX idx_users_avatars_status (status),
                                     CONSTRAINT fk_users_avatars_user
                                         FOREIGN KEY (user_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS feed_items (
                                     uuid BINARY(16) PRIMARY KEY,
                                     user_uuid BINARY(16) NOT NULL,
                                     wall_user_uuid BINARY(16) NOT NULL,
                                     type VARCHAR(20) NOT NULL,
                                     text TEXT NULL,
                                     payload_json LONGTEXT NULL,
                                     likes_count INT NOT NULL DEFAULT 0,
                                     comments_count INT NOT NULL DEFAULT 0,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     INDEX idx_feed_items_created_at (created_at),
                                     INDEX idx_feed_items_user_uuid (user_uuid),
                                     INDEX idx_feed_items_wall_user_uuid (wall_user_uuid),
                                     CONSTRAINT fk_feed_items_user
                                         FOREIGN KEY (user_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE,
                                     CONSTRAINT fk_feed_items_wall_user
                                         FOREIGN KEY (wall_user_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feed_item_media (
                                     uuid BINARY(16) PRIMARY KEY,
                                     feed_item_uuid BINARY(16) NOT NULL,
                                     original_name VARCHAR(255) NOT NULL,
                                     saved_name VARCHAR(255) NOT NULL,
                                     full_path VARCHAR(1024) NULL,
                                     relative_path VARCHAR(1024) NOT NULL,
                                     size BIGINT NOT NULL DEFAULT 0,
                                     extension VARCHAR(20) NOT NULL,
                                     uploaded TINYINT(1) NOT NULL DEFAULT 1,
                                     errors TEXT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     INDEX idx_feed_media_item_uuid (feed_item_uuid),
                                     CONSTRAINT fk_feed_item_media_item
                                         FOREIGN KEY (feed_item_uuid) REFERENCES feed_items(uuid)
                                         ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feed_quiz_answers (
                                     uuid BINARY(16) PRIMARY KEY,
                                     feed_item_uuid BINARY(16) NOT NULL,
                                     user_uuid BINARY(16) NOT NULL,
                                     answer_id CHAR(1) NOT NULL,
                                     is_correct TINYINT(1) NOT NULL DEFAULT 0,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     UNIQUE KEY uniq_feed_quiz_answer (feed_item_uuid, user_uuid),
                                     INDEX idx_feed_quiz_answers_user_uuid (user_uuid),
                                     CONSTRAINT fk_feed_quiz_answers_item
                                         FOREIGN KEY (feed_item_uuid) REFERENCES feed_items(uuid)
                                         ON DELETE CASCADE,
                                     CONSTRAINT fk_feed_quiz_answers_user
                                         FOREIGN KEY (user_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feed_item_delete_audit (
                                     uuid BINARY(16) PRIMARY KEY,
                                     feed_item_uuid BINARY(16) NOT NULL,
                                     deleted_by_uuid BINARY(16) NOT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     INDEX idx_feed_delete_audit_item (feed_item_uuid),
                                     INDEX idx_feed_delete_audit_user (deleted_by_uuid),
                                     CONSTRAINT fk_feed_delete_audit_item
                                         FOREIGN KEY (feed_item_uuid) REFERENCES feed_items(uuid)
                                         ON DELETE CASCADE,
                                     CONSTRAINT fk_feed_delete_audit_user
                                         FOREIGN KEY (deleted_by_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feed_comments (
                                     uuid BINARY(16) PRIMARY KEY,
                                     feed_item_uuid BINARY(16) NOT NULL,
                                     user_uuid BINARY(16) NOT NULL,
                                     parent_uuid BINARY(16) NULL,
                                     text TEXT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     INDEX idx_feed_comments_item (feed_item_uuid),
                                     INDEX idx_feed_comments_user (user_uuid),
                                     INDEX idx_feed_comments_parent (parent_uuid),
                                     CONSTRAINT fk_feed_comments_item
                                         FOREIGN KEY (feed_item_uuid) REFERENCES feed_items(uuid)
                                         ON DELETE CASCADE,
                                     CONSTRAINT fk_feed_comments_user
                                         FOREIGN KEY (user_uuid) REFERENCES users(uuid)
                                         ON DELETE CASCADE,
                                     CONSTRAINT fk_feed_comments_parent
                                         FOREIGN KEY (parent_uuid) REFERENCES feed_comments(uuid)
                                         ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS feed_comment_media (
                                     uuid BINARY(16) PRIMARY KEY,
                                     comment_uuid BINARY(16) NOT NULL,
                                     original_name VARCHAR(255) NOT NULL,
                                     saved_name VARCHAR(255) NOT NULL,
                                     full_path VARCHAR(1024) NULL,
                                     relative_path VARCHAR(1024) NOT NULL,
                                     size BIGINT NOT NULL DEFAULT 0,
                                     extension VARCHAR(20) NOT NULL,
                                     uploaded TINYINT(1) NOT NULL DEFAULT 1,
                                     errors TEXT NULL,
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     INDEX idx_feed_comment_media_comment (comment_uuid),
                                     CONSTRAINT fk_feed_comment_media_comment
                                         FOREIGN KEY (comment_uuid) REFERENCES feed_comments(uuid)
                                         ON DELETE CASCADE
);
