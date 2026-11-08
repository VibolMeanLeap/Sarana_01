# Coffee Corner Aura

**Coffee Shop Management System · Laravel 13 · POS · Inventory · Online Ordering · Finance**

Coffee Corner Aura is a web-based coffee shop management system built with Laravel. It brings the public coffee shop website, point-of-sale workflow, product and inventory management, order processing, staff/customer accounts, purchasing, finance reports, promotions, and store settings into one application.

> **Project status:** This repository represents an actively developed academic/demo project. Use the sandbox payment configuration and a non-production database while testing. Complete the deployment and security checklist before using the system for real customer transactions.

---

## Contents

- [Project overview](#project-overview)
- [Main features](#main-features)
- [User roles](#user-roles)
- [Technology stack](#technology-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database setup](#database-setup)
- [Environment configuration](#environment-configuration)
- [ABA PayWay sandbox setup](#aba-payway-sandbox-setup)
- [Run the application](#run-the-application)
- [Main application URLs](#main-application-urls)
- [Exports, filtering, and pagination](#exports-filtering-and-pagination)
- [Inventory and finance rules](#inventory-and-finance-rules)
- [Validation and account security](#validation-and-account-security)
- [Testing and verification](#testing-and-verification)
- [GitHub publishing checklist](#github-publishing-checklist)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)

---

## Project overview

Coffee Corner Aura is designed around these daily coffee shop activities:

1. Customers browse the website, add products to a cart, place orders, and follow order/payment status.
2. Cashiers use the POS to create in-store orders and process the order queue.
3. Administrators manage products, categories, offers, staff, customers, suppliers, purchases, inventory, deliveries, reviews, and financial records.
4. The system records stock movements and sale-time product costs so inventory and profit reports can be reviewed.

The application uses Laravel controllers, Eloquent models, Blade views, middleware, and a MySQL-compatible database. Some project-specific database objects are managed through SQL upgrade scripts because the existing custom migrations are incomplete and overlap one another.

## Main features

### Public coffee shop website

- Website home page and product catalogue.
- Shop page with product/category browsing.
- Active offer banners and discounted product prices.
- Session-based Add to Cart, quantity updates, item removal, and cart count in the website navigation.
- Customer registration, login, logout, and account page.
- Customer checkout and order history/status pages.
- ABA PayWay KHQR sandbox payment flow when merchant credentials are configured.

### POS and cashier workspace

- Database-driven product catalogue for point-of-sale use.
- Cart and checkout workflow for in-store orders.
- Cashier order queue for visibility into POS and website sales. Website order fulfillment is administrator-controlled through Telegram and the administrator dashboard.
- POS order progress updates and administrator-controlled website order fulfillment. Website orders advance through Pending, Confirmed, Preparing, Ready, Out for delivery (delivery orders only), and Completed/Delivered.
- Cash and configured PayWay payment handling.

### Administrator dashboard and management

- Dashboard overview and business summaries.
- Product and category management.
- Product type selection: **Stock** or **Non-stock**.
- Supplier and purchase management.
- Inventory adjustments and stock movement history.
- Offer creation and scheduling, with active offers displayed on the website shop.
- Order management with expandable order details. Sold items are shown inside the Orders page rather than requiring a separate Order Items tab.
- Customer profile and customer-account management.
- Administrator and cashier account creation/management from Settings.
- Delivery and customer review management.
- Shop profile/settings, including shop name, logo, address, telephone, and exchange rate.
- Income and expense reporting.

### Financial reporting

The finance page separates sales revenue from other income and expenses. It is designed to present:

- **Sales income:** paid POS and website orders.
- **Other income:** manually recorded income not linked to an order or purchase.
- **Inventory purchases:** purchase-related cash outflows.
- **Operating expenses:** other recorded expenses such as supplies, maintenance, rent, or utilities.
- **Cost of Goods Sold (COGS):** cost of items sold, using the sale-time `order_items.unit_cost` snapshot where available and a legacy product-cost fallback for older records.
- **Gross profit:** sales income minus COGS.
- **Net profit:** gross profit plus other income minus operating expenses.
- **Net cash movement:** sales income plus other income minus inventory purchases and operating expenses.

Inventory purchases are displayed as cash outflows, but are not deducted a second time from net profit. The cost of inventory is recognized through COGS when products are sold; this is intended to avoid double-counting purchase costs.

### Product types

Products use two inventory classifications:

| Type | Behavior |
|---|---|
| **Stock** | Tracks available quantity. Purchases/adjustments increase stock and sales reduce stock. Insufficient stock should prevent an over-sale. |
| **Non-stock** | Does not maintain an inventory quantity. It can be sold without stock deduction and is excluded from stock purchase/adjustment workflows. |

Legacy labels such as `Drink` and `Food` are normalized to `stock` by the product-type upgrade because those products were previously stock-tracked. Existing products with sales, purchase, or stock-log history should not be reclassified; create a new product with the intended type instead.

---

## User roles

| Role | Main access |
|---|---|
| **Administrator** | Full access to the administrator dashboard and management modules, including Settings and staff account management. |
| **Cashier** | POS and cashier order-processing workspace. Cashiers do not receive administrator-only management access. |
| **Customer** | Public website, own account, checkout, own order history/status, and customer payment pages. |
| **Guest** | Can browse public website/shop pages; account login is required for customer checkout/account features. |

Administrator and cashier accounts are managed in **Settings**. The **Customer Accounts** page is for customer profiles and website-account access; it must not be used to promote customers into staff roles.

Only assign the Administrator role to trusted users.

---

## Technology stack

- **Backend:** PHP 8.3+ and Laravel 13 (Composer requirement: `laravel/framework` `^13.8`).
- **Database:** MariaDB 10.4+ is the primary database target for the supplied SQL workflow. MySQL 8.x may be usable, but validate the SQL upgrade scripts against the exact server version before applying them.
- **Frontend:** Laravel Blade, HTML, CSS, JavaScript, and the frontend assets bundled with the project.
- **Asset tooling:** Node.js/npm and Vite for frontend builds.
- **Payment integration:** ABA PayWay KHQR sandbox (optional; requires merchant credentials).
- **Development/testing tools:** Composer scripts, PHPUnit/Pest dependencies, and Laravel Artisan.

## Requirements

Install the following before starting:

- PHP **8.3 or newer**.
- Composer 2.
- MariaDB **10.4 or newer** (recommended for the supplied SQL scripts), or a compatible MySQL 8.x server after validating the scripts.
- Node.js and npm (for Vite asset installation/builds).
- Git.
- A local web server such as Laravel's Artisan server, XAMPP, or another PHP-compatible server.

Enable the PHP extensions required by Laravel and PDO MySQL, including `pdo_mysql`, `mbstring`, `openssl`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `json`, `pcre`, `session`, `tokenizer`, and `xml`. Check your actual environment with:

```bash
composer check-platform-reqs
```

---

## Installation

### 1. Clone the repository

Replace `<repository-url>` with your GitHub repository URL:

```bash
git clone <repository-url>
cd Coffee-Corner-Aura
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the local environment file

**Windows PowerShell:**

```powershell
Copy-Item .env.example .env
```

**macOS / Linux / Git Bash:**

```bash
cp .env.example .env
```

Open `.env` and configure the database connection. Never commit this file.

### 4. Configure the database

Create a new empty database, for example `sarona`, using `utf8mb4`. See [Database setup](#database-setup) before importing or upgrading any SQL.

### 5. Generate the application key

```bash
php artisan key:generate
php artisan optimize:clear
```

### 6. Install frontend dependencies and build assets

```bash
npm install
npm run build
```

For frontend development with hot reload, use:

```bash
npm run dev
```

### 7. Start Laravel

```bash
php artisan serve
```

Open the URL printed by Artisan, normally:

```text
http://127.0.0.1:8000
```

---

## Database setup

### Important database design note

The project has a SQL-first custom schema. The repository's historical Laravel migration files are incomplete and include overlapping/duplicate custom table migrations. **Do not use `php artisan migrate:fresh`, `php artisan migrate:refresh`, or drop/recreate your existing database as a routine setup or update step.** These commands can destroy data or fail against existing tables.

The current application expects the project-specific tables and fields from the SQL schema plus the upgrade scripts described below.

### Fresh demo database

For a new local/demo installation:

1. Create an empty database, e.g. `sarona`.
2. Import `database/sarona_demo.sql` using phpMyAdmin or the MySQL/MariaDB command line. This file is the original base demo dump and may not include the latest application fields by itself.
3. Run `database/role_access_upgrade.sql` to apply the role-index/customer-account compatibility update when starting from the supplied older demo dump.
4. Run `database/01_upgrade_schema.sql` to add the newer order workflow fields, sale-time unit-cost field, payments table/indexes, and audit-history foreign-key behavior.
5. Run `database/03_stock_nonstock_finance_upgrade.sql` to normalize product types and update the order-item insert trigger so only Stock products reduce inventory.
6. Run `database/04_telegram_order_fulfillment.sql` to add the order event history and Telegram notification tables, and reconcile the known demo delivery/order mismatch.
7. Optionally run `database/02_audit_stock_balance.sql` to review product quantities against recorded stock movements. This is a **read-only audit query**, not a schema migration; it does not repair or change quantities.

The expected SQL upgrade files should be present in the repository under `database/`:

```text
database/
├── sarona_demo.sql
├── role_access_upgrade.sql
├── 01_upgrade_schema.sql
├── 02_audit_stock_balance.sql
├── 03_stock_nonstock_finance_upgrade.sql
└── 04_telegram_order_fulfillment.sql
```

If one of the upgrade files is absent from your working copy, retrieve it from the corresponding project update patch before documenting the repository as ready for a fresh install.

### Existing database

If you already have a working `sarona` / `sarona01` database containing real project data:

1. Export a complete backup first.
2. Do **not** import `sarona_demo.sql` over the existing database.
3. Check which upgrade scripts have already been applied.
4. Apply only the missing upgrade scripts, in the order above, after confirming the target database selected in phpMyAdmin.
5. Do not import the stock audit query as a repair script. It only reports differences.

The `01_upgrade_schema.sql`, `03_stock_nonstock_finance_upgrade.sql`, and `04_telegram_order_fulfillment.sql` scripts are intended as additive/compatibility updates. Always test them on a backup before running them against a database that contains important records.

### Historical data notes

- The original order-item schema did not always store cost at the time of sale. When `01_upgrade_schema.sql` adds `order_items.unit_cost`, older rows are backfilled using the product's current cost as the best available estimate. This does not reconstruct the exact historical cost at the original sale date.
- The stock audit can reveal quantity differences. It does not determine the reason for a discrepancy and does not automatically adjust stock.
- Legacy `Drink`/`Food` product types are mapped to Stock to preserve the previous stock-tracking behavior.

---

## Environment configuration

At minimum, configure these settings in your local `.env` file:

```dotenv
APP_NAME="Coffee Corner Aura"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarona
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.test"
MAIL_FROM_NAME="${APP_NAME}"

# ABA PayWay sandbox (optional)
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=
PAYWAY_API_KEY=
PAYWAY_CALLBACK_URL=
PAYWAY_CURRENCY=USD
PAYWAY_PAYMENT_OPTION=abapay_khqr
PAYWAY_LIFETIME=15
PAYWAY_QR_IMAGE_TEMPLATE=template3_color

# Telegram order notifications (optional; website order workflow)
TELEGRAM_BOT_TOKEN=
TELEGRAM_GROUP_CHAT_ID=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_ADMIN_IDS=
```

Update `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` to match your local database. `APP_KEY` is generated by `php artisan key:generate`.

For a production deployment, set `APP_ENV=production`, `APP_DEBUG=false`, configure a real HTTPS `APP_URL`, use a restricted database user, and configure an appropriate mail driver. Never expose credentials or enable debug mode on a public server.

---

## ABA PayWay sandbox setup

ABA PayWay is optional. Cash/POS and website functionality that does not require KHQR can be tested without configuring PayWay. KHQR sandbox payments require ABA-provided sandbox merchant credentials.

Configure these values in `.env`:

| Variable | Purpose |
|---|---|
| `PAYWAY_BASE_URL` | ABA sandbox API base URL. |
| `PAYWAY_MERCHANT_ID` | Your sandbox merchant ID. |
| `PAYWAY_API_KEY` | Your sandbox API key/secret. Keep it private. |
| `PAYWAY_CALLBACK_URL` | Public HTTPS callback URL that ABA can reach. |
| `PAYWAY_CURRENCY` | Payment currency, normally `USD` for this setup. |
| `PAYWAY_PAYMENT_OPTION` | Payment option, default `abapay_khqr`. |
| `PAYWAY_LIFETIME` | QR/payment lifetime in minutes. |
| `PAYWAY_QR_IMAGE_TEMPLATE` | QR image template identifier. |

### Testing locally with ngrok

ABA cannot call a `127.0.0.1` callback on your computer. For local sandbox tests, expose the Laravel server through a public HTTPS tunnel such as ngrok.

**Terminal 1 – start Laravel:**

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 – start ngrok:**

```bash
ngrok http 8000
```

Copy the HTTPS forwarding domain shown by ngrok and set:

```dotenv
APP_URL=https://YOUR-NGROK-DOMAIN
PAYWAY_CALLBACK_URL=https://YOUR-NGROK-DOMAIN/web/payments/payway/callback
```

Replace `YOUR-NGROK-DOMAIN` with the current domain issued by ngrok. If the ngrok domain changes, update `.env` and any callback/allowlist configuration required by ABA, then run:

```bash
php artisan optimize:clear
```

Keep both Laravel and ngrok running while testing. Do not share your ngrok authtoken, merchant API key, or payment credentials. A tunnel is for development/testing; use a stable HTTPS domain and the correct production PayWay configuration for deployment.

The application uses a public callback route at `/web/payments/payway/callback`. The callback is treated as a notification; the application should verify transaction status server-to-server before considering an order paid. Do not trust a browser redirect alone as proof of payment.

---

## Telegram order notifications and fulfillment

The Telegram integration is optional until a bot and public HTTPS webhook have been configured. It does not create a second order database: Telegram actions update the same `orders` and `delivery` rows used by the administrator dashboard and customer order-tracking page.

### Order lifecycle

1. A customer submits a website checkout. The order and its item/delivery records are saved as `pending`; stock reservation follows the existing checkout workflow.
2. The customer pays through ABA KHQR. A successful browser redirect or the callback body is **not** treated as payment proof. Laravel verifies the transaction with ABA's server-side Check Transaction request.
3. Only after ABA confirms the amount and currency does the order become paid. The order remains `pending` until an administrator accepts it.
4. The application posts one paid-order notification to the configured Telegram group. The message includes the order reference, customer contact, items, total, and delivery address when applicable.
5. An allowlisted administrator presses the single next-step button. The Telegram webhook validates the Telegram secret header, group chat ID, Telegram user ID, message ID, and signed callback data before updating the database.
6. The order and delivery status are updated in the same database transaction. The original Telegram message is edited to show the latest status and the next permitted action. The customer order page polls the same database state and shows its progress and audit history.

Delivery orders use this sequence:

`Pending → Confirmed → Preparing → Ready → Out for delivery → Delivered`

Store-pickup orders use:

`Pending → Confirmed → Preparing → Ready for pickup → Collected`

Every new status transition is written to `order_status_history`, including the actor and Telegram identity where applicable. Duplicate or out-of-order Telegram button presses are rejected; the bot refreshes the original group message to the current state. Cashiers can see website orders but cannot advance them. Administrators can also use the admin order screen as a controlled fallback.

### Create and configure the Telegram bot

1. Create a bot with Telegram's official **@BotFather** account and copy its bot token. Keep the token private.
2. Add the bot to your administrator group and give it permission to send messages. Use the group's numeric chat ID as `TELEGRAM_GROUP_CHAT_ID` (supergroups commonly have a negative ID beginning with `-100`).
3. Add the numeric Telegram user IDs of trusted administrators, comma-separated, to `TELEGRAM_ADMIN_IDS`. Group membership alone does not grant permission to press order buttons.
4. Generate a random webhook secret containing only letters, numbers, `_` or `-`. For example, run `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` and put the result into `TELEGRAM_WEBHOOK_SECRET`.
5. Configure `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=YOUR_BOT_TOKEN
TELEGRAM_GROUP_CHAT_ID=YOUR_GROUP_CHAT_ID
TELEGRAM_WEBHOOK_SECRET=YOUR_RANDOM_WEBHOOK_SECRET
TELEGRAM_ADMIN_IDS=YOUR_TELEGRAM_USER_ID,ANOTHER_ADMIN_USER_ID
```

6. Use a public HTTPS domain. For local testing, Laravel may be exposed through ngrok as described in the PayWay section. Set `APP_URL` to that HTTPS origin, then clear cached configuration:

```bash
php artisan optimize:clear
```

7. Register the webhook with Telegram. Replace the placeholders locally; do not paste the token or secret into source control or public chat:

```bash
curl -X POST "https://api.telegram.org/botYOUR_BOT_TOKEN/setWebhook" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://YOUR_PUBLIC_DOMAIN/telegram/webhook","secret_token":"YOUR_RANDOM_WEBHOOK_SECRET","allowed_updates":["callback_query"]}'
```

The webhook endpoint is `/telegram/webhook`. Telegram sends the `X-Telegram-Bot-Api-Secret-Token` header; Laravel checks this header before processing an update. Keep the public HTTPS endpoint online while using Telegram actions. If the tunnel URL changes, register the new webhook URL again.

### Demo testing checklist

- Place a website order and check that it appears as `pending` and unpaid in the database.
- Complete the ABA sandbox payment and wait for Laravel's server-side verification. Confirm that the Telegram group receives exactly one notification only after payment is verified.
- Press **Confirm order** as an allowlisted administrator. Check that both `orders.order_status` and, for delivery orders, `delivery.status` become `confirmed`.
- Advance each button one step at a time. Check the original Telegram message, admin order list, delivery register, customer tracking page, and `order_status_history` after each action.
- Try pressing a stale button or using a Telegram account not in the allowlist; it must not change the database.
- Test a pickup order and verify that it does not receive an `Out for delivery` step.

The Telegram bot token, numeric group ID, webhook secret, allowlisted administrator IDs, and public HTTPS webhook are deployment settings. Do not commit `.env`. A paid delivery cancellation does not automatically refund a payment; record the appropriate refund/adjustment through the shop's accounting process.

---

## Run the application

Typical local development workflow:

```bash
# Terminal 1
php artisan serve

# Terminal 2 (optional, for frontend asset development)
npm run dev
```

After changing `.env`, routes, configuration, or Blade files, clear Laravel's cached state:

```bash
php artisan optimize:clear
```

Useful commands:

```bash
php artisan route:list
php artisan route:list --name=admin.orders
php artisan route:list --name=admin.exports
php artisan test
npm run build
```

---

## Main application URLs

The exact routes are defined in `routes/web.php`. The main entry points are:

| Area | URL | Access |
|---|---|---|
| Staff login | `/` | Administrator / Cashier login page |
| Administrator dashboard | `/admin` | Administrator |
| POS | `/pos` | Administrator / Cashier |
| Cashier workspace | `/cashier` | Cashier |
| Website home | `/web` | Public |
| Website shop | `/web/shop` | Public |
| Website cart | `/web/cart` | Public cart; checkout requires customer login |
| Customer login | `/web/login` | Public |
| Customer registration | `/web/register` | Public |
| Customer account | `/web/account` | Customer |
| Administrator orders | `/admin/orders` | Administrator |
| Income & Expenses | `/admin/expense-income` | Administrator |
| Settings | `/admin/meanleap/setting` | Administrator |
| Customer accounts | `/admin/user` | Administrator |

Use the application navigation to move between management pages. Some older URLs are retained as redirects for compatibility.

---

## Exports, filtering, and pagination

Administrator list pages provide relevant search/filter controls and pagination where implemented. The Orders screen supports order-level searching/filtering and expandable line-item details.

Export actions are available for supported administration lists, including products, categories, suppliers, customers/customer accounts, staff accounts, accounting categories, offers, orders, order items, purchases, inventory, transactions, deliveries, and reviews.

- **Excel export:** generated as UTF-8 CSV, which can be opened by Microsoft Excel and other spreadsheet applications. It is not an `.xlsx` workbook.
- **PDF export:** opens a print-ready report. Choose **Save as PDF** in the browser's print dialog.
- Exports use the selected list filters and are designed to include all matching records rather than only the current visible pagination page.

Product and supplier tables do not have a reliable created-at timestamp in the supplied schema, so they should be sorted by available fields such as ID/name rather than displaying a fabricated creation date.

---

## Inventory and finance rules

- Stock-tracked products are checked before a sale and reduced when an order item is inserted.
- Non-stock products do not have an inventory balance and do not reduce quantity during sales.
- Purchase receipts and manual inventory adjustments are intended to create stock movement history for Stock products.
- Sale-time product cost is saved to `order_items.unit_cost` for new orders so future cost changes do not rewrite the cost basis of older sales.
- The database stock trigger is part of the inventory workflow. Apply `03_stock_nonstock_finance_upgrade.sql` before selling Non-stock products.
- Do not manually edit or delete historical stock movements without understanding the related product quantity, purchase, and order records.
- Product classification is protected after historical activity. If the wrong type was selected, deactivate the product and create a replacement with the correct type rather than reinterpreting old records.
- Paid orders and payment history should be corrected through a controlled adjustment/refund process, not by casually changing saved order lines.

---

## Validation and account security

- Forms use client-side validation feedback and Laravel server-side validation rules where implemented.
- Invalid required fields are highlighted with a red border and field-level feedback.
- Password fields include show/hide controls on supported login and account forms.
- Passwords are stored using Laravel password hashing.
- Administrator, cashier, and customer routes use authentication and role middleware.
- Administrator/cashier account management is separate from customer account management.
- Payment status must be confirmed by the payment integration, not by a client-side message.

Client-side validation improves usability but is not a security boundary. All requests must continue to be validated and authorized by the backend.

---

## Testing and verification

Run the project checks from the repository root:

```bash
composer check-platform-reqs
php artisan route:list
php artisan test
npm run build
```

Recommended manual test checklist:

- [ ] Staff login works for an active Administrator and Cashier account.
- [ ] Cashiers cannot access administrator-only pages.
- [ ] Customer registration/login works and customers can only view their own orders.
- [ ] Add to Cart works from both the website home page and Shop page.
- [ ] Cart quantity updates and item removal update the cart count and totals.
- [ ] Active offers appear on the website and discounted prices carry through cart/checkout.
- [ ] POS checkout creates the expected order and payment status.
- [ ] KHQR sandbox test is confirmed through the server-side payment status flow.
- [ ] Stock products reduce quantity and create a stock movement on sale.
- [ ] Non-stock products can be sold without quantity deduction.
- [ ] Purchases/stock adjustments affect only eligible Stock products.
- [ ] Clicking an order row displays its sold item details.
- [ ] Order total summaries remain consistent when changing pagination pages.
- [ ] Income/expense totals reconcile with paid orders, purchase records, and manual transactions.
- [ ] CSV and print/PDF exports respect the chosen filters.
- [ ] Empty/invalid form submissions show validation errors and do not save invalid data.

A passing syntax or route check does not replace browser, database, inventory, or payment integration testing. Always test against a disposable or backed-up database first.

---

## GitHub publishing checklist

Before pushing this project to a public GitHub repository, check the following carefully.

### Never commit secrets or private data

Do **not** commit:

- `.env` or any real environment file.
- ABA PayWay merchant API keys, ngrok authtokens, database passwords, or production credentials.
- Real customer names, phone numbers, emails, or account details.
- Real payment QR payloads, callback payloads, transaction responses, or bank references.
- Production database exports or the separate personal database file `sarona (7).sql`.
- Laravel logs, session files, cache contents, or local backups.
- `vendor/` or `node_modules/`.

The original development archive used during project review contained a local `.env` file. Make sure it is not tracked in Git. If a real `.env` or credential was ever pushed, deleting it in a new commit is not enough: rotate the exposed credentials and remove the secret from Git history.

### Confirm ignored files are not tracked

The repository `.gitignore` should contain at least:

```gitignore
.env
.env.*
!.env.example
/vendor
/node_modules
/public/build
/public/hot
/storage/logs/*
/storage/framework/sessions/*
```

Check tracked files before committing:

```bash
git status --short
git ls-files .env
```

If `.env` is already tracked, remove it from Git's index while keeping your local file:

```bash
git rm --cached .env
```

If `vendor` or `node_modules` were previously committed, untrack them too:

```bash
git rm -r --cached vendor node_modules
```

Then review the staged changes. Do not remove local files unless you intend to.

### Publish a safe example database

If you include `database/sarona_demo.sql`, keep it strictly as demo data. Replace real people and transaction information with fictional records. Do not add `sarona (7).sql` or an unfiltered export of your working database to the repository. Review payment-related columns (`qr_string`, `qr_image`, `raw_response`, `callback_payload`, `bank_ref`, and `tran_id`) before publishing any SQL file.

### Suggested Git commands

```bash
git status --short
git add .
git status --short
git commit -m "Document Coffee Corner Aura project"
git push -u origin main
```

Before `git add .`, review the status output and ensure there are no secrets, personal database exports, logs, or generated dependency folders staged for commit.

---

## Troubleshooting

### `Route [name] not defined`

Clear route/config cache and check the route name:

```bash
php artisan optimize:clear
php artisan route:list
```

Make sure the local `routes/web.php` includes the route definition from the latest project update.

### `SQLSTATE[42S02]` table not found or `SQLSTATE[42S22]` column not found

- Confirm `.env` points to the intended database.
- Confirm the base SQL dump was imported into the correct empty database for a fresh setup.
- Confirm the required schema upgrade scripts were applied in order.
- Do not import a demo dump over a working database.

### `Class ... does not exist`

Run:

```bash
composer install
composer dump-autoload
php artisan optimize:clear
```

Then verify the controller/model file and namespace match the route import.

### Changes in `.env` are not taking effect

Run:

```bash
php artisan optimize:clear
```

Check spelling and quoting in `.env`, then restart the local development server.

### Product images or shop logo are missing

Check that the uploaded file exists under the public image/upload directory used by the relevant controller. Confirm that the web server can read the `public/` files and that the filename saved in the database matches the physical file.

### ABA callback does not arrive locally

- Confirm `php artisan serve` and ngrok are both running.
- Confirm `PAYWAY_CALLBACK_URL` uses the current ngrok HTTPS domain and ends with `/web/payments/payway/callback`.
- Run `php artisan optimize:clear` after changing `.env`.
- Confirm the callback URL and merchant sandbox settings with ABA.
- Check Laravel logs for the transaction reference and server-side verification result. Never paste API keys or payment secrets into public issues.

### Stock differs from stock logs

Run the read-only stock audit query in `database/02_audit_stock_balance.sql`. Review the returned product IDs and their movement histories. The query does not repair quantities. Investigate missing adjustments or historical movements before changing stock.

---

## Known limitations

- The project-specific SQL schema is ahead of some historical Laravel migrations. Use the documented SQL setup/upgrade workflow; do not assume `php artisan migrate:fresh` can recreate the full business schema.
- The original demo SQL is an older base snapshot and must be upgraded for the latest order/payment and Stock/Non-stock features.
- Historical COGS values backfilled from current product cost are estimates, not exact historical costs.
- The stock audit reports unexplained differences; it does not identify the cause or auto-correct inventory.
- ABA PayWay integration is configured for sandbox testing unless valid production credentials and a production callback/domain have been separately approved and configured.
- Excel exports are CSV files and PDF exports use the browser print dialog.
- Live payment, concurrency, and production hosting behavior must be validated in the target environment.

---

## Contributing

For a project contribution or local modification:

1. Create a branch for the change.
2. Keep database changes additive where possible and document any required SQL upgrade.
3. Validate authorization, request input, stock movements, order totals, and payment status on the backend.
4. Run PHP/route/build checks and test the relevant workflow with a non-production database.
5. Update this README when setup requirements, routes, or workflows change.

---

## License

No standalone project license file was confirmed during the project review. Add a `LICENSE` file before presenting the repository as open source, and make sure the license you choose covers the code and assets you own or are permitted to redistribute.

---

**Coffee Corner Aura** — manage the shop, serve customers, and keep every order accountable.
