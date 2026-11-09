# Coffee Corner Aura

**Coffee Shop Management System · Laravel 13 · POS · Online Ordering · Inventory · Delivery · Finance**

Coffee Corner Aura is a web-based coffee shop management system built with Laravel. It brings the public storefront, point of sale (POS), order fulfillment, customer service, product and inventory management, purchasing, finance reporting, promotions, and store settings into one application.

This repository is an actively developed academic/graduation-demo project. Use a development database and sandbox payment credentials for demonstrations. Complete the security and deployment checks before using it with real customer data or payments.

---

## Contents

- [Overview](#overview)
- [Features](#features)
- [Roles and access](#roles-and-access)
- [Technology stack](#technology-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database setup](#database-setup)
- [Environment configuration](#environment-configuration)
- [Payment integration](#payment-integration)
- [Google sign-in](#google-sign-in-optional)
- [Telegram notifications](#telegram-notifications-and-fulfillment)
- [Order and delivery workflows](#order-and-delivery-workflows)
- [AJAX updates and admin notifications](#ajax-updates-and-admin-notifications)
- [Inventory and finance rules](#inventory-and-finance-rules)
- [Useful routes](#useful-routes)
- [Run and test](#run-and-test)
- [GitHub and security checklist](#github-and-security-checklist)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)
- [License](#license)

## Overview

The system supports three connected operating areas:

1. **Storefront:** customers browse products, use a cart, place pickup or delivery orders, pay using supported methods, track orders, leave reviews, and contact the store.
2. **POS and cashier workspace:** staff record walk-in sales, takeaway orders, and delivery orders, including Cash on Delivery (COD).
3. **Administration:** administrators manage orders, delivery, staff, customers, products, inventory, suppliers, purchases, reviews, enquiries, settings, and finance information.

The project uses Laravel controllers, Eloquent models, middleware, Blade views, JavaScript, and a MariaDB-compatible SQL schema. Some business tables and upgrades are managed with the SQL scripts in `database/`; the historical Laravel migrations do not recreate the complete business schema by themselves.

## Features

### Public storefront and customer account

- Store home/shop pages, product catalogue, product details, categories, and active offers.
- Session-based cart with item quantity updates and removal.
- Customer registration, login, logout, and account/order history.
- Checkout for store pickup or home delivery.
- Delivery fee displayed on checkout and shipping information pages.
- ABA PayWay KHQR sandbox checkout when valid merchant configuration is provided.
- Customer order tracking with payment state, fulfillment progress, and order activity.
- Customer product/order reviews after an eligible completed order; new reviews require administrator approval before public display.
- Contact/enquiry form for customer questions.
- Store Account inbox for signed-in customers: an acknowledgement is added to a new enquiry and the customer can read store replies and continue the conversation.
- Unread reply indicator in the storefront account navigation.

### POS and cashier workspace

- POS product catalogue, cart, checkout, and order recording.
- Cash, KHQR/PayWay, card, and Cash on Delivery payment options where configured.
- Walk-in, takeaway, and delivery fulfillment types.
- POS delivery order requires customer/delivery details and creates a linked delivery record.
- COD orders remain **Unpaid** until the delivery handoff is confirmed and the full amount is collected.
- Cashier order queue and order status workflow.
- Stock-tracked products are checked and deducted according to the configured stock rules.

> **Important:** Cash on Delivery is a *payment method*, not an order type. For a POS home-delivery order, choose both **Order type: Delivery** and **Payment method: Cash on Delivery**. A Dine-in or Takeaway COD selection is not a delivery order and does not receive delivery-stage controls.

### Admin dashboard and management

- Dashboard summaries and business activity overview.
- Product/category management and Stock/Non-stock product classification.
- Supplier and purchase management.
- Inventory adjustments and stock movement history.
- Offers and scheduled discounts.
- Order management with expandable order details and item information.
- Unified delivery management directly in **Admin → Orders** for Delivery-type orders created from either POS or the website.
- Visual delivery stepper showing completed/current/upcoming stages, progress, customer contact, delivery address, fee, total, payment method, and activity history.
- COD cash-settlement modal at final handoff. It requires the exact order amount and an explicit cash-received confirmation; the collection note is optional.
- Separate Delivery register remains available for delivery administration.
- Customer profile/account management and staff accounts in Settings.
- Customer enquiries inbox with conversation view, admin replies, status handling, and private internal notes.
- Store Settings, including an editable **Home delivery fee (USD)** field.
- Review moderation and approval.
- Income/expense and business reports, with supported CSV/print exports.

### Customer enquiries and replies

- New signed-in customer enquiries are linked to that customer's account.
- The system adds an acknowledgement reply and enquiry reference.
- Customers can read the conversation and send follow-up messages in **My Account → Inbox**.
- Administrators manage enquiries in **Admin → Customer enquiries** and can send customer-visible replies.
- Guest enquiries remain available to administrators but are not automatically attached to an account by matching email or phone.
- Private admin notes are not displayed in the customer's inbox.

### Delivery fee settings

- Administrators can edit the default home-delivery fee from **Admin → Settings → Store profile**.
- The saved setting is used for new POS and website delivery orders and shown on the storefront shipping-information page.
- Enter `0.00` to offer free delivery.
- The `WEB_DELIVERY_FEE` environment variable remains a fallback when the database setting is `NULL` / has not yet been saved.
- Changing the fee does not recalculate existing orders; their original totals remain unchanged.

### Optional form fields

Fields described as optional (for example order notes, review comments, COD collection notes, internal notes, and selected customer contact/reference fields) are intended to be optional in both browser validation and server validation. Required fields and conditional requirements remain enforced, such as a delivery address for a delivery order and the COD cash amount/confirmation at final handoff.

## Roles and access

| Role | Typical access |
|---|---|
| **Administrator** | Full admin dashboard, orders/delivery, settings, staff management, inventory, purchasing, customer service, reviews, and finance. |
| **Cashier** | POS and cashier order-processing workspace. No administrator-only management access. |
| **Customer** | Public storefront, own account, checkout, own order history/tracking, review submission, and own customer inbox. |
| **Guest** | Public storefront browsing and contact form; customer account features require sign-in. |

Role-protected routes use Laravel authentication and the project's role/administrator middleware. Give the Administrator role only to trusted accounts.

## Technology stack

- **Backend:** PHP `^8.3`, Laravel Framework `^13.8` (the project has been developed against Laravel 13.x).
- **Database:** MariaDB 10.4+ is the primary target for the supplied SQL scripts; validate any MySQL alternative before applying the scripts.
- **Frontend:** Laravel Blade, HTML, CSS, JavaScript, and Bootstrap-based UI components/icons used by the views.
- **Asset tooling:** Node.js, npm, Vite, and Tailwind CSS tooling.
- **Payment:** ABA PayWay KHQR sandbox integration (optional and credential-dependent).
- **Other integrations:** Google OAuth sign-in (optional) and Telegram Bot notifications/fulfillment controls (optional).

## Requirements

Install the following:

- PHP 8.3 or newer with the Laravel-required extensions, including PDO MySQL, OpenSSL, Mbstring, cURL, DOM, Fileinfo, JSON, XML, and Tokenizer.
- Composer 2.
- MariaDB 10.4+ (recommended for the provided SQL scripts).
- Node.js and npm (to install/build frontend assets).
- Git.
- A local PHP web server such as Laravel Artisan, XAMPP, or another compatible environment.

Check PHP/Composer platform compatibility with:

```bash
composer check-platform-reqs
```

## Installation

### 1. Clone the repository

Replace the URL with your own GitHub repository URL:

```bash
git clone <your-repository-url>
cd <your-project-folder>
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create your local environment file

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

macOS, Linux, or Git Bash:

```bash
cp .env.example .env
```

Edit `.env` for your local database and integrations. Never commit `.env`.

### 4. Create and configure the database

Create a new MariaDB database (for example `sarona01`) using `utf8mb4`. Follow [Database setup](#database-setup) before opening the application.

### 5. Generate the application key

```bash
php artisan key:generate
php artisan optimize:clear
```

### 6. Install and build frontend assets

```bash
npm install
npm run build
```

During frontend development, run `npm run dev` in a separate terminal.

### 7. Start the Laravel application

```bash
php artisan serve
```

Open the local URL printed by Artisan, normally `http://127.0.0.1:8000`.

## Database setup

### Important: use the SQL-first upgrade path

The project contains historical Laravel migrations that do not fully represent the custom business schema and may overlap with existing tables. The supplied SQL dump and additive SQL upgrade files are the documented setup path.

**Do not use `php artisan migrate:fresh`, `php artisan migrate:refresh`, `db:wipe`, or other commands that drop/recreate the database.** They can destroy existing business records or fail against the historic schema.

### Fresh local/demo database

1. Create a new empty database in MariaDB/phpMyAdmin.
2. Import `database/sarona_demo.sql` as the base demo schema/data. Review and sanitize demo records before publishing this SQL file to a public repository.
3. Apply the additive schema scripts to the selected database in this order:

   | Order | Script | Purpose |
   |---:|---|---|
   | 1 | `database/role_access_upgrade.sql` | Compatibility update for role access/indexes on older imported databases. |
   | 2 | `database/01_upgrade_schema.sql` | Order source/status fields, order notes, sale-time item cost, payment support, and related schema updates. |
   | 3 | `database/03_stock_nonstock_finance_upgrade.sql` | Stock/Non-stock normalization and stock deduction trigger update. |
   | 4 | `database/04_telegram_order_fulfillment.sql` | Order status history and Telegram order-notification support. |
   | 5 | `database/05_google_sign_in.sql` | Optional Google account-linking field/index. |
   | 6 | `database/06_customer_reviews_moderation.sql` | Customer review approval flag. |
   | 7 | `database/07_pos_delivery_workflow.sql` | POS delivery/order-channel support and Cash on Delivery payment method. |
   | 8 | `database/08_storefront_customer_service.sql` | Store contact/enquiry table. |
   | 9 | `database/09_cod_cash_collection.sql` | COD amount, collection timestamp, collector, and collection note fields. |
   | 10 | `database/10_store_inbox_replies.sql` | Customer/admin conversation replies and inbox support. |
   | 11 | `database/11_admin_delivery_fee_settings.sql` | Editable delivery fee column in Store Settings. |

4. `database/02_audit_stock_balance.sql` is a **read-only audit query**, not a schema upgrade. Run it separately when you want to compare product quantities with recorded stock movements. It does not repair or change stock.
5. Run `php artisan optimize:clear` after applying schema updates.

The delivery-fee upgrade also has a Laravel migration at `database/migrations/2026_11_09_000001_add_delivery_fee_to_settings_table.php` when the installation has a healthy migration history. Use **either** the SQL script `11_admin_delivery_fee_settings.sql` or that migration; do not apply both. For the project's SQL-first setup, the SQL script is the recommended method.

### Existing database with live/demo records

1. Export a full backup before making changes.
2. Do not import `sarona_demo.sql` over an existing database.
3. Check which upgrade scripts have already been applied and apply only missing scripts, in the documented order.
4. Run upgrades against the database configured by `.env`, not an unintended database selected in phpMyAdmin.
5. Never use `migrate:fresh`, `db:wipe`, or drop/recreate commands on the working database.

Some SQL upgrade scripts are written to be re-runnable, but always back up first and test against a copy. Do not assume every historic migration or third-party SQL edit is reversible.

### Data notes

- Older `order_items` rows may have their sale-time cost backfilled from the product's current cost. This is an estimate; it cannot reconstruct the exact historical cost at the original sale date.
- The stock audit reports discrepancies; it does not identify their cause or change quantities.
- Existing contact messages without a linked `user_id` remain in the admin inbox and are not automatically linked to an account by email/phone matching.

## Environment configuration

The `.env.example` file contains the basic application, database, Telegram, and callback placeholders. The integration variables below may need to be added to your local `.env` when the related feature is enabled.

### Core application/database

```dotenv
APP_NAME="Coffee Corner Aura"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarona01
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
```

Use your own database name and credentials. `APP_KEY` is generated by `php artisan key:generate`.

### ABA PayWay sandbox

```dotenv
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=
PAYWAY_API_KEY=
PAYWAY_CALLBACK_URL=https://YOUR-PUBLIC-HTTPS-DOMAIN/store/payments/payway/callback
PAYWAY_CURRENCY=USD
PAYWAY_PAYMENT_OPTION=abapay_khqr
PAYWAY_LIFETIME=15
PAYWAY_QR_IMAGE_TEMPLATE=template3_color
```

### Google sign-in (optional)

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://YOUR-DOMAIN/auth/google/callback
```

Configure the same redirect URI in the Google OAuth client settings. Google sign-in requires correctly configured OAuth credentials and HTTPS callback reachability in the deployed environment.

### Telegram bot notifications (optional)

```dotenv
TELEGRAM_BOT_TOKEN=
TELEGRAM_GROUP_CHAT_ID=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_ADMIN_IDS=
```

`TELEGRAM_ADMIN_IDS` is a comma-separated list of numeric Telegram user IDs authorized to perform Telegram fulfillment actions. Keep the bot token, chat ID when private, webhook secret, and administrator IDs out of source control.

### Delivery fee fallback

```dotenv
WEB_DELIVERY_FEE=2.00
```

The administrator-saved Store Settings value takes precedence once the delivery fee database column is installed and a value has been saved. The environment value is a fallback when the stored value is `NULL`/not yet configured. The setting affects new delivery orders only.

## Payment integration

ABA PayWay is optional. Local cash-based POS usage and other non-KHQR workflows can be tested without enabling KHQR. KHQR sandbox transactions require merchant credentials issued for the sandbox environment.

Important behavior:

- Use the sandbox base URL for sandbox credentials.
- `PAYWAY_CALLBACK_URL` must be a public HTTPS URL reachable by ABA. A local `127.0.0.1` address is not reachable by ABA.
- For local testing, a public HTTPS development tunnel such as ngrok can expose the local Laravel server. Update `APP_URL` and `PAYWAY_CALLBACK_URL` to the currently assigned HTTPS host.
- After changing `.env`, run `php artisan optimize:clear` and restart local services if necessary.
- A browser redirect or QR scan is not, by itself, proof of payment. The application must verify the transaction server-to-server with ABA before marking an order paid.
- Never commit merchant IDs/secrets, raw payment responses, QR payloads, transaction references, or bank references.

## Google sign-in (optional)

Google OAuth supports the project's staff/customer sign-in flow when configured. The application uses the Google API client dependency. Set the OAuth client ID, secret, and redirect URI in `.env`, configure the same redirect URI with Google, and keep credentials private. Test staff and customer audiences separately; OAuth sign-in should not grant a more privileged role than the account's assigned role.

## Telegram notifications and fulfillment

Telegram is an additional notification/management channel; the Admin Orders page remains the primary in-app workflow. The bot and public HTTPS webhook must be configured before Telegram can deliver messages or process buttons.

### Configure the bot

1. Create a bot using Telegram's official `@BotFather` and keep its token private.
2. Add the bot to the intended administrator group and grant permission to send messages.
3. Set `TELEGRAM_GROUP_CHAT_ID` to the current numeric group/supergroup ID. When a Telegram group is upgraded to a supergroup, Telegram assigns a new chat ID; update the environment value if the old ID returns a “group chat was upgraded to a supergroup chat” error. Supergroup IDs commonly begin with `-100`.
4. Add trusted numeric Telegram user IDs to `TELEGRAM_ADMIN_IDS`.
5. Generate a random webhook secret, for example:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

6. Configure the `.env` values and run `php artisan optimize:clear`.
7. Register the webhook at `https://YOUR-DOMAIN/telegram/webhook` with Telegram's `setWebhook` method and the same secret in the `secret_token` property. The Laravel endpoint validates the Telegram secret header before processing updates.

Telegram actions are restricted to configured administrators and update the shared order/delivery records. A Telegram outage should not be treated as evidence that an order was not saved: verify the order in Admin → Orders and inspect `storage/logs/laravel.log` for the notification error.

If a bot token has appeared in a URL in logs, screenshots, a public issue, or a repository, revoke it with BotFather and replace it in `.env`. Removing it from the latest commit alone does not invalidate an exposed token.

## Order and delivery workflows

### Website order

1. A signed-in customer creates a cart and submits checkout.
2. The order, line items, and (for a delivery type) delivery information are saved in the database.
3. For KHQR, the order remains unpaid until the server-side PayWay verification confirms the transaction amount and currency.
4. Paid orders wait for fulfillment confirmation; COD orders remain unpaid while staff prepare and dispatch them.
5. Authorized staff advance the order through the correct fulfillment stages. The customer tracking page reads the same order state and event history.

### POS delivery order using COD

1. In POS, select **Order type: Delivery**.
2. Select **Payment method: Cash on Delivery**.
3. Enter the customer contact and delivery address, then save the order. It should remain **Unpaid**.
4. Open **Admin → Orders**. The unified visual delivery panel appears for Delivery-type orders from POS and website.
5. Advance one step at a time: `Pending → Confirmed → Preparing → Ready → Out for delivery → Delivered`.
6. At final handoff, the Cash Settlement modal displays the amount due. Enter the amount actually received, tick the cash-received confirmation, and optionally add a note.
7. The server checks that the amount matches the order's exact final price. Only then is the order marked Paid/Delivered and the cash-collection event recorded.

For pickup orders, the fulfillment path ends at collection and does not include an Out for delivery stage.

### COD accounting rule

The COD patch does not create a second manual sales-income transaction when cash is collected. Sales revenue is recognized from the order's paid state. This avoids double-counting the same sale in finance reports. Do not mark a COD order paid before the customer actually hands over the money.

## AJAX updates and admin notifications

The admin experience uses lightweight AJAX polling (not WebSockets) to keep order information and actionable counts current without repeatedly reloading the whole page.

- Admin action badges refresh approximately every 10 seconds and hide when their count is zero; large counts display as `99+`.
- The Orders list checks for new/updated orders approximately every 12 seconds and updates the relevant table content without a full-page reload.
- New website orders can trigger an in-app toast/notification with the order reference and a link to the Orders tab.
- Polling pauses while the browser tab is hidden and resumes when it becomes visible.
- Delivery status transitions update the visual stepper, order/payment indicators, and activity information after the server confirms the change.
- Telegram message refresh is deferred after the HTTP response so a slow Telegram API request does not unnecessarily delay the admin action.

The database remains the source of truth. Notifications are a convenience layer; a failed Telegram or browser notification does not undo a saved order. Staff should check Admin → Orders for pending work after reconnecting.

### Action badge meaning

| Admin area | Count represents |
|---|---|
| Orders | Orders awaiting fulfillment (pending status). |
| Delivery | Deliveries not yet delivered or cancelled. |
| Inventory/stock | Active stock-tracked products with quantity at or below zero. |
| Reviews | Reviews awaiting administrator approval. |
| Customer enquiries | Unread enquiries and unread customer replies on unresolved conversations. |

## Inventory and finance rules

- **Stock** products track available quantity. Sales reduce stock; purchase receipts/valid adjustments increase stock; insufficient stock should prevent overselling.
- **Non-stock** products do not use an inventory balance and are excluded from stock deduction.
- The order-item record stores a sale-time `unit_cost` snapshot for more reliable COGS reporting on new sales.
- Sales revenue is derived from paid POS/website orders.
- Manual finance entries are intended for non-sale expenses (for example utilities, rent, maintenance, or operating supplies). Do not enter a paid order as manual income as well.
- Inventory purchases are cash outflows; inventory cost is recognized as COGS when items are sold. Do not subtract the same purchase cost again from profit.
- Historical cost backfills may be estimates where sale-time cost was not recorded originally.

## Useful routes

Exact route definitions live in `routes/web.php`. Main paths in the current storefront route family include:

| Area | Path | Access |
|---|---|---|
| Staff login | `/` | Staff login page |
| Admin dashboard | `/admin` | Administrator |
| POS | `/pos` | Administrator / Cashier |
| Cashier workspace | `/cashier` | Cashier |
| Storefront shop | `/store/shop` | Public |
| Storefront cart | `/store/cart` | Public cart |
| Customer login | `/store/login` | Public |
| Customer registration | `/store/register` | Public |
| Customer account | `/store/account` | Customer |
| Customer inbox | `/store/inbox` | Customer |
| Customer checkout | `/store/checkout` | Customer |
| Customer order tracking | `/store/orders/{orderId}` | Owner customer account |
| Admin orders | `/admin/orders` | Administrator |
| Admin delivery register | `/admin/delivery` | Administrator |
| Customer enquiries | `/admin/customer-service` | Administrator |
| Admin reviews | `/admin/reviews` | Administrator |
| Inventory | `/admin/inventory` | Administrator |
| Store settings | `/admin/meanleap/setting` | Administrator |
| Finance | `/admin/expense-income` | Administrator |

Use the application navigation where possible; some legacy paths are retained for compatibility.

## Run and test

Start the application:

```bash
php artisan serve
```

Run frontend hot reload in another terminal if needed:

```bash
npm run dev
```

Build production frontend assets:

```bash
npm run build
```

Useful checks:

```bash
composer check-platform-reqs
php artisan optimize:clear
php artisan route:list
php artisan test
```

### Recommended manual test checklist

- [ ] Administrator and cashier login work; cashier cannot access administrator-only routes.
- [ ] Customer registration/login and customer-owned order access work.
- [ ] Product browsing, cart updates, and checkout work.
- [ ] Website and POS orders save the correct item, total, source, and payment state.
- [ ] KHQR is only marked paid after server-side PayWay verification.
- [ ] POS Delivery + COD appears in Admin → Orders and remains unpaid until the final cash handoff.
- [ ] Wrong COD amount or missing confirmation is rejected; correct amount records collector/time/note.
- [ ] Delivery stage changes update the admin stepper and customer tracking page.
- [ ] New website orders appear in Admin → Orders and produce in-app notifications when the admin page is open.
- [ ] Admin action badge counts match pending items in each tab.
- [ ] Editable delivery fee is used for new POS and website delivery orders; pickup/takeaway is not charged.
- [ ] Optional fields can be left blank, while required and conditional fields remain validated.
- [ ] Signed-in customer enquiries appear in My Account → Inbox; admin replies are visible to the customer.
- [ ] Guest enquiries remain manageable by the admin and are not exposed to another account.
- [ ] Reviews remain unpublished until approved by an administrator.
- [ ] Stock products reduce quantity; Non-stock products do not.
- [ ] Finance report totals do not double-count paid orders or inventory purchases.
- [ ] Telegram notification and webhook work with the current bot token and current group/supergroup chat ID.

Static syntax checks are not a substitute for browser/database/payment testing. Test with a backed-up or disposable database and sandbox payment credentials.

## GitHub and security checklist

Before pushing to a public GitHub repository, review every staged file.

### Never commit

- `.env`, production environment files, API secrets, passwords, OAuth client secrets, PayWay merchant keys, Telegram bot tokens, webhook secrets, or ngrok tokens.
- Customer exports, real database backups, unredacted SQL dumps, personal phone/email/address details, payment QR payloads, callback payloads, transaction responses, or bank references.
- Laravel logs, local session/cache files, generated dependencies, `vendor/`, or `node_modules/`.
- Uploaded customer files or private media unless they are intentionally public and licensed for redistribution.

The demo SQL file `database/sarona_demo.sql` should be reviewed and anonymized before public publication. A live database export must never be committed. Keep numbered schema upgrade scripts, because they are part of the documented setup process.

The root `.gitignore` already excludes common Laravel runtime/dependency files and `.env`; verify your current copy still does. Add local dump patterns if needed without ignoring the numbered schema upgrade scripts, for example:

```gitignore
# Local database backups/exports (keep documented upgrade scripts tracked)
/database/*backup*.sql
/database/*dump*.sql
/database/sarona01*.sql
*.sql.gz
```

Before staging:

```bash
git status --short
git ls-files .env
```

If a secret was ever committed, rotating it is essential. Deleting it in a later commit does not remove it from Git history.

Suggested final publishing workflow:

```bash
git add README.md LICENSE
# Review all other source changes and staged filenames before adding them.
git status --short
git diff --cached --check
git commit -m "Update project documentation and license"
git fetch origin
git pull --rebase origin main
git push -u origin HEAD
```

Change `main` if your default branch has a different name. If the remote repository is empty, the pull step is not needed. Do not use `git push --force` to resolve a normal push rejection.

## Troubleshooting

### Route not found

```bash
php artisan optimize:clear
php artisan route:list
```

Confirm the relevant route exists in `routes/web.php` and the controller import/name is correct.

### SQL table or column not found

- Confirm `.env` points to the intended database.
- Check that the base demo schema was imported only for a fresh database.
- Check which additive SQL scripts have been applied and apply the missing one(s) in order.
- Do not import a demo dump over a working database.

### Composer class/autoload error

```bash
composer install
composer dump-autoload
php artisan optimize:clear
```

### `.env` changes are not taking effect

```bash
php artisan optimize:clear
```

Check variable spelling/quoting and restart the local server.

### PayWay callback or KHQR verification fails

- Confirm the sandbox credentials and base URL match.
- Confirm `PAYWAY_CALLBACK_URL` uses a public HTTPS host and the `/store/payments/payway/callback` path.
- Confirm the current tunnel/domain is active and configured with the provider.
- Check Laravel logs for the transaction reference and server-side verification response. Never publish API keys or raw payment payloads.

### Telegram notification cannot be sent

- Check `TELEGRAM_BOT_TOKEN` and `TELEGRAM_GROUP_CHAT_ID` in the local `.env`.
- Confirm the bot is still a member of the group and can send messages.
- If the group was upgraded to a supergroup, update the chat ID to the new value (commonly a negative `-100...` ID).
- Verify webhook URL/secret for Telegram button actions.
- Run `php artisan optimize:clear` after environment changes and inspect `storage/logs/laravel.log` locally.
- If a token is exposed in logs or source control, revoke it with BotFather and configure a new token.

### Views or Blade changes do not appear

```bash
php artisan view:clear
php artisan optimize:clear
```

Then refresh the browser. Check the first application exception in `storage/logs/laravel.log`; a broken exception renderer may hide the original error page.

### Stock balance differs from the movement log

Run the read-only query in `database/02_audit_stock_balance.sql`, review the related product's movement history, and investigate before creating an adjustment. The audit script does not correct stock automatically.

## Known limitations

- The custom business schema is partly SQL-managed; the historical Laravel migrations do not independently recreate the full project database.
- `database/sarona_demo.sql` is a base demo snapshot and requires the documented schema upgrade scripts for the newer workflows.
- Historical COGS may be approximate where sale-time unit cost was not originally recorded.
- AJAX updates use timed polling, not WebSockets or guaranteed push delivery. The database is authoritative; a badge/toast is not a durable message queue.
- Telegram and ABA PayWay require valid external configuration and an accessible HTTPS endpoint for callbacks/webhooks.
- PayWay is documented for sandbox testing unless production credentials, callback URL, and provider approval are separately configured.
- CSV exports are spreadsheet-compatible CSV rather than native `.xlsx`; print/PDF reports use the browser's print-to-PDF function where implemented.
- A production deployment still needs environment hardening, HTTPS, backups, monitoring, secure mail configuration, and end-to-end transaction testing.

## Contributing

For a project contribution or local change:

1. Create a feature branch.
2. Keep schema changes additive when practical and include a numbered SQL upgrade plus an installation note.
3. Validate authorization, request data, stock movements, order totals, and payment transitions on the server.
4. Run PHP, route, frontend build, and relevant manual workflow checks.
5. Update this README whenever installation, integration variables, routes, or workflows change.

## License

This project is distributed under the [MIT License](LICENSE). The `composer.json` package metadata also declares `MIT`.

Before publishing, confirm that you or your project team have the rights to license all included code, images, fonts, icons, and other assets. Third-party assets/packages remain subject to their own licenses.

---

**Coffee Corner Aura** — manage the shop, serve customers, and keep every order accountable.
