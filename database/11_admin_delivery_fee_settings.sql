-- Coffee Corner Aura: add an editable home delivery fee to Store Settings.
-- Safe to run more than once. Run this against the application's selected database.

SET @cc_has_delivery_fee := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'settings'
      AND COLUMN_NAME = 'delivery_fee'
);

SET @cc_delivery_fee_sql := IF(
    @cc_has_delivery_fee = 0,
    'ALTER TABLE `settings` ADD COLUMN `delivery_fee` DECIMAL(10,2) NULL DEFAULT NULL AFTER `exchange_rate`',
    'SELECT 1'
);

PREPARE cc_delivery_fee_stmt FROM @cc_delivery_fee_sql;
EXECUTE cc_delivery_fee_stmt;
DEALLOCATE PREPARE cc_delivery_fee_stmt;

-- NULL preserves the legacy .env fallback until an administrator saves a value.
