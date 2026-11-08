-- Sarana 01: customer review moderation support
-- Safe for the supplied MariaDB 10.4 database. Existing reviews default to published
-- so historical demo reviews remain visible; new customer-submitted reviews are created
-- with is_approved = 0 and require administrator approval.

ALTER TABLE `reviews`
    ADD COLUMN IF NOT EXISTS `is_approved` TINYINT(1) NOT NULL DEFAULT 1 AFTER `comment`;
