-- Coffee Corner Aura: Stock / Non-stock product type and stock trigger upgrade.
-- Run once in phpMyAdmin after selecting your existing application database.
-- This script does not drop or rebuild tables and is safe to re-run.

START TRANSACTION;

-- Legacy product_type values such as Drink/Food were labels, while the application
-- previously tracked stock for every product. Preserve that behavior as Stock.
-- Only clear non-stock/service terms are mapped to Non-stock.
UPDATE product
SET product_type = CASE
    WHEN LOWER(TRIM(COALESCE(product_type, ''))) IN
        ('non-stock', 'non stock', 'nonstock', 'no stock', 'service', 'digital', 'untracked')
        THEN 'non-stock'
    ELSE 'stock'
END;

COMMIT;

-- Recreate the insert trigger so stock is deducted only for Stock products.
DROP TRIGGER IF EXISTS after_order_item_insert_final;

DELIMITER $$
CREATE TRIGGER after_order_item_insert_final
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    DECLARE current_qty INT DEFAULT 0;
    DECLARE current_cashier_id BIGINT DEFAULT NULL;
    DECLARE current_product_type VARCHAR(100) DEFAULT 'stock';

    SELECT COALESCE(qty, 0), LOWER(TRIM(COALESCE(product_type, 'stock')))
      INTO current_qty, current_product_type
      FROM product
     WHERE product_id = NEW.product_id
     LIMIT 1;

    SELECT cashier_id
      INTO current_cashier_id
      FROM orders
     WHERE order_id = NEW.order_id
     LIMIT 1;

    IF current_product_type NOT IN ('non-stock', 'non stock', 'nonstock', 'no stock', 'service', 'digital', 'untracked') THEN
        IF COALESCE(NEW.quantity, 0) > current_qty THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Insufficient stock for this sale.';
        ELSE
            UPDATE product
               SET qty = qty - NEW.quantity
             WHERE product_id = NEW.product_id;

            IF current_cashier_id IS NOT NULL AND EXISTS (
                SELECT 1 FROM users WHERE user_id = current_cashier_id
            ) THEN
                INSERT INTO stock_logs (product_id, user_id, type, quantity, reason, note)
                VALUES (NEW.product_id, current_cashier_id, 'OUT', NEW.quantity, 'Sale',
                        CONCAT('Sale from order #', NEW.order_id));
            ELSE
                INSERT INTO stock_logs (product_id, user_id, type, quantity, reason, note)
                VALUES (NEW.product_id, NULL, 'OUT', NEW.quantity, 'Sale',
                        CONCAT('Sale from order #', NEW.order_id));
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Optional verification:
-- SELECT product_type, COUNT(*) AS products FROM product GROUP BY product_type;
-- SHOW TRIGGERS LIKE 'order_items';
