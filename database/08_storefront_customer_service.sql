-- Sarana 01: customer-service enquiry inbox
-- Additive and safe to import into an existing MariaDB/MySQL database.

CREATE TABLE IF NOT EXISTS `store_contact_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `phone` VARCHAR(40) DEFAULT NULL,
    `order_reference` VARCHAR(40) DEFAULT NULL,
    `topic` VARCHAR(30) NOT NULL DEFAULT 'other',
    `message` TEXT NOT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'new',
    `admin_note` TEXT DEFAULT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `handled_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
    `handled_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_store_contact_user` (`user_id`),
    KEY `idx_store_contact_order` (`order_reference`),
    KEY `idx_store_contact_topic` (`topic`),
    KEY `idx_store_contact_status` (`status`),
    KEY `idx_store_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
