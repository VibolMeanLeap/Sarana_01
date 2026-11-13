# Coffee Corner Aura

**Coffee Shop Management System · Laravel 13 · POS · Online Ordering · Inventory · Delivery · Finance · English / Khmer**

Coffee Corner Aura (also referred to in the repository as **Sarana 01** / **SK Coffee**) is a Laravel-based coffee shop management application. It combines a customer-facing storefront, staff point of sale (POS), order and delivery workflows, product and inventory management, purchasing, finance reports, customer service, store settings, and administrator tools.

> **Project status:** This is an actively developed academic / graduation-demo project. Use a local or staging database and sandbox payment credentials for demonstrations. Complete the security, backup, payment, and deployment checks before using it with real customer data or payments.

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
- [English and Khmer language support](#english-and-khmer-language-support)
- [Backup and restore](#backup-and-restore)
- [Payment integration](#payment-integration)
- [Google sign-in](#google-sign-in-optional)
- [Telegram notifications and fulfillment](#telegram-notifications-and-fulfillment)
- [Order and delivery workflows](#order-and-delivery-workflows)
- [AJAX updates and admin notifications](#ajax-updates-and-admin-notifications)
- [Inventory and finance rules](#inventory-and-finance-rules)
- [Useful routes](#useful-routes)
- [Run and test](#run-and-test)
- [GitHub and security checklist](#github-and-security-checklist)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)
- [Contributing](#contributing)
- [License](#license)

## Overview

The application has three main workspaces:

1. **Public storefront:** customers browse products and offers, manage a cart, create accounts, place pickup or delivery orders, pay through configured methods, track orders, submit reviews, and contact the shop.
2. **POS and cashier workspace:** authorized staff create and process in-store, takeaway, and delivery orders, record payments, and work with the cashier order queue.
3. **Administration:** administrators manage products, categories, staff, customers, orders, delivery, stock, purchases, suppliers, reviews, enquiries, offers, shop settings, finance information, exports, and backup/restore operations.

The project uses Laravel routes, controllers, middleware, Eloquent models, Blade templates, JavaScript, and a MariaDB-compatible business schema. Some business tables and upgrades are managed by SQL scripts in `database/`; the historical Laravel migrations do **not** recreate the complete business schema on their own. Follow the SQL-first setup below.

## Features

### Public storefront and customer account

- Storefront home/shop pages, product catalogue, product details, categories, and active offers.
- Session-based shopping cart with quantity updates and item removal.
- Customer registration, login, logout, account page, and order history.
- Checkout for store pickup or home delivery.
- Delivery fee shown during checkout and on the shipping-information page.
- Optional ABA PayWay KHQR checkout when valid merchant credentials and a reachable HTTPS callback are configured.
- Customer order tracking with payment state, fulfillment progress, and order activity.
- Review submission for eligible completed orders; new reviews remain pending until an administrator approves them.
- Contact/enquiry form for customer questions.
- Customer inbox for signed-in users, with automatic enquiry acknowledgement, follow-up replies, store replies, and unread indicators.

### POS and cashier workspace

- POS product catalogue, category/product selection, cart, checkout, and order recording.
- Cash, KHQR/PayWay, card, and Cash on Delivery (COD) payment options where configured.
- Walk-in, takeaway, and delivery fulfillment workflows.
- POS delivery orders collect customer and delivery information and create a linked delivery record.
- COD orders remain **Unpaid** until the delivery handoff is completed and the exact amount is collected.
- Cashier order queue and order-status workflow.
- Stock-tracked products are checked and deducted according to the configured stock rules; non-stock products are not deducted from inventory.

> **Important:** Cash on Delivery is a *payment method*, not an order type. For a home-delivery COD order, choose **Order type: Delivery** and **Payment method: Cash on Delivery**. A walk-in or takeaway COD selection is not a delivery order and does not receive delivery-stage controls.

### Admin dashboard and management

- Dashboard summaries, business activity overview, recent orders, and stock/low-stock information.
- Product and category management, including Stock / Non-stock classification.
- Supplier and purchase management.
- Inventory adjustments and stock-movement history.
- Offers and scheduled discounts.
- Order management with expandable order and item details.
- Unified delivery management in **Admin → Orders** for Delivery-type orders created from the POS or website.
- Delivery stepper showing completed/current/upcoming stages, customer contact, delivery address, fee, total, payment method, and activity history.
- COD cash-settlement dialog at final handoff. The staff member must confirm that cash was received and enter the exact amount; the collection note is optional.
- Separate delivery register for delivery administration.
- Customer profile/account management and staff-account management in Settings.
- Customer enquiries inbox with conversation view, administrator replies, status handling, and private internal notes.
- Store profile/settings, including the editable **Home delivery fee (USD)** value.
- Review moderation and approval.
- Income/expense and business reports, with supported CSV and print exports.
- Administrator-only **Backups & restore** workspace (see [Backup and restore](#backup-and-restore)).

### Customer enquiries and replies

- New enquiries submitted by signed-in customers are associated with that customer account.
- The system adds an acknowledgement and enquiry reference.
- Customers can read the thread and send follow-up messages in **My Account → Inbox**.
- Administrators manage conversations in **Admin → Customer enquiries** and can send customer-visible replies.
- Guest enquiries remain available to administrators but are not automatically attached to a customer account by matching an email address or phone number.
- Private administrator notes are not displayed in the customer's inbox.

### Delivery fee settings

- Administrators can edit the default home-delivery fee from **Admin → Settings → Store profile**.
- The saved setting is used for new POS and website delivery orders and shown on the storefront shipping-information page.
- Enter `0.00` to offer free delivery.
- `WEB_DELIVERY_FEE` is a fallback when the database setting is `NULL` or has not yet been saved.
- Changing the fee does not recalculate existing orders; their original totals remain unchanged.

### Optional form fields

Fields described as optional (for example order notes, review comments, COD collection notes, internal notes, and selected customer contact/reference fields) are intended to be optional in both browser validation and server validation. Required and conditional fields remain enforced, such as a delivery address for a delivery order and the COD amount/confirmation at final handoff.

## Roles and access

| Role | Typical access |
|---|---|
| **Administrator** | Admin dashboard, orders/delivery, settings, staff management, inventory, purchasing, customer service, reviews, finance, exports, and backups. |
| **Cashier** | POS and cashier order-processing workspace. No administrator-only management or backup access. |
| **Customer** | Public storefront, own account, checkout, own order tracking/history, review submission, and own customer inbox. |
| **Guest** | Public storefront browsing and contact form; customer account and checkout features require sign-in. |

Role-protected routes use Laravel authentication and the project's role/administrator middleware. Grant the Administrator role only to trusted accounts. Backup routes are inside the administrator-protected route group.

## Technology stack

- **Backend:** PHP `^8.3`, Laravel Framework `^13.8` (Laravel 13.x).
- **Database:** MariaDB 10.4+ is the primary target for the supplied SQL scripts. Validate MySQL alternatives before applying the SQL scripts.
- **Frontend:** Laravel Blade, HTML, CSS, JavaScript, Bootstrap-based components, Bootstrap Icons, and SweetAlert2 notifications where included by the layout.
- **Asset tooling:** Node.js, npm, Vite, and Tailwind CSS tooling.
- **Database backup tooling:** PHP ZIP extension (`ZipArchive`) and MySQL/MariaDB command-line clients (`mysqldump` / `mysql`, or compatible MariaDB client binaries).
- **Payments:** ABA PayWay KHQR sandbox integration (optional and credential-dependent).
- **Other integrations:** Google OAuth sign-in and Telegram Bot notifications/fulfillment actions (both optional).

## Requirements

Install or enable:

- PHP 8.3 or newer with Laravel-required extensions, including PDO MySQL, OpenSSL, Mbstring, cURL, DOM, Fileinfo, JSON, XML, and Tokenizer.
- PHP **ZIP** extension for backup creation and restore (`ZipArchive`).
- Composer 2.
- MariaDB 10.4+ (recommended for the supplied SQL scripts).
- `mysqldump` and `mysql` command-line tools (or MariaDB-compatible equivalents) available to the PHP process for backup and restore.
- Node.js and npm for frontend asset installation/builds.
- Git.
- A local PHP web server such as Laravel Artisan, XAMPP, or another compatible environment.

Check PHP and Composer platform compatibility:

```bash
composer check-platform-reqs
php -m
php --ri zip
```

On Windows, also verify the PHP executable used by XAMPP:

```cmd
D:\Xamm\php\php.exe -r "var_dump(extension_loaded('zip'));"
```

The expected result is `bool(true)`. Apache and command-line PHP can load different `php.ini` files; enable ZIP in both runtimes used by the application and restart Apache after changing the Apache PHP configuration.

## Installation

### 1. Clone the repository

Replace the URL with your GitHub repository URL:

```bash
git clone <your-repository-url>
cd <your-project-folder>
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the local environment file

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

macOS, Linux, or Git Bash:

```bash
cp .env.example .env
```

Edit `.env` for your local database and integrations. Never commit `.env`. If your checkout does not contain `.env.example`, obtain the project's template from a trusted project copy or create the file using the variables in [Environment configuration](#environment-configuration); do not copy another machine's real `.env` with passwords or tokens.

### 4. Create and configure the database

Create an empty MariaDB database (for example `sarona01`) using `utf8mb4`. Follow [Database setup](#database-setup) before opening the application.

### 5. Generate the application key and clear cached configuration

```bash
php artisan key:generate
php artisan optimize:clear
```

### 6. Install and build frontend assets

```bash
npm install
npm run build
```

For frontend development, run `npm run dev` in a separate terminal.

### 7. Start the application

```bash
php artisan serve
```

Open the local URL printed by Artisan, normally `http://127.0.0.1:8000`.

## Database setup

### Important: use the SQL-first upgrade path

The repository contains historical Laravel migrations that do not fully represent the custom business schema and may overlap with existing tables. The supplied SQL dump and additive SQL upgrade files are the documented setup path.

**Do not use `php artisan migrate:fresh`, `php artisan migrate:refresh`, `db:wipe`, or commands that drop/recreate the database on a working database.** They can destroy business records or fail against the historical schema.

### Fresh local/demo database

1. Create a new empty MariaDB database in MariaDB/phpMyAdmin.
2. Import `database/sarona_demo.sql` as the base demo schema/data. Review and sanitize demo records before publishing the SQL file to a public repository.
3. Apply the additive SQL scripts to that database in this order:

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
   | 9 | `database/09_cod_cash_collection.sql` | COD amount, collection timestamp, collector, and collection-note fields. |
   | 10 | `database/10_store_inbox_replies.sql` | Customer/admin conversation replies and inbox support. |
   | 11 | `database/11_admin_delivery_fee_settings.sql` | Editable delivery-fee column in Store Settings. |

4. `database/02_audit_stock_balance.sql` is a **read-only audit query**, not a schema upgrade. Run it separately when you want to compare product quantities with recorded stock movements. It does not repair or change stock.
5. Run `php artisan optimize:clear` after applying schema updates.

The delivery-fee upgrade also has a Laravel migration at `database/migrations/2026_11_09_000001_add_delivery_fee_to_settings_table.php` for installations with a healthy migration history. Use **either** the SQL script `11_admin_delivery_fee_settings.sql` or that migration; do not apply both. For this project's SQL-first setup, the SQL script is the recommended method.

### Existing database with live/demo records

1. Export a full database backup before making changes.
2. Do not import `sarona_demo.sql` over an existing database.
3. Check which upgrade scripts have already been applied and apply only missing scripts, in the documented order.
4. Run upgrades against the database configured in `.env`, not an unintended database selected in phpMyAdmin.
5. Never use `migrate:fresh`, `db:wipe`, or drop/recreate commands on the working database.

Some SQL upgrade scripts are designed to be re-runnable, but always back up first and test against a copy. Do not assume every historical migration or third-party SQL edit is reversible.

### Data notes

- Older `order_items` rows may have their sale-time cost backfilled from the product's current cost. This is an estimate; it cannot reconstruct the exact historical cost at the original sale date.
- The stock audit reports discrepancies; it does not identify their cause or change quantities.
- Existing contact messages without a linked `user_id` remain in the admin inbox and are not automatically linked to an account by email/phone matching.

## Environment configuration

The `.env.example` template provides core application, database, Telegram, and callback placeholders. Add the optional variables below to the local `.env` when enabling the related feature. Never put real credentials in README files or commit them to Git.

### Core application and database

```dotenv
APP_NAME="Coffee Corner Aura"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

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

Use your own database name and credentials. Generate `APP_KEY` with `php artisan key:generate`.

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

Use sandbox credentials with the sandbox base URL. The callback must be a public HTTPS endpoint reachable by ABA; `127.0.0.1` is not reachable by the provider.

### Google sign-in (optional)

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://YOUR-DOMAIN/auth/google/callback
```

Configure the same redirect URI in the Google OAuth client settings. Test customer and staff audiences separately. Google sign-in must not grant a more privileged role than the account's assigned role.

### Telegram bot notifications (optional)

```dotenv
TELEGRAM_BOT_TOKEN=
TELEGRAM_GROUP_CHAT_ID=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_ADMIN_IDS=
```

`TELEGRAM_ADMIN_IDS` is a comma-separated list of numeric Telegram user IDs authorized to perform Telegram fulfillment actions. Keep the bot token, private group chat ID, webhook secret, and administrator IDs out of source control.

### Delivery fee fallback

```dotenv
WEB_DELIVERY_FEE=2.00
```

The administrator-saved Store Settings value takes precedence after the delivery-fee column exists and a value has been saved. The environment value is used when the database value is `NULL` / not yet configured. The setting affects new delivery orders only.

### Backup and restore settings

The backup feature has defaults in `config/backup.php`. Add or override these values in `.env` when needed:

```dotenv
# Optional absolute paths. If blank, the application tries common XAMPP/PATH locations.
BACKUP_MYSQLDUMP_PATH=D:/Xamm/mysql/bin/mysqldump.exe
BACKUP_MYSQL_PATH=D:/Xamm/mysql/bin/mysql.exe

# Private archive directory relative to storage/app/private.
BACKUP_DIRECTORY=backups

# Automated backup and retention schedule.
BACKUP_DAILY_AT=02:00
BACKUP_TIMEZONE=Asia/Phnom_Penh
BACKUP_RETENTION_DAYS=30

# Uploaded ZIP limit in KB (1,024,000 KB = approximately 1 GB).
BACKUP_UPLOAD_MAX_KB=1024000

# Restore safety limits.
BACKUP_MAX_FILES=20000
BACKUP_MAX_UNCOMPRESSED_BYTES=21474836480
```

Use paths and port settings from the actual XAMPP/MariaDB installation. On the Windows setup used for development, the code checks common XAMPP locations including `D:\Xamm\mysql\bin` and `C:\xampp\mysql\bin`. Explicit paths are recommended when more than one MySQL/MariaDB installation exists. After changing `.env`, run:

```bash
php artisan optimize:clear
```

## English and Khmer language support

The application provides English (`en`) and Khmer (`km`) interface options. The language switcher submits the selected locale to `POST /language`, stores it in the browser session, and the `SetLocale` middleware applies it to the Laravel request.

### How Khmer UI translation works

- `lang/km.json` contains Laravel JSON translation entries.
- `public/assets/lang/km-ui.json` contains the approved exact-string dictionary used by the browser-side interface translator.
- `public/assets/js/cc-khmer-ui.js` translates matching interface text and supported attributes, and continues to observe newly inserted interface elements.
- `resources/views/partials/locale-bootstrap.blade.php` loads the Khmer dictionary into the initial HTML response for Khmer sessions. This avoids waiting for a separate dictionary fetch before the first translation pass.
- The Khmer page is revealed after the initial translation pass. The translator fails open if its script cannot load, so a page is not left hidden indefinitely.
- The locale bootstrap is included by the Admin, Cashier, POS, and public storefront layouts.
- The translator skips customer-generated content and other user-entered values such as customer messages, notes, product descriptions, and review text; it is intended for approved UI wording, not machine translation of user data.

### Adding or changing a translation

Keep matching entries synchronized in both dictionaries when the same UI phrase is used in the server-side and browser-side interface:

```json
{
    "Backups & restore": "ការបម្រុងទុក និងការស្តារទិន្នន័យ",
    "Create backup now": "បង្កើតការបម្រុងទុកឥឡូវនេះ"
}
```

Merge new keys into the existing JSON objects; do not replace the full dictionaries with a small sample. Preserve valid JSON syntax, double quotes, commas, and unique keys. Use `php artisan optimize:clear` and a hard browser refresh (`Ctrl + F5`) after changing the files.

> **Translation completeness:** The browser translator only changes exact phrases that exist in `public/assets/lang/km-ui.json`. When adding or updating a screen, review its Blade view for new labels, button text, modal text, placeholders, and notifications, then add the missing exact keys to the browser dictionary (and to `lang/km.json` when the same phrase is used by Laravel server-side translations). In particular, verify the Backup & restore page in Khmer mode after adding its backup-specific phrases; unmatched text remains in English.

## Backup and restore

The administrator sidebar includes **Admin → Backups & restore** at `/admin/backups`. It creates downloadable ZIP archives containing a logical dump of the configured MySQL/MariaDB database and the application's managed uploaded files.

### Backup features

- Manual backup creation from the administrator page.
- Daily scheduled backup (default `02:00`, `Asia/Phnom_Penh`).
- Automatic retention cleanup (default retention: 30 days; cleanup event runs daily at `03:15`).
- Backup history with creation time, type, archive name, size, file count, and manifest status.
- Download of a stored ZIP archive.
- Restore from a stored archive or an administrator-uploaded ZIP archive.
- JSON manifest with database/archive metadata and SHA-256 file-integrity hashes.
- Manifest, entry path, size, and SHA-256 validation before restore is allowed to replace live data.
- Automatic `pre_restore` safety backup before the selected database is imported.
- Private backup storage outside the public web root.

### What a backup contains

| Included item | Source path / archive content |
|---|---|
| Configured database | `database.sql` logical dump created with `mysqldump` / compatible client |
| Product/shop images | `public/images` |
| Other public uploads | `public/uploads` |
| Public Laravel storage files | `storage/app/public` |
| Backup metadata and integrity records | `manifest.json` |

The database export includes routines, triggers, and events as requested by the MySQL dump command. Only the configured database is exported; this is not a complete database-server backup.

### What a backup does not contain

The ZIP does **not** back up the Laravel source code, `.env` file, credentials, `vendor/`, `node_modules/`, logs, sessions, cache files, or the rest of the server configuration. Keep a separate secure copy of the application source, environment settings, deployment configuration, and any files outside the managed directories. Generated backup archives should remain private and must not be committed to GitHub.

### Restore safeguards and behavior

1. Only an active administrator can open the backup page, download archives, create backups, restore data, or delete archives.
2. Restoring from a stored or uploaded archive requires the administrator to type `RESTORE` exactly in the confirmation dialog.
3. The archive must contain a valid Sarana backup manifest. File paths, expected sizes, and SHA-256 hashes are checked before database import begins.
4. The system creates a `pre_restore` backup of the currently configured database and managed upload folders before import.
5. The SQL dump is imported into the database currently configured in `.env`. Restoring an archive does not modify `.env` or switch the application to a different database name.
6. After the database import, the managed folders `public/images`, `public/uploads`, and `storage/app/public` are replaced with the archived copies.
7. Successful and failed operations are reported through the backup page's styled notifications. Restore/delete actions use branded confirmation/progress dialogs rather than the browser's native `confirm()` prompt.

> **Destructive operation warning:** A failed MySQL import can leave the target database partially restored because MySQL DDL statements are not generally rolled back as one transaction. A pre-restore safety archive is retained so an administrator can restore the previous state. Test restore in a disposable database/environment before relying on the feature for production recovery. Do not repeatedly retry a failed restore against production data without checking the logs and confirming the safety archive exists.

### PHP ZIP extension

The PHP ZIP extension is required for both creating and restoring ZIP archives. In XAMPP on Windows:

1. Open XAMPP Control Panel.
2. Beside Apache, choose **Config → PHP (php.ini)**. This opens the Apache PHP configuration.
3. Find `;extension=zip` and remove the leading semicolon so it reads `extension=zip`.
4. Confirm `extension_dir` points to the correct PHP `ext` folder and that `php_zip.dll` exists there.
5. Save the file and restart Apache.
6. Verify command-line PHP too, because CLI and Apache may use different `php.ini` files:

   ```cmd
   php --ini
   php -m | findstr /i zip
   D:\Xamm\php\php.exe -r "var_dump(extension_loaded('zip'));"
   ```

If the website still reports ZIP disabled, check the `php.ini` loaded by Apache separately from the one shown by `php --ini`.

### MySQL/MariaDB client tools and Windows socket fix

The web process must be able to execute `mysqldump` and `mysql` (or MariaDB-compatible binaries). The backup manager checks configured absolute paths first, then common XAMPP locations and the system `PATH`.

For the XAMPP installation used in development, these are the usual values:

```dotenv
BACKUP_MYSQLDUMP_PATH=D:/Xamm/mysql/bin/mysqldump.exe
BACKUP_MYSQL_PATH=D:/Xamm/mysql/bin/mysql.exe
DB_HOST=127.0.0.1
DB_PORT=3306
```

The Windows compatibility update explicitly passes key system environment variables (`SYSTEMROOT`, `WINDIR`, `PATH`, `TEMP`, `TMP`, and `COMSPEC`) to the MySQL child processes. This addresses a Windows Winsock initialization failure reported as error `10106` when `mysqldump.exe` is launched from PHP/Apache.

If error `10106` occurs, first run the same `mysqldump.exe` command directly from CMD using `--host=127.0.0.1` and the actual port/database. Only if the direct CMD test also fails with 10106 should you consider resetting Winsock from an elevated Command Prompt (`netsh winsock reset`) and restarting Windows. Do not reset Winsock when a direct MySQL client test succeeds; investigate the PHP/Apache process environment instead.

### Scheduled backups

Laravel defines two scheduler events in `routes/console.php`:

| Event | Default time | Default behavior |
|---|---:|---|
| Daily backup | `02:00` | Creates a full backup with type `scheduled`. |
| Retention cleanup | `03:15` | Deletes generated backup ZIP files older than `BACKUP_RETENTION_DAYS` (30 days by default). |

Each event has a unique name and uses `withoutOverlapping()` to avoid concurrent runs. **Laravel does not run the scheduler by itself.** The operating system must invoke `php artisan schedule:run` every minute.

#### Windows Task Scheduler

Create a Windows scheduled task:

- **Program/script:** the full path to the PHP executable used by XAMPP, for example `D:\Xamm\php\php.exe`.
- **Add arguments:** `artisan schedule:run`.
- **Start in:** the project directory containing `artisan`, for example `D:\26 - Sarana\Sarana_01`.
- **Trigger:** repeat every 1 minute indefinitely.

Use the PHP executable and project directory from your own installation. Keep the computer/server running at the scheduled time.

#### Linux cron

Add a cron entry using the PHP executable and project path used by the web application:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

### Upload limits and storage

The application-level uploaded ZIP limit defaults to 1 GB (`BACKUP_UPLOAD_MAX_KB=1024000`). PHP and the web server can impose smaller limits. Check `upload_max_filesize`, `post_max_size`, `max_execution_time`, `max_input_time`, and web-server request-body limits. `post_max_size` should be larger than `upload_max_filesize`.

The restore guard defaults to 20,000 files and 20 GiB of uncompressed content. Increase `BACKUP_MAX_FILES` or `BACKUP_MAX_UNCOMPRESSED_BYTES` only after checking available disk space and the risk of processing very large archives.

Backups are stored in:

```text
storage/app/private/backups
```

This directory is outside the public web root. Keep it writable by the PHP process, protected by operating-system permissions, included in a separate off-site backup plan where appropriate, and excluded from Git commits.

### Recommended backup/restore test

1. Create a manual backup from **Admin → Backups & restore**.
2. Download the archive and verify that it contains `manifest.json`, `database.sql`, and the expected image/upload entries.
3. Copy the application and database into a disposable test environment.
4. Restore the downloaded archive in that test environment.
5. Verify login, products, product images, shop logo, inventory, purchases, sales, orders, customer records, settings, and reports.
6. Confirm a `pre_restore` archive was created before the test restore.
7. Configure and verify the daily scheduler and retention cleanup.

A ZIP downloaded from GitHub is source code, not a backup of the live database. Create the first operational backup after installing/configuring this feature against the intended database.

## Payment integration

ABA PayWay is optional. Local cash-based POS workflows can be tested without enabling KHQR. KHQR sandbox transactions require merchant credentials issued for the sandbox environment.

Important behavior:

- Use the sandbox base URL with sandbox credentials.
- `PAYWAY_CALLBACK_URL` must be a public HTTPS URL reachable by ABA. A local `127.0.0.1` address is not reachable by the provider.
- For local testing, a public HTTPS development tunnel such as ngrok can expose the Laravel server. Update `APP_URL` and `PAYWAY_CALLBACK_URL` to the currently assigned HTTPS host.
- After changing `.env`, run `php artisan optimize:clear` and restart local services if needed.
- A browser redirect or QR scan is not proof of payment. The application must verify the transaction server-to-server with ABA before marking an order paid.
- Never commit merchant IDs/secrets, raw payment responses, QR payloads, transaction references, or bank references.

## Google sign-in (optional)

Google OAuth supports the staff/customer sign-in flow when correctly configured. Set the OAuth client ID, secret, and redirect URI in `.env`, configure the same redirect URI with Google, and keep credentials private. Test staff and customer audiences separately; OAuth sign-in must not grant a more privileged role than the account's assigned role.

## Telegram notifications and fulfillment

Telegram is an additional notification/management channel; the Admin Orders page remains the primary in-app workflow. The bot and public HTTPS webhook must be configured before Telegram can deliver messages or process buttons.

### Configure the bot

1. Create a bot using Telegram's official `@BotFather` and keep its token private.
2. Add the bot to the intended administrator group and grant permission to send messages.
3. Set `TELEGRAM_GROUP_CHAT_ID` to the current numeric group/supergroup ID. When a group is upgraded to a supergroup, Telegram assigns a new chat ID; update the environment value if the old ID returns a group-upgraded error. Supergroup IDs commonly begin with `-100`.
4. Add trusted numeric Telegram user IDs to `TELEGRAM_ADMIN_IDS`.
5. Generate a random webhook secret:

   ```bash
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

6. Configure the `.env` values and run `php artisan optimize:clear`.
7. Register the webhook at `https://YOUR-DOMAIN/telegram/webhook` with Telegram's `setWebhook` method and the same secret in the `secret_token` property. The Laravel endpoint validates the Telegram secret header before processing updates.

Telegram actions are restricted to configured administrators and update the shared order/delivery records. A Telegram outage does not mean an order was not saved: verify the order in Admin → Orders and inspect `storage/logs/laravel.log` for notification errors.

If a bot token has appeared in a URL, log, screenshot, public issue, or repository, revoke it with BotFather and replace it in `.env`. Removing it from the latest commit does not invalidate an exposed token.

## Order and delivery workflows

### Website order

1. A signed-in customer creates a cart and submits checkout.
2. The order, line items, and (for a delivery order) delivery information are saved in the database.
3. For KHQR, the order remains unpaid until server-side PayWay verification confirms the transaction amount and currency.
4. Paid orders wait for fulfillment confirmation; COD orders remain unpaid while staff prepare and dispatch them.
5. Authorized staff advance the order through the correct fulfillment stages. The customer tracking page reads the same order state and event history.

### POS delivery order using COD

1. In POS, select **Order type: Delivery**.
2. Select **Payment method: Cash on Delivery**.
3. Enter the customer contact and delivery address, then save the order. It should remain **Unpaid**.
4. Open **Admin → Orders**. The unified delivery panel appears for Delivery-type orders from POS and the website.
5. Advance one step at a time: `Pending → Confirmed → Preparing → Ready → Out for delivery → Delivered`.
6. At final handoff, the Cash Settlement dialog displays the amount due. Enter the amount actually received, confirm cash was received, and optionally add a note.
7. The server checks that the amount matches the order's final price. Only then is the order marked Paid/Delivered and the cash-collection event recorded.

For pickup orders, the fulfillment path ends at collection and does not include an Out for delivery stage.

### COD accounting rule

Cash collection for COD does not create a second manual sales-income transaction. Sales revenue is recognized from the order's paid state. This avoids double-counting the same sale in finance reports. Do not mark a COD order paid before the customer actually hands over the money.

## AJAX updates and admin notifications

The admin experience uses timed AJAX polling (not WebSockets) to keep order information and actionable counts current without repeatedly reloading the entire page.

- Admin action badges refresh approximately every 10 seconds and hide when their count is zero; large counts display as `99+`.
- The Orders list checks for new/updated orders approximately every 12 seconds and updates relevant table content without a full-page reload.
- New website orders can trigger an in-app toast/notification with the order reference and a link to the Orders tab.
- Polling pauses while the browser tab is hidden and resumes when it becomes visible.
- Delivery status transitions update the visual stepper, order/payment indicators, and activity information after the server confirms the change.
- Telegram message refresh is deferred after the HTTP response so a slow Telegram API request does not unnecessarily delay the admin action.

The database remains the source of truth. Notifications are a convenience layer; a failed Telegram or browser notification does not undo a saved order. Staff should check Admin → Orders for pending work after reconnecting.

### Admin action badge meaning

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
- Order items store a sale-time `unit_cost` snapshot for more reliable COGS reporting on new sales.
- Sales revenue is derived from paid POS/website orders.
- Manual finance entries are intended for non-sale expenses (for example utilities, rent, maintenance, or operating supplies). Do not enter a paid order as manual income as well.
- Inventory purchases are cash outflows; inventory cost is recognized as COGS when items are sold. Do not subtract the same purchase cost again from profit.
- Historical cost backfills may be estimates where sale-time unit cost was not recorded originally.

## Useful routes

Exact route definitions are in `routes/web.php`. Main paths include:

| Area | Path | Access |
|---|---|---|
| Staff login | `/` | Public login page |
| Language switch | `POST /language` | Session locale (`en` or `km`) |
| Admin dashboard | `/admin` | Administrator |
| POS | `/pos` | Administrator / Cashier |
| Cashier workspace | `/cashier` | Cashier |
| Storefront shop | `/store/shop` | Public |
| Storefront about | `/store/about` | Public |
| Storefront shipping | `/store/shipping` | Public |
| Storefront contact | `/store/contact` | Public |
| Storefront product | `/store/product/{productId}` | Public |
| Storefront cart | `/store/cart` | Public cart |
| Customer login | `/store/login` | Public |
| Customer registration | `/store/register` | Public |
| Customer account | `/store/account` | Customer |
| Customer inbox | `/store/inbox` | Customer |
| Customer checkout | `/store/checkout` | Customer |
| Customer order tracking | `/store/orders/{orderId}` | Owner customer account |
| PayWay callback | `POST /store/payments/payway/callback` | Provider callback |
| Telegram webhook | `POST /telegram/webhook` | Telegram secret + configured user checks |
| Admin orders | `/admin/orders` | Administrator |
| Admin delivery register | `/admin/delivery` | Administrator |
| Customer enquiries | `/admin/customer-service` | Administrator |
| Admin reviews | `/admin/reviews` | Administrator |
| Inventory | `/admin/inventory` | Administrator |
| Store settings | `/admin/meanleap/setting` | Administrator |
| Finance | `/admin/expense-income` | Administrator |
| Backup & restore | `/admin/backups` | Administrator |

Use the application navigation where possible; some legacy route names/paths are retained for compatibility. Legacy GET requests under `/web` redirect to the canonical `/store` storefront path.

## Run and test

Start the application:

```bash
php artisan serve
```

Run frontend hot reload in another terminal when needed:

```bash
npm run dev
```

Build production frontend assets:

```bash
npm run build
```

Useful validation commands:

```bash
composer check-platform-reqs
php artisan optimize:clear
php artisan route:list
php artisan schedule:list
php artisan test
```

### Recommended manual test checklist

- [ ] Administrator and cashier login work; cashier cannot access administrator-only routes.
- [ ] Customer registration/login and customer-owned order access work.
- [ ] Product browsing, cart updates, and checkout work.
- [ ] Website and POS orders save the correct items, totals, source, and payment state.
- [ ] KHQR is marked paid only after server-side PayWay verification.
- [ ] POS Delivery + COD appears in Admin → Orders and remains unpaid until final cash handoff.
- [ ] Wrong COD amount or missing confirmation is rejected; correct amount records collector/time/note.
- [ ] Delivery stage changes update the admin stepper and customer tracking page.
- [ ] New website orders appear in Admin → Orders and can produce in-app notifications while the admin page is open.
- [ ] Admin action badge counts match pending items in each tab.
- [ ] Delivery fee is used for new POS and website delivery orders; pickup/takeaway is not charged.
- [ ] Optional fields can be left blank while required and conditional fields remain validated.
- [ ] Signed-in customer enquiries appear in My Account → Inbox; administrator replies are visible to the customer.
- [ ] Guest enquiries remain manageable by the administrator and are not exposed to another account.
- [ ] Reviews remain unpublished until approved by an administrator.
- [ ] Stock products reduce quantity; Non-stock products do not.
- [ ] Finance totals do not double-count paid orders or inventory purchases.
- [ ] English/Khmer switching works across Admin, Cashier, POS, and storefront without a visible English-to-Khmer flash.
- [ ] Backup creation produces a private ZIP with database dump, manifest, and expected uploaded files.
- [ ] A stored backup can be restored in a disposable environment and a `pre_restore` backup is created first.
- [ ] Daily backup and retention events appear in `php artisan schedule:list` and run when the OS scheduler is enabled.
- [ ] Telegram notification and webhook work with the current bot token and group/supergroup chat ID.

Static syntax checks are not a substitute for browser/database/payment testing. Test with a backed-up or disposable database and sandbox payment credentials.

## GitHub and security checklist

Review every staged file before pushing to a public repository.

### Never commit

- `.env`, production environment files, API secrets, passwords, OAuth client secrets, PayWay merchant keys, Telegram bot tokens, webhook secrets, ngrok tokens, or private administrator IDs.
- Generated backup ZIP files, live SQL exports, customer exports, unredacted database dumps, personal phone/email/address details, payment QR payloads, callback payloads, transaction responses, or bank references.
- Laravel logs, local session/cache files, generated dependencies, `vendor/`, or `node_modules/`.
- Uploaded customer files or private media unless they are intentionally public and licensed for redistribution.

Backup archives contain database records and uploaded shop/customer files. Even though the application stores them outside the public web directory, treat them as sensitive data. Do not upload backup archives to GitHub.

The demo SQL file `database/sarona_demo.sql` should be reviewed and anonymized before public publication. Keep numbered schema-upgrade scripts tracked because they are part of the documented setup process.

The root `.gitignore` excludes common Laravel runtime/dependency files and `.env`; verify your current copy still does. Add local dump patterns if needed without ignoring the numbered schema-upgrade scripts:

```gitignore
# Local database backups/exports (keep numbered schema upgrade scripts tracked)
/database/*backup*.sql
/database/*dump*.sql
/database/sarona01*.sql
*.sql.gz

# Generated backup archives
/storage/app/private/backups/*
!/storage/app/private/backups/.gitignore
```

Before staging:

```bash
git status --short
git ls-files .env
git diff --check
```

Review the staged file list before committing:

```bash
git add README.md
# Stage the reviewed source/patch files explicitly, or use git add -A only after checking git status.
git status --short
git diff --cached --check
git diff --cached --stat
```

Suggested publish workflow (change `main` if your default branch is different):

```bash
git fetch origin
git pull --rebase origin main
git commit -m "Update backup restore and Khmer localization"
git push origin HEAD
```

If the remote rejects the push, inspect and reconcile the incoming commits. Do not use `git push --force` to resolve a normal push rejection. If a secret was ever committed, rotate it; deleting it in a later commit does not remove it from Git history.

## Troubleshooting

### Route not found

```bash
php artisan optimize:clear
php artisan route:list
```

Confirm the route exists in `routes/web.php` and the controller import/name is correct.

### SQL table or column not found

- Confirm `.env` points to the intended database.
- Import the base demo schema only for a fresh database.
- Check which additive SQL scripts have already been applied and apply only the missing scripts in order.
- Do not import the demo dump over a working database.

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

Check variable spelling/quoting and restart the local server or Apache where necessary.

### Backup reports that PHP ZIP is disabled

- Open XAMPP Control Panel → Apache → **Config → PHP (php.ini)**.
- Enable `extension=zip` by removing the leading semicolon from `;extension=zip`.
- Confirm `extension_dir` points to the active PHP `ext` directory and `php_zip.dll` exists.
- Restart Apache.
- Verify CLI PHP separately using `php --ri zip` or the exact XAMPP PHP executable.

### Backup cannot find `mysqldump` or `mysql`

Set `BACKUP_MYSQLDUMP_PATH` and `BACKUP_MYSQL_PATH` to the absolute executable paths in `.env`, confirm the PHP process can read/execute them, then run `php artisan optimize:clear`. Ensure the database user has privileges to export the application's tables, views, routines, triggers, and events. Restore also needs privileges to create, alter, drop, and insert into the application's database objects.

### MySQL client reports Windows socket error 10106

The Windows backup fix passes `SYSTEMROOT`, `WINDIR`, `PATH`, `TEMP`, `TMP`, and `COMSPEC` to the `mysqldump` / `mysql` child processes. Prefer `DB_HOST=127.0.0.1` and set the explicit XAMPP tool paths if multiple database installations exist. Test `mysqldump` directly from CMD. Only if the direct client also reports error 10106 should you consider `netsh winsock reset` from an elevated Command Prompt and then restart Windows.

### Backup scheduler is not running

Laravel only registers scheduler events; Windows Task Scheduler or Linux cron must call `php artisan schedule:run` every minute. Check `php artisan schedule:list`, confirm the task's **Start in** folder contains `artisan`, and verify it uses the same PHP executable and `.env` as the website.

### Restore fails

- Read the message shown by the Backup & restore page and inspect `storage/logs/laravel.log`.
- Confirm the ZIP contains a valid `manifest.json`, `database.sql`, and managed file entries.
- Check available disk space, PHP upload/time limits, MySQL client path, and database permissions.
- Check whether the automatic `pre_restore` archive exists before attempting another restore.
- Remember that a failed SQL import can leave a partially restored database; recover from the safety archive in a disposable/test environment first.

### Khmer appears after a brief English flash, or some words remain English

- Confirm the current versions of `resources/views/partials/locale-bootstrap.blade.php`, `public/assets/js/cc-khmer-ui.js`, and `public/assets/lang/km-ui.json` are installed.
- Run `php artisan view:clear` and `php artisan optimize:clear`, then press `Ctrl + F5`.
- Add missing exact UI strings to `public/assets/lang/km-ui.json` and, where required by Laravel JSON translation, `lang/km.json`. Keep JSON valid and avoid duplicate keys.
- The translator intentionally skips customer-entered text, product descriptions, and other user-generated content.

### PayWay callback or KHQR verification fails

- Confirm sandbox credentials and the base URL match.
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

- The custom business schema is partly SQL-managed; historical Laravel migrations do not independently recreate the complete project database.
- `database/sarona_demo.sql` is a base demo snapshot and requires the documented SQL upgrade scripts for newer workflows. Sanitize demo data before any public release.
- Historical COGS may be approximate where sale-time unit cost was not originally recorded.
- AJAX updates use timed polling, not WebSockets or guaranteed push delivery. The database is authoritative; a badge/toast is not a durable message queue.
- The browser-side Khmer translator translates approved exact UI strings. It is not a general-purpose translator for all arbitrary/dynamic content; the Backup & restore page needs its specific dictionary entries to be merged to be fully localized.
- Backup archives protect the configured database and three managed upload locations, not the full server/application deployment. Keep separate secure copies of source, environment settings, and infrastructure configuration.
- A failed database import may partially change the target database; use the `pre_restore` safety archive to recover.
- Telegram and ABA PayWay require valid external configuration and reachable HTTPS endpoints for callbacks/webhooks.
- PayWay is documented for sandbox testing unless production credentials, callback URL, and provider approval are separately configured.
- CSV exports are spreadsheet-compatible CSV rather than native `.xlsx`; print/PDF reports use the browser's print-to-PDF function where implemented.
- A production deployment still needs environment hardening, HTTPS, least-privilege database credentials, off-site backups, monitoring, secure mail configuration, and end-to-end transaction testing.

## Contributing

For a project contribution or local change:

1. Create a feature branch.
2. Keep schema changes additive when practical and include a numbered SQL upgrade plus an installation note.
3. Validate authorization, request data, stock movements, order totals, and payment transitions on the server.
4. Run PHP, route, frontend build, and relevant manual workflow checks.
5. Update this README whenever installation, integrations, routes, language support, backup behavior, or workflows change.

## License

This project is distributed under the [MIT License](LICENSE). The `composer.json` package metadata also declares `MIT`.

Before publishing, confirm that you or your project team have the rights to license all included code, images, fonts, icons, and other assets. Third-party assets/packages remain subject to their own licenses.

---

**Coffee Corner Aura** — manage the shop, serve customers, and keep every order accountable.
