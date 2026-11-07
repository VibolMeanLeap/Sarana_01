-- Read-only stock audit. This query reports products whose current qty does not
-- equal the net quantity recorded in stock_logs. It DOES NOT change any stock.
-- A discrepancy can also mean opening stock was never logged or old movements
-- were edited/deleted; investigate before applying a manual adjustment.

SELECT
    p.product_id,
    p.product_name,
    p.qty AS current_product_qty,
    COALESCE(SUM(
        CASE
            WHEN UPPER(sl.type) = 'IN' THEN COALESCE(sl.quantity, 0)
            WHEN UPPER(sl.type) = 'OUT' THEN -COALESCE(sl.quantity, 0)
            ELSE 0
        END
    ), 0) AS net_logged_qty,
    p.qty - COALESCE(SUM(
        CASE
            WHEN UPPER(sl.type) = 'IN' THEN COALESCE(sl.quantity, 0)
            WHEN UPPER(sl.type) = 'OUT' THEN -COALESCE(sl.quantity, 0)
            ELSE 0
        END
    ), 0) AS unexplained_difference
FROM product p
LEFT JOIN stock_logs sl ON sl.product_id = p.product_id
GROUP BY p.product_id, p.product_name, p.qty
HAVING unexplained_difference <> 0
ORDER BY ABS(unexplained_difference) DESC, p.product_name;
