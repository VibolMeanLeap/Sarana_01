-- POS item-specific discount upgrade (MariaDB 10.4+ / MySQL)
-- Back up the database before running. This is additive and preserves old orders.
-- Run this SQL OR run the matching Laravel migration; it is safe if both are run.

ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) NULL DEFAULT NULL AFTER `discount`;

ALTER TABLE `orders`
    MODIFY COLUMN `discount` DECIMAL(10,6) NULL DEFAULT NULL;

ALTER TABLE `order_items`
    ADD COLUMN IF NOT EXISTS `discount_percent` DECIMAL(5,2) NULL DEFAULT NULL AFTER `price`,
    ADD COLUMN IF NOT EXISTS `discount_amount` DECIMAL(10,2) NULL DEFAULT NULL AFTER `discount_percent`;
