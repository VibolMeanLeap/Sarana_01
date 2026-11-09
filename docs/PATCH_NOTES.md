# Patch notes

## 2026-11-07 – Coffee Corner Aura review patch

### Added
- Website ordering controller and shop/cart/checkout/order views.
- PayWay payment model and service.
- Order status workflow controller.
- Additive MariaDB schema upgrade and read-only stock audit query.

### Updated
- POS checkout payment-state handling.
- Order and order-item models/controllers.
- Product stock adjustments and append-only stock-log writing.
- Cashier order queue and administrator order workflow controls.
- COGS reporting to use the order-item cost snapshot.
- PayWay callback CSRF exception and service configuration.
- PayWay Check Transaction HMAC now includes `req_time` in ABA's documented field order.
- Database foreign-key rules that could cascade-delete historical records are changed to `SET NULL` or `RESTRICT`; staff accounts with history are deactivated instead of deleted.

### Safety notes
- No `.env`, credentials, production keys, database dumps, vendor directory, or user data are included in this patch ZIP.
- No automatic stock correction or destructive migration is included.
- Card payments and paid-order refunds are not implemented.

### 2026-11-07 – Patch v3: ngrok callback and Order tab repair

- Added `PAYWAY_CALLBACK_URL` configuration so the ABA callback can explicitly use the HTTPS ngrok endpoint instead of a localhost URL generated from the current browser request.
- Updated both POS and website KHQR creation to use the configured callback URL.
- Documented Windows ngrok installation, tunnel startup, `.env` values, and callback endpoint.
- Standardized the administrator Order tab to `/admin/orders` and `admin.orders.*` route names; `/admin/olders` now redirects for backward compatibility.
- Fixed Order tab search, payment/order status filters, source/date filters, summary meanings, payment-vs-workflow status display, safe delete visibility, and filtered order-item navigation.
- Updated order create/edit forms and administrator navigation/payment return links to the canonical Order routes.
- Added filtering by order ID to the Order Items list.

## 2026-11-09 — COD cash collection and customer inbox

- Added a cash collection confirmation to the POS delivery fulfillment workflow for Cash on Delivery orders.
- COD remains unpaid until the final delivery handoff is confirmed with the exact amount received; collector/time/note and a dedicated payment event are retained.
- Kept sales revenue tied to the paid-order reporting flow; no duplicate manual sales income transaction is inserted.
- Added a private Store Account inbox with automatic enquiry acknowledgement, customer follow-up replies, administrator replies and unread navigation badges.
- Added additive MariaDB scripts `09_cod_cash_collection.sql` and `10_store_inbox_replies.sql`.
- See `COD_AND_CUSTOMER_INBOX_PATCH.md` for installation, test steps, ownership rules and known limitations.
