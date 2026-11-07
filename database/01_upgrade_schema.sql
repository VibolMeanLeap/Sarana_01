-- Coffee Corner Aura - safe schema upgrade
-- Target: MariaDB 10.4+ (matches the server version in the supplied SQL dump)
-- Run this against the existing application database (normally `sarona`).
-- Back up the database before importing this file.
-- This script is additive; it does not drop tables or delete existing business data.

SET NAMES utf8mb4;

-- 1) Order workflow/source fields. Add as nullable first so existing rows can
--    be backfilled based on their current payment state, then set safe defaults.
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `order_status` varchar(30) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `order_source` varchar(20) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `customer_note` varchar(500) DEFAULT NULL;

UPDATE `orders`
SET `order_status` = CASE WHEN COALESCE(`is_paid`, 0) = 1 THEN 'completed' ELSE 'pending' END
WHERE `order_status` IS NULL OR TRIM(`order_status`) = '';

UPDATE `orders`
SET `order_source` = 'pos'
WHERE `order_source` IS NULL OR TRIM(`order_source`) = '';

ALTER TABLE `orders`
    MODIFY COLUMN `order_status` varchar(30) NOT NULL DEFAULT 'pending',
    MODIFY COLUMN `order_source` varchar(20) NOT NULL DEFAULT 'pos';

-- 2) Preserve the cost of each item at the time it is sold.
--    Existing rows are backfilled from the current product.cost. This is only
--    the best available estimate for historic sales; it cannot recreate old costs.
ALTER TABLE `order_items`
    ADD COLUMN IF NOT EXISTS `unit_cost` decimal(10,2) NULL DEFAULT NULL;

UPDATE `order_items` AS oi
LEFT JOIN `product` AS p ON p.`product_id` = oi.`product_id`
SET oi.`unit_cost` = COALESCE(p.`cost`, 0)
WHERE oi.`unit_cost` IS NULL;

ALTER TABLE `order_items`
    MODIFY COLUMN `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00;

-- 3) Payment table required by the included Payment model and PayWay controller.
--    On the newer supplied database, this table already exists; CREATE IF NOT EXISTS
--    leaves its current payment rows untouched.
CREATE TABLE IF NOT EXISTS `payments` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `order_id` bigint(20) NOT NULL,
    `provider` varchar(40) NOT NULL DEFAULT 'aba_payway',
    `tran_id` varchar(20) DEFAULT NULL,
    `amount` decimal(12,2) NOT NULL,
    `currency` char(3) NOT NULL DEFAULT 'USD',
    `status` varchar(30) NOT NULL DEFAULT 'pending',
    `qr_string` longtext DEFAULT NULL,
    `qr_image` longtext DEFAULT NULL,
    `abapay_deeplink` text DEFAULT NULL,
    `app_store` varchar(500) DEFAULT NULL,
    `play_store` varchar(500) DEFAULT NULL,
    `apv` varchar(30) DEFAULT NULL,
    `bank_ref` varchar(100) DEFAULT NULL,
    `expires_at` datetime DEFAULT NULL,
    `raw_response` longtext DEFAULT NULL,
    `callback_payload` longtext DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payments_order_status` (`order_id`, `status`),
    KEY `idx_payments_provider_tran` (`provider`, `tran_id`),
    CONSTRAINT `fk_payments_orders_patch` FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Add indexes to a pre-existing payments table only when they are absent.
--    Dynamic DDL keeps this file re-runnable against both supplied schemas.
SET @has_index_order_status := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'payments'
      AND index_name = 'idx_payments_order_status'
);
SET @ddl_order_status_index := IF(
    @has_index_order_status = 0,
    'ALTER TABLE `payments` ADD INDEX `idx_payments_order_status` (`order_id`, `status`)',
    'SELECT 1'
);
PREPARE stmt_order_status_index FROM @ddl_order_status_index;
EXECUTE stmt_order_status_index;
DEALLOCATE PREPARE stmt_order_status_index;

SET @has_index_provider_tran := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'payments'
      AND index_name = 'idx_payments_provider_tran'
);
SET @ddl_provider_tran_index := IF(
    @has_index_provider_tran = 0,
    'ALTER TABLE `payments` ADD INDEX `idx_payments_provider_tran` (`provider`, `tran_id`)',
    'SELECT 1'
);
PREPARE stmt_provider_tran_index FROM @ddl_provider_tran_index;
EXECUTE stmt_provider_tran_index;
DEALLOCATE PREPARE stmt_provider_tran_index;

-- 5) Add the payment/order FK to an existing payments table only if it is absent
--    and there are no orphaned payment rows. If orphan rows exist, inspect them
--    manually instead of deleting or silently rewriting payment history.
SET @has_payment_order_fk := (
    SELECT COUNT(*) FROM information_schema.key_column_usage
    WHERE table_schema = DATABASE()
      AND table_name = 'payments'
      AND column_name = 'order_id'
      AND referenced_table_name = 'orders'
);
SET @orphan_payment_count := (
    SELECT COUNT(*) FROM `payments` p
    LEFT JOIN `orders` o ON o.`order_id` = p.`order_id`
    WHERE o.`order_id` IS NULL
);
SET @ddl_payment_order_fk := IF(
    @has_payment_order_fk = 0 AND @orphan_payment_count = 0,
    'ALTER TABLE `payments` ADD CONSTRAINT `fk_payments_orders_patch` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON UPDATE CASCADE ON DELETE RESTRICT',
    'SELECT 1'
);
PREPARE stmt_payment_order_fk FROM @ddl_payment_order_fk;
EXECUTE stmt_payment_order_fk;
DEALLOCATE PREPARE stmt_payment_order_fk;

-- 6) Preserve audit history when staff/customer accounts or orders are removed.
--    The supplied newer dump used ON DELETE CASCADE for several audit links.
--    Convert only existing CASCADE rules; other FK definitions are left alone.
--    User references are nullable in both supplied database layouts, so SET NULL
--    keeps the order/purchase/stock/finance record without retaining a deleted user.

-- cash_transactions.user_id -> users.user_id
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='cash_transactions' AND kcu.COLUMN_NAME='user_id' AND kcu.REFERENCED_TABLE_NAME='users' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `cash_transactions` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- customer.user_id -> users.user_id
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='customer' AND kcu.COLUMN_NAME='user_id' AND kcu.REFERENCED_TABLE_NAME='users' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `customer` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- orders.cashier_id -> users.user_id
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='orders' AND kcu.COLUMN_NAME='cashier_id' AND kcu.REFERENCED_TABLE_NAME='users' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `orders` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- purchases.user_id -> users.user_id
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='purchases' AND kcu.COLUMN_NAME='user_id' AND kcu.REFERENCED_TABLE_NAME='users' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `purchases` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- stock_logs.user_id -> users.user_id
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='stock_logs' AND kcu.COLUMN_NAME='user_id' AND kcu.REFERENCED_TABLE_NAME='users' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `stock_logs` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- payments.order_id -> orders.order_id
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='payments' AND kcu.COLUMN_NAME='order_id' AND kcu.REFERENCED_TABLE_NAME='orders' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `payments` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE RESTRICT ON UPDATE CASCADE'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- users.role_id -> role.role_id (deleting a role must not delete its users)
SET @fk_name := (SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
    JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_SCHEMA=kcu.CONSTRAINT_SCHEMA AND rc.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME AND rc.TABLE_NAME=kcu.TABLE_NAME
    WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.TABLE_NAME='users' AND kcu.COLUMN_NAME='role_id' AND kcu.REFERENCED_TABLE_NAME='role' AND rc.DELETE_RULE='CASCADE' LIMIT 1);
SET @ddl := IF(@fk_name IS NULL, 'SELECT 1', CONCAT('ALTER TABLE `users` DROP FOREIGN KEY `', @fk_name, '`, ADD CONSTRAINT `', @fk_name, '` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE RESTRICT ON UPDATE CASCADE'));
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- End of additive patch. Review row counts and stock audit separately.
