-- Sarana 01: Google Sign-In account-linking support
-- Safe to import more than once on MariaDB 10.4+.
-- This adds a nullable Google subject ID to existing users; it does not alter
-- roles, passwords, customer data, order history, or create new accounts.

SET @google_id_column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'google_id'
);

SET @google_id_column_ddl := IF(
    @google_id_column_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `google_id` VARCHAR(255) NULL AFTER `email`',
    'SELECT 1'
);
PREPARE google_id_column_stmt FROM @google_id_column_ddl;
EXECUTE google_id_column_stmt;
DEALLOCATE PREPARE google_id_column_stmt;

SET @google_id_index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'users_google_id_unique'
);

SET @google_id_index_ddl := IF(
    @google_id_index_exists = 0,
    'ALTER TABLE `users` ADD UNIQUE KEY `users_google_id_unique` (`google_id`)',
    'SELECT 1'
);
PREPARE google_id_index_stmt FROM @google_id_index_ddl;
EXECUTE google_id_index_stmt;
DEALLOCATE PREPARE google_id_index_stmt;

SELECT 'Google Sign-In database preparation complete.' AS result;
