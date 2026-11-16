-- Customer-account archive/release support for Coffee Corner Aura.
-- Run once in the same MariaDB/MySQL database used by Laravel.
-- Existing phone values and customer/order records are not changed.

ALTER TABLE `users`
    MODIFY COLUMN `phone` VARCHAR(20) NULL DEFAULT NULL;

SELECT 'Customer account phone-release schema update complete.' AS result;
