-- Coffee Corner Aura: Telegram order notifications + auditable fulfillment workflow
-- Compatible with the existing SQL-first schema (MariaDB 10.4+).
-- BACK UP THE DATABASE BEFORE RUNNING THIS SCRIPT.
-- This script is additive: it does not drop, truncate, or replace existing tables.

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `history_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT(20) NOT NULL,
    `event_type` VARCHAR(40) NOT NULL DEFAULT 'status_changed',
    `from_status` VARCHAR(30) DEFAULT NULL,
    `to_status` VARCHAR(30) DEFAULT NULL,
    `actor_type` VARCHAR(30) NOT NULL DEFAULT 'system',
    `actor_id` BIGINT(20) DEFAULT NULL,
    `telegram_user_id` VARCHAR(32) DEFAULT NULL,
    `telegram_username` VARCHAR(100) DEFAULT NULL,
    `actor_label` VARCHAR(255) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`history_id`),
    KEY `idx_order_status_history_order_created` (`order_id`, `created_at`),
    KEY `idx_order_status_history_actor` (`actor_type`, `actor_id`),
    CONSTRAINT `fk_order_status_history_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `telegram_order_notifications` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT(20) NOT NULL,
    `chat_id` VARCHAR(64) NOT NULL,
    `message_id` BIGINT(20) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
    `last_status` VARCHAR(30) DEFAULT NULL,
    `last_error` TEXT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_telegram_order_notifications_order` (`order_id`),
    KEY `idx_telegram_order_notifications_status` (`status`),
    CONSTRAINT `fk_telegram_order_notifications_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reconcile the known legacy demo inconsistency where a delivery is already
-- marked delivered but its related order is not completed. No history is
-- fabricated for this old demo data; new transitions are audited from now on.
UPDATE `orders` AS o
INNER JOIN `delivery` AS d ON d.`order_id` = o.`order_id`
SET o.`order_status` = 'completed'
WHERE LOWER(COALESCE(d.`status`, '')) = 'delivered'
  AND o.`order_status` NOT IN ('completed', 'cancelled');
