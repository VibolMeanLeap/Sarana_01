# Sarana 01 — Webstore Customer Experience & Support Patch

This is an overlay patch for the existing Sarana 01 Laravel repository. It does not create a new project and does not replace `.env`, `.git`, `vendor`, or `node_modules`.

## What changed

### Storefront footer
- Removed the unused **Subscriptions** link.
- Rebuilt the footer with a consistent shop identity and responsive layout.
- Added working links for Shop, About Us, Track an Order, Shipping Information, Returns & Order Issues, and Contact Support.
- Removed placeholder `href="#"` links.
- Store name, logo, address, phone and exchange-rate display continue to use the saved Store Profile.

### Customer-service pages
- `/store/about` — shop overview using the current Store Profile.
- `/store/shipping` — delivery guidance and the current `WEB_DELIVERY_FEE` value.
- `/store/returns` — guidance for missing, incorrect or damaged orders and payment adjustments.
- `/store/contact` — validated customer-enquiry form with name, email, optional phone/order reference, topic and message.

### Working contact workflow
1. A customer submits the storefront contact form.
2. Laravel validates the form, applies a request throttle and stores the enquiry in `store_contact_messages`.
3. If Telegram is configured, Laravel sends an alert to `TELEGRAM_GROUP_CHAT_ID` with an admin-inbox link.
4. If Telegram is temporarily unavailable, the enquiry remains saved in the database; the form submission is not lost.
5. Administrators can open **Customer enquiries** in the admin sidebar, search/filter messages, view the contact details, add an internal note, and set the status to New, In Progress or Resolved.

Customer enquiry messages are restricted to the administrator area. The contact form explicitly warns customers not to submit passwords, card details, bank PINs or other secrets.

## Installation

1. Back up the current project and database.
2. Extract this ZIP directly into the existing repository root, for example `D:\26 - Sarana\Sarana_01`.
3. Allow the included files to replace their matching paths. Keep your `.env`, `.git`, `vendor` and `node_modules` unchanged.
4. In phpMyAdmin, select the existing `sarona01` database and import:

   `database/08_storefront_customer_service.sql`

   The SQL uses `CREATE TABLE IF NOT EXISTS` and does not alter or delete existing customer/order data.
5. Clear Laravel's cached configuration, routes and views:

   ```bash
   php artisan optimize:clear
   ```
6. Verify public and admin routes:

   ```bash
   php artisan route:list --path=store
   php artisan route:list --path=admin/customer-service
   ```

The package also includes an idempotent Laravel migration at `database/migrations/2026_11_08_000001_create_store_contact_messages_table.php`. If you use the provided SQL import, the migration checks whether the table already exists before creating it.

## Notes
- No new `.env` variable is required. Customer enquiries use the existing `TELEGRAM_BOT_TOKEN` and `TELEGRAM_GROUP_CHAT_ID` configuration when available.
- No Composer or npm installation is required.
- Do not use `php artisan migrate:fresh`.
- The Returns page is written as practical store guidance, not an automated refund promise. Refund approvals and timing remain subject to shop review and the payment provider.
- Live database and Telegram delivery tests must be completed in your local environment.
