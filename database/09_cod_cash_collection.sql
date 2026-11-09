-- Coffee Corner Aura: auditable Cash-on-Delivery collection fields
-- Compatible with MariaDB 10.4+ and MySQL versions supporting ADD COLUMN IF NOT EXISTS.
-- BACK UP THE DATABASE BEFORE RUNNING THIS SCRIPT.
-- Additive only: existing order/payment data is retained.

ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `cod_collected_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_status`,
    ADD COLUMN IF NOT EXISTS `cod_collected_at` DATETIME DEFAULT NULL AFTER `cod_collected_amount`,
    ADD COLUMN IF NOT EXISTS `cod_collected_by` BIGINT(20) DEFAULT NULL AFTER `cod_collected_at`,
    ADD COLUMN IF NOT EXISTS `cod_collection_note` VARCHAR(500) DEFAULT NULL AFTER `cod_collected_by`;
