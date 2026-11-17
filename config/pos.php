<?php

return [
    /*
    | POS pricing permissions.
    | Cashiers receive no discretionary discount by default. Administrators
    | may discount up to the configured cap; every non-zero discount needs a
    | reason recorded in order_status_history.
    */
    'admin_max_discount_percent' => env('POS_ADMIN_MAX_DISCOUNT_PERCENT', 100),
    'cashier_max_discount_percent' => env('POS_CASHIER_MAX_DISCOUNT_PERCENT', 0),
];
