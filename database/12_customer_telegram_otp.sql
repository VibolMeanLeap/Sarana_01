-- SK Coffee Aura: Telegram OTP customer registration.
-- Safe to import repeatedly on MariaDB 10.4+.
-- Run after the base demo schema and existing Google Sign-In SQL upgrade (05).
-- Existing users are preserved; only nullable Telegram identity columns are added.

SET @telegram_chat_column_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'telegram_chat_id'
);
SET @telegram_chat_column_ddl := IF(
    @telegram_chat_column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `telegram_chat_id` VARCHAR(64) NULL',
    'SELECT 1'
);
PREPARE telegram_chat_column_stmt FROM @telegram_chat_column_ddl;
EXECUTE telegram_chat_column_stmt;
DEALLOCATE PREPARE telegram_chat_column_stmt;

SET @telegram_verified_column_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'telegram_verified_at'
);
SET @telegram_verified_column_ddl := IF(
    @telegram_verified_column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `telegram_verified_at` TIMESTAMP NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE telegram_verified_column_stmt FROM @telegram_verified_column_ddl;
EXECUTE telegram_verified_column_stmt;
DEALLOCATE PREPARE telegram_verified_column_stmt;

SET @telegram_user_index_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'users_telegram_chat_id_unique'
);
SET @telegram_user_index_ddl := IF(
    @telegram_user_index_exists = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `users_telegram_chat_id_unique` (`telegram_chat_id`)',
    'SELECT 1'
);
PREPARE telegram_user_index_stmt FROM @telegram_user_index_ddl;
EXECUTE telegram_user_index_stmt;
DEALLOCATE PREPARE telegram_user_index_stmt;

CREATE TABLE IF NOT EXISTS `customer_telegram_registrations` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `telegram_chat_id` VARCHAR(64) DEFAULT NULL,
    `link_token_hash` CHAR(64) DEFAULT NULL,
    `link_expires_at` TIMESTAMP NULL DEFAULT NULL,
    `otp_hash` VARCHAR(255) DEFAULT NULL,
    `otp_expires_at` TIMESTAMP NULL DEFAULT NULL,
    `otp_attempts` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `customer_telegram_registrations_phone_unique` (`phone`),
    UNIQUE KEY `customer_telegram_registrations_email_unique` (`email`),
    UNIQUE KEY `customer_telegram_registrations_chat_unique` (`telegram_chat_id`),
    UNIQUE KEY `customer_telegram_registrations_link_unique` (`link_token_hash`),
    KEY `customer_telegram_registrations_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Telegram OTP customer registration database preparation complete.' AS result;
