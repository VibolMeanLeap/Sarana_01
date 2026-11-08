# Sarana 01 — Finance, Store URL and Customer Reviews Patch

This is an **overlay patch** for the existing Sarana 01 Laravel repository. It is not a new project. Extract the ZIP into the current repository root and replace the matching files.

## What changes

### Finance / sales and expenses
- The finance workspace now records manual **expenses only**.
- Sales revenue is calculated from paid POS and website orders.
- Expense categories are restricted to `id_type = 2`; the old `Other income` category is hidden and cannot be selected for new transactions.
- Inventory purchases remain visible as cash outflow and are not subtracted a second time from net profit; inventory cost is recognized through COGS when sold.
- Management reports, transaction exports and accounting-category exports omit the old income-category entries.
- Historical `Other income` rows and category records are **not deleted or altered**. They remain in the database for historical/audit purposes.

### Storefront URL
- Public storefront routes now generate `/store/...` URLs.
- Legacy **GET** requests to `/web` are permanently redirected to `/store`; this helps existing bookmarks resolve to the canonical URL without presenting two storefront URL families.
- Internal Laravel route names such as `web.home` and `web.shop` are intentionally retained to avoid breaking existing controllers and Blade links. Their generated paths are `/store/...`.
- Update the live `.env` value for `PAYWAY_CALLBACK_URL` to the new `/store` callback path. Example:

  ```dotenv
  PAYWAY_CALLBACK_URL=https://YOUR-CURRENT-HTTPS-DOMAIN/store/payments/payway/callback
  ```

  Keep your current public domain (for example, the active ngrok domain); change only the path. Then clear Laravel's config cache. The Telegram webhook (`/telegram/webhook`) and Google OAuth callback (`/auth/google/callback`) are unchanged.

### Customer reviews
- Customers can leave one store review for an order only after it is paid and reaches `completed` (delivered or collected).
- New customer reviews start as **Pending** and are not displayed publicly until an administrator publishes them in **Admin → Reviews**.
- Administrators can publish or hide reviews from the review register.
- Only published reviews appear in the public `/store` homepage testimonial section. Only the customer's first name is displayed publicly.
- Existing demo reviews remain published by default after the SQL patch so historical demo content is preserved.

## Installation

1. Back up the current project folder and export the current MariaDB database.
2. Extract this ZIP directly into the existing repository root (for example, `D:\26 - Sarana\Sarana_01`). Replace the matching source files. Do **not** delete or replace `.git`, `.env`, `vendor`, `node_modules`, uploaded files, or the database.
3. In phpMyAdmin, select the existing `sarona01` database and import `database/06_customer_reviews_moderation.sql`. This adds one column to the existing `reviews` table. It does not drop tables or rows.
4. Update the existing `.env` line `PAYWAY_CALLBACK_URL` so it ends with `/store/payments/payway/callback`, using your current HTTPS host. Do not copy the sample hostname literally.
5. Run:

   ```bash
   php artisan optimize:clear
   php artisan route:list --path=store
   php artisan route:list --path=reviews
   ```

6. Test an order end-to-end: pay it, advance it to Delivered/Collected, open the customer's order page, submit a review, then publish it from Admin → Reviews and confirm it appears on the `/store` homepage.

## Important

- Do **not** run `php artisan migrate:fresh` or re-import the entire demo database.
- The SQL uses MariaDB's `ADD COLUMN IF NOT EXISTS`, compatible with the project's MariaDB 10.4 environment.
- This patch does not modify your real `.env`, Google Cloud OAuth settings, Telegram settings, payment credentials, or existing order/customer records.
- Revenue is from paid orders. Do not create a manual sales-income entry; use the expense ledger only for rent, utilities, operating supplies, maintenance and other costs.
