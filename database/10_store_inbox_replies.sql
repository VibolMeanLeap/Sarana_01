-- Coffee Corner Aura: customer/store conversation replies
-- BACK UP THE DATABASE BEFORE RUNNING THIS SCRIPT.
-- The original store_contact_messages table and its existing enquiry records are untouched.

CREATE TABLE IF NOT EXISTS `store_contact_replies` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_message_id` BIGINT(20) UNSIGNED NOT NULL,
    `sender_type` VARCHAR(20) NOT NULL,
    `sender_user_id` BIGINT(20) DEFAULT NULL,
    `body` TEXT NOT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_store_contact_reply_thread` (`contact_message_id`, `created_at`, `id`),
    KEY `idx_store_contact_reply_unread` (`sender_type`, `read_at`),
    CONSTRAINT `fk_store_contact_replies_message`
        FOREIGN KEY (`contact_message_id`) REFERENCES `store_contact_messages` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
