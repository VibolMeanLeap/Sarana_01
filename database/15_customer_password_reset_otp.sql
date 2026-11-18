-- SK Coffee: short-lived Telegram OTP password recovery challenges.
-- Apply once to the same database used by Laravel. This does not change customer/order rows.
CREATE TABLE IF NOT EXISTS `customer_password_reset_otps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `consumed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_password_reset_user_consumed_idx` (`user_id`, `consumed_at`),
  KEY `customer_password_reset_expires_idx` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
