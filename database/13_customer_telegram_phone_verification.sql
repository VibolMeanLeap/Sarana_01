-- SK Coffee Aura: require Telegram contact/phone verification before OTP.
-- Safe to run repeatedly on MariaDB/MySQL. Does not modify existing customer accounts.

SET @telegram_phone_verified_column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'customer_telegram_registrations'
      AND COLUMN_NAME = 'telegram_phone_verified_at'
);

SET @telegram_phone_verified_column_ddl := IF(
    @telegram_phone_verified_column_exists = 0,
    'ALTER TABLE `customer_telegram_registrations` ADD COLUMN `telegram_phone_verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `telegram_chat_id`',
    'SELECT 1'
);

PREPARE telegram_phone_verified_column_stmt FROM @telegram_phone_verified_column_ddl;
EXECUTE telegram_phone_verified_column_stmt;
DEALLOCATE PREPARE telegram_phone_verified_column_stmt;

SELECT 'Telegram phone ownership verification database update complete.' AS result;
