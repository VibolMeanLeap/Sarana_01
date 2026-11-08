-- Sarana 01 POS delivery workflow
-- MariaDB 10.4+; additive and safe to run more than once.
-- Back up the database before importing.

-- Record where staff received an order (walk-in, Facebook, phone, referral, etc.).
SET @has_order_channel := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'order_channel'
);
SET @add_order_channel_sql := IF(
    @has_order_channel = 0,
    "ALTER TABLE `orders` ADD COLUMN `order_channel` VARCHAR(30) NOT NULL DEFAULT 'walk_in' AFTER `order_source`",
    'SELECT 1'
);
PREPARE add_order_channel_stmt FROM @add_order_channel_sql;
EXECUTE add_order_channel_stmt;
DEALLOCATE PREPARE add_order_channel_stmt;

-- Add Cash on Delivery as a POS payment method if it does not already exist.
INSERT INTO `payment_method` (`payment_name`)
SELECT 'Cash on Delivery'
WHERE NOT EXISTS (
    SELECT 1 FROM `payment_method`
    WHERE LOWER(TRIM(`payment_name`)) IN ('cash on delivery', 'cash-on-delivery', 'cod')
);

-- Existing POS orders remain classified as walk-in unless staff create a new
-- order using one of the explicit order channels in the POS screen.
