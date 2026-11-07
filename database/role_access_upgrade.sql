-- Coffee Corner role-access patch for an existing imported sarona database.
-- Run this in phpMyAdmin if you already imported an earlier demo dump.
-- The index check handles both the original UNIQUE role_id index and the newer
-- non-unique idx_users_role_id index. It is safe to run more than once.

SET @legacy_role_unique_index = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'role_id'
      AND non_unique = 0
);
SET @role_index_already_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'idx_users_role_id'
);

SET @role_index_sql = CASE
    WHEN @legacy_role_unique_index > 0 AND @role_index_already_exists = 0
        THEN 'ALTER TABLE `users` DROP INDEX `role_id`, ADD INDEX `idx_users_role_id` (`role_id`)'
    WHEN @legacy_role_unique_index > 0
        THEN 'ALTER TABLE `users` DROP INDEX `role_id`'
    ELSE 'SELECT 1 AS result'
END;
PREPARE role_index_statement FROM @role_index_sql;
EXECUTE role_index_statement;
DEALLOCATE PREPARE role_index_statement;

INSERT INTO `users`
    (`name`, `email`, `password`, `phone`, `username`, `role_id`, `status`)
SELECT
    'Demo Customer',
    'customer@example.test',
    '$2y$12$am5cUyyidFhVI5t66Xyk2.uMEBfiwvId8atTjYf77fyY.mavsuPLS',
    '0120000001',
    'demo.customer',
    `role`.`role_id`,
    'active'
FROM `role`
WHERE LOWER(`role`.`role_name`) = 'customer'
  AND NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'demo.customer');

INSERT INTO `customer` (`user_id`, `name`, `phone`)
SELECT `users`.`user_id`, 'Demo Customer', '0120000001'
FROM `users`
WHERE `users`.`username` = 'demo.customer'
  AND NOT EXISTS (SELECT 1 FROM `customer` WHERE `customer`.`user_id` = `users`.`user_id`);
