# SK Coffee Aura

**Coffee Shop Management System · Laravel 13 · POS · Online Ordering · Inventory · Delivery · Finance · Sales Analytics · Audit Trail · PDF Receipts & Reports · Telegram OTP · English / Khmer**

SK Coffee Aura (also referred to in the repository as **Sarana 01** / **SK Coffee**) is a Laravel-based coffee shop management application. It combines a customer-facing storefront, staff point of sale (POS), order and delivery workflows, product and inventory management, purchasing, finance reports, customer service, store settings, and administrator tools.

> **Project status:** This is an academic / graduation-demo project. This README was updated on **22 November 2026** to document the current storefront receipt download, item-specific POS discounts, order/report totals, audit PDF exports, finance filters, and expense-summary improvements in addition to the existing Telegram verification, delivery, inventory, backup, and security features. Use a local or staging database and sandbox payment credentials for demonstrations. Complete the security, backup, payment, and deployment checks before using it with real customer data or payments.

---

## Contents

- [Overview](#overview)
- [Features](#features)
- [Customer Telegram OTP and phone verification](#customer-telegram-otp-and-phone-verification)
- [Customer receipt downloads](#customer-receipt-downloads)
- [POS product-specific discounts](#pos-product-specific-discounts)
- [Order management and report totals](#order-management-and-report-totals)
- [POS receipt corrections and audit](#pos-receipt-corrections-and-audit)
- [Product sales analytics](#product-sales-analytics)
- [POS price and discount audit](#pos-price-and-discount-audit)
- [PDF and CSV report exports](#pdf-and-csv-report-exports)
- [Finance filters and expense totals](#finance-filters-and-expense-totals)
- [Admin navigation and date filters](#admin-navigation-and-date-filters)
- [Security hardening](#security-hardening)
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
- Customer registration protected by Telegram account linking, phone-contact verification, and a one-time six-digit OTP before a new account is activated.
- Temporary registration records are kept separate from active customer login accounts until verification succeeds.
- Checkout for store pickup or home delivery.
- Delivery fee shown during checkout and on the shipping-information page.
- Optional ABA PayWay KHQR checkout when valid merchant credentials and a reachable HTTPS callback are configured.
- Customer order tracking with payment state, fulfillment progress, and order activity.
- Customer-owned PDF receipt download from order tracking and order history. Receipt access is restricted to the signed-in owner of the order.
- Review submission for eligible completed orders; new reviews remain pending until an administrator approves them.
- Contact/enquiry form for customer questions.
- Customer inbox for signed-in users, with automatic enquiry acknowledgement, follow-up replies, store replies, and unread indicators.

### POS and cashier workspace

- POS product catalogue, category/product selection, cart, checkout, and order recording.
- Product-specific discounts: staff select the cart line to discount, choose a quick percentage preset or enter a custom percentage, and see the saving and updated line total before applying it. The discount affects only the selected product line, not every item in the cart.
- The discount number control changes in 5-percentage-point increments when its up/down controls are used. The custom value remains editable, subject to the configured role limit.
- Per-item discount amounts are accumulated into the order's monetary discount total. Admin order views and report totals display the discount as a currency value (for example, `$2.00`) rather than showing a percentage as the order discount amount.
- Cash, KHQR/PayWay, card, and Cash on Delivery (COD) payment options where configured.
- Walk-in, takeaway, and delivery fulfillment workflows.
- POS delivery orders collect customer and delivery information and create a linked delivery record.
- COD orders remain **Unpaid** until the delivery handoff is completed and the exact amount is collected.
- Cashier order queue and order-status workflow.
- Stock-tracked products are checked and deducted according to the configured stock rules; non-stock products are not deducted from inventory.
- POS receipt correction for eligible completed, paid, cash POS orders, with original/corrected snapshots, correction reason, staff attribution, stock reconciliation, and cash-adjustment confirmation.
- Server-side price-override and item-discount permissions; cashier overrides/discounts are blocked by default, while authorized adjustments must stay within the configured role limit. Discount/price changes require a reason where the adjustment workflow requests one and are recorded in the POS audit history.
- Quick catalogue-product creation from POS is administrator-only after the security-hardening patch.

> **Important:** Cash on Delivery is a _payment method_, not an order type. For a home-delivery COD order, choose **Order type: Delivery** and **Payment method: Cash on Delivery**. A walk-in or takeaway COD selection is not a delivery order and does not receive delivery-stage controls.

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
- Income/expense and business reports, with CSV exports and print-optimized PDF reports where available.
- **Product Sales Analytics** with date presets/custom range, product/category breakdown, quantities sold, item sales subtotal, order source, staff filters, CSV export, and a print/PDF report.
- **Order Correction Audit** showing receipt correction history, staff member, before/after receipt details, correction reason, and recorded cash adjustments, with a readable receipt-comparison PDF layout.
- **POS Price & Discount Audit** showing recorded POS price overrides and item discounts, the acting staff member, reason, order reference, date, and adjustment type, with a readable event-based PDF layout.
- **Order management totals and filters:** filter POS orders by administrator/cashier, show the date immediately after Order ID in the order list, and display discount as a monetary amount. The order report places Date after Queue No and totals Subtotal, Discount, and Final Total.
- **Sales & Expenses reporting:** filters are placed above the KPI cards; date filters drive the financial summary, while ledger filters refine the expense list. Total Expenses includes the matching inventory-purchase and operating-expense outflows.
- Shared date-filter controls across supported reporting tabs: Today, This week, This month, All time, and a custom date range.
- The Orders tab can filter POS orders by the administrator/cashier recorded as the order staff actor; website orders can be shown separately when no POS staff actor is attached.
- Collapsible administrator sidebar groups for workspace, catalogue, customers/support, sales/audit, inventory/operations, and administration.
- **Customer Accounts** archive action that disables login and releases the account phone/Telegram identity while preserving customer/order history; email remains reserved.
- Administrator-only **Backups & restore** workspace (see [Backup and restore](#backup-and-restore)).

### Customer Telegram OTP and phone verification

- The customer submits the normal website registration form first. Laravel stores a temporary registration draft; it does not create an active customer login account at this point.
- The customer opens a short-lived, one-use Telegram deep link and presses **Start** in a private chat with the configured bot.
- The bot asks the customer to share their Telegram phone contact using Telegram's contact-sharing action.
- The webhook checks that the shared contact belongs to the Telegram user who sent it, then compares the normalized phone number with the phone number entered on the website. Cambodian local and `+855` formats are normalized for comparison.
- Only after phone ownership is confirmed does the bot send a six-digit OTP. The customer enters that code on the website verification page; the active account is created after successful verification.
- OTP values are stored as hashes, are time-limited, and have a limited number of verification attempts. Telegram connection links and temporary registration drafts also expire.
- The same Telegram webhook handles private customer registration messages and administrator-group fulfillment callbacks. Register both `message` and `callback_query` in Telegram `allowed_updates`.
- Customer account archival is not a hard delete: the login identity can be disabled and the phone/Telegram identity released while the historical customer profile and orders remain available for reporting. The email remains reserved according to the account-archive workflow.

Keep `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, and `TELEGRAM_WEBHOOK_SECRET` private. The webhook must use a public HTTPS URL and the configured Telegram secret header. See [Telegram notifications and fulfillment](#telegram-notifications-and-fulfillment).

### Customer receipt downloads

- A signed-in customer can download a PDF receipt from the customer's order-tracking page and order history.
- The receipt is generated for the selected order and includes its order reference, customer/order details, line items, quantities, prices, discount information, applicable delivery/payment information, and order totals supported by the saved record.
- The receipt endpoint checks order ownership. A customer must not be able to download another customer's receipt by changing the order ID in the URL.
- Receipt downloads do not change the order, payment status, stock, or delivery state.
- The receipt PDF feature uses `barryvdh/laravel-dompdf`. Install dependencies from the committed `composer.lock` with `composer install`; if the package is missing from the local checkout, add the project-approved version with `composer require "barryvdh/laravel-dompdf:^3.1.2"`.

### POS product-specific discounts

- A discount is applied to the selected POS cart line only. Other products in the same order keep their original line prices unless they are selected and discounted separately.
- Staff can choose a quick percentage preset or enter a custom percentage. The discount control's increment/decrement arrows use **5 percentage points per click**.
- The discount dialog previews the selected product, original line amount, discount saving in USD, and new line total before the user confirms.
- The permitted percentage is capped by the administrator/cashier limit configured in `config/pos.php` and the corresponding environment variables. The server validates the submitted value; hiding or disabling a browser control is not sufficient authorization.
- A reason is collected for an authorized discount adjustment where the POS discount workflow requires it. Recorded discount events are available in the POS Price & Discount Audit when audit logging is enabled.
- The order-level Discount displayed in the Admin Orders list and reports is the **sum of the monetary discounts applied to individual product lines**, not a percentage label. Older orders may retain legacy discount data; do not rewrite historical transactions merely to make their display match the new entry workflow.
- Test a multi-product sale by applying a discount to one line only, then verify the other line, order discount amount, final total, receipt, and audit record.

### Order management and report totals

- The Admin Orders list supports filtering POS records by the administrator/cashier associated with the order. Website orders without a POS staff actor can be displayed separately where the filter provides that option.
- The Orders list places the order date immediately after Order ID and displays the order Discount as a currency amount.
- The order report places Date immediately after Queue No, making the queue reference and transaction date easy to scan together.
- The order report shows aggregate **Subtotal**, **Discount**, and **Final Total** values for the selected result set. The final total follows the saved order calculation, including applicable delivery charges; a delivery fee is not a product discount.
- Exports and totals should follow the currently selected filters and include all matching records, not only rows visible on the current paginated screen.

### POS receipt corrections and audit

- The Orders tab links eligible receipts to the POS correction workspace instead of relying on a simple status-marking action.
- The correction workflow records the original and corrected receipt snapshots, staff actor, timestamp, reason/notes, changed line items, and any cash collection/refund adjustment supported by the existing correction schema.
- Stock changes are reconciled through the correction workflow; users should not manually edit order-item rows to imitate a correction.
- The **Order Correction Audit** page reads the existing `order_corrections` history and provides receipt-level review of who changed a receipt and what was recorded.
- A correction may be restricted when an order is not a completed/paid POS cash order, is a delivery order, has an external payment record, has a cash-ledger transaction, or has missing/unverifiable item or stock history. The Orders page displays the detected reason where the eligibility check can determine one.
- If a correction has a pending cash adjustment, confirm/settle that adjustment through the dedicated workflow before attempting another correction.
- The correction screen provides a **Back to Orders** link and carries the originating Orders URL/query string so the user can return to the previous date/staff/status filters.
- The compact correction UI reduces unused space but does not change the underlying accounting or stock rules.

### Product sales analytics

- The **Product Sales Analytics** page aggregates saved `order_items` quantities against their parent orders and products.
- Date presets and a custom date range are available, with filters for product, category, order source, and staff where the data supports the filter.
- POS orders use `orders.cashier_id` to identify the staff account recorded on the order. Website orders normally have no POS staff actor and can be filtered separately.
- The report includes units sold, affected orders, product/category rankings, item sales value, and supported daily sales trends with CSV and print/PDF export.
- The documented sales view counts paid, non-cancelled orders. Item sales subtotal is calculated from recorded item quantity and item price before item-specific discounts; it can differ from the final amount paid on the whole order. Review the separate discount and final-total figures when reconciling net sales.
- Product corrections are reflected using the currently saved order items. This is not a separate immutable snapshot of every historical product-line edit unless the correction history is consulted.

### POS price and discount audit

- Authorized POS price overrides and discount events are recorded in `order_status_history` using the event types `pos_price_override` and `pos_discount_applied`.
- The **POS Price & Discount Audit** page displays the events recorded by the POS audit handler, including order reference, staff identity, event type, timestamp, and available reason/adjustment details. It supports date, staff, and event-type filtering, CSV export, and a print/PDF report. The PDF presents price overrides and discount events as readable event records rather than forcing all fields into one very wide table.
- A price-override event is written only when the submitted selling price differs from the catalogue price. A discount event is written when a non-zero discount is applied.
- These records are prospective: events made before the audit logging patch was installed are not automatically reconstructed or backfilled.
- The audit history is an application audit record; it does not independently prove that physical cash was received or that a refund was paid. Reconcile cash against the cash drawer/payment records.

### Admin navigation and date filters

- The administrator sidebar uses collapsible groups to organize related destinations, including Customers & Support and Sales & Audit. The current page's group is expanded/highlighted according to the installed sidebar layout.
- Supported date-bearing admin pages use the shared date-filter panel with **Today**, **This week**, **This month**, **All time**, and custom From/To dates.
- The Orders page uses the shared date-filter UI while retaining its order-specific filters such as status, payment, administrator/cashier staff, source, and page size. Legacy `period` query links remain supported by the Orders date-filter update.
- Product Sales and Sales & Expenses place their filter panels above the KPI cards so date and ledger filters are visible before the summary figures.
- Product, Category, Supplier, and walk-in Customer Directory lists do not receive invented date filters when their underlying schema does not provide a reliable creation timestamp.
- The customer registration form uses a consolidated validation-feedback area to avoid showing the same validation warning in multiple places.

### PDF and CSV report exports

- Product Sales, Order Correction Audit, and POS Price & Discount Audit provide print-optimized report views that can be saved as PDF through the browser's Print → Save as PDF workflow where the PDF action is available.
- The Order Correction Audit PDF uses a receipt-oriented before/after layout so original and corrected values can be compared without squeezing every field into a very wide table.
- The POS Price & Discount Audit PDF uses readable event records. Price overrides show catalogue/charged-price details where recorded; discount events show the discount amount/percentage, staff, order reference, and reason where recorded.
- CSV and PDF/print exports should use the active date, staff, category, product, source, event-type, and other page filters supported by that report.
- Report totals must represent the full filtered result set, not only the current page of a paginated table.
- Customer receipt PDFs are separate from admin report PDFs: receipts are generated for an individual customer-owned order; admin reports summarize filtered business records.

### Finance filters and expense totals

- Product Sales and Sales & Expenses pages place their filter panels above the KPI summary cards.
- The Sales & Expenses date range controls the financial summary period. Search, category, sort, and page-size controls refine the displayed expense ledger where supported.
- **Total Expenses** represents the sum of the expense categories included by the report for the selected period. In this project, the finance summary combines inventory-purchase outflows and operating expenses.
- The expense ledger's total row and the expense PDF/CSV report total should include all matching records across pagination, not just the current page.
- A report's Total Expenses is a cash-outflow summary. It must be interpreted separately from profit/COGS: inventory purchase cash outflow is recorded when stock is bought, while the inventory cost is recognized in COGS as the related stock is sold. Do not subtract the same purchase amount twice when calculating profit.

### Security hardening

The recent security patch adds server-side enforcement for three areas:

1. **Image uploads:** image input is validated as an image, a server-generated filename is used, and a safe extension is selected from the validated image content. The patch also includes Apache `.htaccess` rules to prevent script execution in the configured public image/upload directories.
2. **POS price/discount authorization:** cashier price overrides and discounts are rejected by the server by default. Authorized administrator overrides/discounts require a reason and are written to the POS audit history. The user interface is not the security boundary; authorization is checked in the request handler.
3. **Quick product creation:** catalogue product creation from the POS quick-product endpoint is administrator-only; a cashier cannot bypass the interface by directly submitting the endpoint.

The Apache `.htaccess` protection does not configure Nginx. Nginx deployments must add equivalent server-level rules preventing PHP/script execution in public upload directories. Existing uploaded files should be reviewed separately because adding upload validation does not retroactively sanitize old files.

The patch's default cashier discount allowance is 0%. The administrator/cashier limits are configurable in `config/pos.php`; use the environment variables documented below to set the business policy. Review the configured limits before enabling the POS for staff.

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

| Role              | Typical access                                                                                                                                 |
| ----------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| **Administrator** | Admin dashboard, orders/delivery, settings, staff management, inventory, purchasing, customer service, reviews, finance, exports, and backups. |
| **Cashier**       | POS and cashier order-processing workspace. No administrator-only management or backup access; quick catalogue-product creation and POS price overrides/discounts are blocked by default. |
| **Customer**      | Public storefront, own account, checkout, own order tracking/history, review submission, and own customer inbox.                               |
| **Guest**         | Public storefront browsing and contact form; customer account and checkout features require sign-in.                                           |

Role-protected routes use Laravel authentication and the project's role/administrator middleware. Grant the Administrator role only to trusted accounts. Backup routes are inside the administrator-protected route group.

## Technology stack

- **Backend:** PHP `^8.3`, Laravel Framework `^13.8` (Laravel 13.x).
- **Database:** MariaDB 10.4+ is the primary target for the supplied SQL scripts. Validate MySQL alternatives before applying the SQL scripts.
- **Frontend:** Laravel Blade, HTML, CSS, JavaScript, Bootstrap-based components, Bootstrap Icons, and SweetAlert2 notifications where included by the layout.
- **Asset tooling:** Node.js, npm, Vite, and Tailwind CSS tooling.
- **Database backup tooling:** PHP ZIP extension (`ZipArchive`) and MySQL/MariaDB command-line clients (`mysqldump` / `mysql`, or compatible MariaDB client binaries).
- **PDF generation:** `barryvdh/laravel-dompdf` for customer receipt PDFs; browser print-to-PDF for the print-optimized admin reports.
- **Payments:** ABA PayWay KHQR sandbox integration (optional and credential-dependent).
- **Other integrations:** Google OAuth sign-in and Telegram Bot notifications/fulfillment actions (both optional).

## Requirements

Install or enable:

- PHP 8.3 or newer with Laravel-required extensions, including PDO MySQL, OpenSSL, Mbstring, cURL, DOM, Fileinfo, JSON, XML, and Tokenizer.
- PHP **ZIP** extension for backup creation and restore (`ZipArchive`).
- Composer 2.
- `barryvdh/laravel-dompdf` as declared in the project's Composer dependencies for downloadable customer receipts.
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

The receipt-download feature requires `barryvdh/laravel-dompdf`. It should be listed in `composer.json` and `composer.lock`. If you are updating an older checkout that does not yet include it, install the project-approved version:

```bash
composer require "barryvdh/laravel-dompdf:^3.1.2"
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

    | Order | Script                                           | Purpose                                                                                                    |
    | ----: | ------------------------------------------------ | ---------------------------------------------------------------------------------------------------------- |
    |     1 | `database/role_access_upgrade.sql`               | Compatibility update for role access/indexes on older imported databases.                                  |
    |     2 | `database/01_upgrade_schema.sql`                 | Order source/status fields, order notes, sale-time item cost, payment support, and related schema updates. |
    |     3 | `database/03_stock_nonstock_finance_upgrade.sql` | Stock/Non-stock normalization and stock deduction trigger update.                                          |
    |     4 | `database/04_telegram_order_fulfillment.sql`     | Order status history and Telegram order-notification support.                                              |
    |     5 | `database/05_google_sign_in.sql`                 | Optional Google account-linking field/index.                                                               |
    |     6 | `database/06_customer_reviews_moderation.sql`    | Customer review approval flag.                                                                             |
    |     7 | `database/07_pos_delivery_workflow.sql`          | POS delivery/order-channel support and Cash on Delivery payment method.                                    |
    |     8 | `database/08_storefront_customer_service.sql`    | Store contact/enquiry table.                                                                               |
    |     9 | `database/09_cod_cash_collection.sql`            | COD amount, collection timestamp, collector, and collection-note fields.                                   |
    |    10 | `database/10_store_inbox_replies.sql`            | Customer/admin conversation replies and inbox support.                                                     |
    |    11 | `database/11_admin_delivery_fee_settings.sql`    | Editable delivery-fee column in Store Settings.                                                            |
    |    12 | `database/12_customer_telegram_otp.sql`          | Temporary customer registration/Telegram OTP storage for the verification workflow.                       |
    |    13 | `database/13_customer_telegram_phone_verification.sql` | Telegram contact/phone ownership verification fields for pending customer registrations.              |
    |    14 | `database/14_customer_account_phone_release.sql` | Supports releasing a phone/Telegram login identity when an account is archived while preserving history. |

4. `database/02_audit_stock_balance.sql` is a **read-only audit query**, not a schema upgrade. Run it separately when you want to compare product quantities with recorded stock movements. It does not repair or change stock.
5. Apply scripts 12–14 only when those files are present in the checkout and have not already been applied. They belong to the Telegram OTP/customer-account upgrade series; back up first and inspect the SQL before running it on an existing database.
6. If the item-specific POS discount migration is present in the checkout and has not already been applied, run it once after backing up the database:

   ```powershell
   php artisan migrate --path=database/migrations/2026_11_22_000001_add_item_specific_pos_discounts.php
   ```

   This migration is additive. Do not run it again manually after Laravel has recorded it as applied.
7. Run `php artisan optimize:clear` after applying schema updates.

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
APP_NAME="SK Coffee Aura"
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
TELEGRAM_BOT_USERNAME=
TELEGRAM_GROUP_CHAT_ID=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_ADMIN_IDS=
TELEGRAM_PHONE_COUNTRY_CODE=855
```

`TELEGRAM_ADMIN_IDS` is a comma-separated list of numeric Telegram user IDs authorized to perform Telegram fulfillment actions. `TELEGRAM_BOT_USERNAME` is used to build customer registration deep links; `TELEGRAM_PHONE_COUNTRY_CODE` defaults to `855` for Cambodian local-number normalization. Keep the bot token, private group chat ID, webhook secret, and administrator IDs out of source control. The webhook `allowed_updates` must include both `message` and `callback_query` for customer OTP and group fulfillment buttons.

### Delivery fee fallback

```dotenv
WEB_DELIVERY_FEE=2.00
```

The administrator-saved Store Settings value takes precedence after the delivery-fee column exists and a value has been saved. The environment value is used when the database value is `NULL` / not yet configured. The setting affects new delivery orders only.

### POS price and discount limits

The security-hardening patch reads the POS limits from `config/pos.php`. The cashier discount limit defaults to `0` (no cashier discount). Set explicit limits in `.env` to match your store policy; the values below are an example policy, not a requirement:

```dotenv
# Example policy: administrator discount up to 30%; cashier discount disabled.
POS_ADMIN_MAX_DISCOUNT_PERCENT=30
POS_CASHIER_MAX_DISCOUNT_PERCENT=0
```

After changing these values, run `php artisan optimize:clear`. Administrator price overrides and discounts must include a reason and are recorded in POS audit history when the installed audit handler processes the sale. The POS discount selector offers quick percentage choices and a custom value; its up/down controls change the percentage in 5-point steps. The server applies the configured maximum regardless of the value submitted by the browser.

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

| Included item                         | Source path / archive content                                            |
| ------------------------------------- | ------------------------------------------------------------------------ |
| Configured database                   | `database.sql` logical dump created with `mysqldump` / compatible client |
| Product/shop images                   | `public/images`                                                          |
| Other public uploads                  | `public/uploads`                                                         |
| Public Laravel storage files          | `storage/app/public`                                                     |
| Backup metadata and integrity records | `manifest.json`                                                          |

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

| Event             | Default time | Default behavior                                                                            |
| ----------------- | -----------: | ------------------------------------------------------------------------------------------- |
| Daily backup      |      `02:00` | Creates a full backup with type `scheduled`.                                                |
| Retention cleanup |      `03:15` | Deletes generated backup ZIP files older than `BACKUP_RETENTION_DAYS` (30 days by default). |

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

For customer registration, the bot also handles private `/start` deep links and phone-contact sharing. The contact must belong to the same Telegram sender, and the normalized Telegram phone must match the number supplied on the website before an OTP is sent. A new customer account is not activated until the OTP is verified. Keep the bot username configured and ensure the webhook accepts `message` as well as `callback_query`.

Changing `APP_KEY` invalidates signatures on already-sent Telegram fulfillment buttons. If the key must be rotated, refresh/regenerate existing Telegram order messages using the application workflow so their buttons are signed with the current key. Do not rotate `APP_KEY` casually on a live system because it can also invalidate encrypted application data and sessions.

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

| Admin area         | Count represents                                                          |
| ------------------ | ------------------------------------------------------------------------- |
| Orders             | Orders awaiting fulfillment (pending status).                             |
| Delivery           | Deliveries not yet delivered or cancelled.                                |
| Inventory/stock    | Active stock-tracked products with quantity at or below zero.             |
| Reviews            | Reviews awaiting administrator approval.                                  |
| Customer enquiries | Unread enquiries and unread customer replies on unresolved conversations. |

## Inventory and finance rules

- **Stock** products track available quantity. Sales reduce stock; purchase receipts/valid adjustments increase stock; insufficient stock should prevent overselling.
- **Non-stock** products do not use an inventory balance and are excluded from stock deduction.
- Order items store a sale-time `unit_cost` snapshot for more reliable COGS reporting on new sales.
- Sales revenue is derived from paid POS/website orders.
- Manual finance entries are intended for non-sale expenses (for example utilities, rent, maintenance, or operating supplies). Do not enter a paid order as manual income as well.
- Inventory purchases are cash outflows; inventory cost is recognized as COGS when items are sold. Do not subtract the same purchase cost again from profit.
- The Sales & Expenses **Total Expenses** summary combines inventory-purchase outflows and operating-expense entries for the selected reporting period. Treat this cash-outflow total separately from COGS/profit calculations to avoid counting the same inventory cost twice.
- Historical cost backfills may be estimates where sale-time unit cost was not recorded originally.

## Useful routes

Exact route definitions are in `routes/web.php`. Main paths include:

| Area                    | Path                                   | Access                                   |
| ----------------------- | -------------------------------------- | ---------------------------------------- |
| Staff login             | `/`                                    | Public login page                        |
| Language switch         | `POST /language`                       | Session locale (`en` or `km`)            |
| Admin dashboard         | `/admin`                               | Administrator                            |
| POS                     | `/pos`                                 | Administrator / Cashier                  |
| Cashier workspace       | `/cashier`                             | Cashier                                  |
| Storefront shop         | `/store/shop`                          | Public                                   |
| Storefront about        | `/store/about`                         | Public                                   |
| Storefront shipping     | `/store/shipping`                      | Public                                   |
| Storefront contact      | `/store/contact`                       | Public                                   |
| Storefront product      | `/store/product/{productId}`           | Public                                   |
| Storefront cart         | `/store/cart`                          | Public cart                              |
| Customer login          | `/store/login`                         | Public                                   |
| Customer registration   | `/store/register`                      | Public                                   |
| Registration processing | `POST /store/register/process`          | Public, throttled                         |
| Telegram OTP page        | `/store/register/verify-telegram`       | Temporary registration session           |
| Telegram OTP submission | `POST /store/register/verify-telegram`  | Temporary registration session, throttled |
| Telegram link refresh    | `POST /store/register/telegram/new-link`| Temporary registration session, throttled |
| Customer account        | `/store/account`                       | Customer                                 |
| Customer inbox          | `/store/inbox`                         | Customer                                 |
| Customer checkout       | `/store/checkout`                      | Customer                                 |
| Customer order tracking | `/store/orders/{orderId}`              | Owner customer account                   |
| Customer receipt download | Named route `web.orders.receipt` (check its URI with the command below) | Owner customer account |
| PayWay callback         | `POST /store/payments/payway/callback` | Provider callback                        |
| Telegram webhook        | `POST /telegram/webhook`               | Telegram secret + configured user checks |
| Admin orders            | `/admin/orders`                        | Administrator                            |
| Product Sales Analytics | `/admin/product-sales`                 | Administrator                            |
| Order Correction Audit  | `/admin/order-corrections`              | Administrator                            |
| POS Price & Discount Audit | `/admin/pos-price-discount-audit`      | Administrator                            |
| Admin delivery register | `/admin/delivery`                      | Administrator                            |
| Customer enquiries      | `/admin/customer-service`              | Administrator                            |
| Admin reviews           | `/admin/reviews`                       | Administrator                            |
| Inventory               | `/admin/inventory`                     | Administrator                            |
| Store settings          | `/admin/meanleap/setting`              | Administrator                            |
| Finance                 | `/admin/expense-income`                | Administrator                            |
| Backup & restore        | `/admin/backups`                       | Administrator                            |

Use the application navigation where possible; some legacy route names/paths are retained for compatibility. Legacy GET requests under `/web` redirect to the canonical `/store` storefront path.

To inspect the exact registered receipt-download URI and middleware in your checkout, run `php artisan route:list --name=web.orders.receipt`.

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
composer audit
php artisan optimize:clear
php artisan route:list
php artisan schedule:list
php artisan test
```

### Recommended manual test checklist

- [ ] Administrator and cashier login work; cashier cannot access administrator-only routes.
- [ ] Customer registration/login and customer-owned order access work.
- [ ] Telegram registration requires sharing the same Telegram account phone number as the website registration and a valid, unexpired OTP before account activation.
- [ ] Expired/used Telegram links and reused OTPs are rejected; a wrong contact does not receive an OTP.
- [ ] Archiving a customer account releases the intended phone/Telegram identity while preserving customer/order history and reserving the email as documented.
- [ ] Product browsing, cart updates, and checkout work.
- [ ] Website and POS orders save the correct items, totals, source, and payment state.
- [ ] A customer can download a PDF receipt for their own order from order tracking/order history, and cannot access another customer's receipt by changing the order ID.
- [ ] POS item discounts affect only the selected cart line; undiscounted products retain their original line totals.
- [ ] POS discount selector presets/custom values work, and spinner arrows change the percentage by exactly 5 points per click.
- [ ] Discount limits and required reasons are enforced server-side for the configured staff role.
- [ ] Admin Orders shows the order discount as a currency amount, places the date after Order ID, and filters POS orders by administrator/cashier where supported.
- [ ] Order reports place Date after Queue No and show matching-result totals for Subtotal, Discount, and Final Total.
- [ ] Product Sales, Order Correction Audit, and POS Price & Discount Audit PDF/print exports are readable and respect their active filters.
- [ ] Receipt Correction PDF compares the original and corrected receipt clearly; POS Price & Discount Audit PDF displays price and discount events as readable records.
- [ ] Product Sales and Sales & Expenses filter panels appear above KPI cards.
- [ ] Sales & Expenses Total Expenses matches the full filtered sum of inventory-purchase and operating-expense records, including records on other pagination pages.
- [ ] Expense PDF/CSV totals match the full filtered expense ledger.
- [ ] KHQR is marked paid only after server-side PayWay verification.
- [ ] POS Delivery + COD appears in Admin → Orders and remains unpaid until final cash handoff.
- [ ] Eligible completed POS cash receipts open the POS correction workflow; ineligible orders display the correct reason.
- [ ] Receipt corrections preserve original/corrected snapshots, stock reconciliation, staff attribution, reason, cash adjustment, and Back to Orders filter context.
- [ ] Product Sales Analytics totals match the underlying paid, non-cancelled order items for the selected date/source/staff filters.
- [ ] Order Correction Audit shows correction records and POS Price & Discount Audit shows only events recorded after its logging patch is installed.
- [ ] Cashiers cannot create quick products, override prices, or apply discounts above their configured limit through a direct HTTP request.
- [ ] Administrator price overrides/discounts require a reason and create the expected `order_status_history` audit event.
- [ ] Disguised/non-image uploads are rejected and valid image uploads receive server-generated safe filenames.
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
- [ ] Collapsible admin navigation expands the group containing the current page and all grouped links remain permission-protected.
- [ ] Shared date filters (Today, This week, This month, All time, and custom range) behave consistently across supported reporting tabs and exports.
- [ ] Orders staff filter separates the selected administrator/cashier from website orders and preserves the selection in exports where supported.
- [ ] Backup creation produces a private ZIP with database dump, manifest, and expected uploaded files.
- [ ] A stored backup can be restored in a disposable environment and a `pre_restore` backup is created first.
- [ ] Daily backup and retention events appear in `php artisan schedule:list` and run when the OS scheduler is enabled.
- [ ] Telegram notification and webhook work with the current bot token and group/supergroup chat ID.

Static syntax checks are not a substitute for browser/database/payment testing. Test with a backed-up or disposable database and sandbox payment credentials.

## Security controls and production hardening

### Controls added by the recent security patch

- Validate uploaded image content and allowed image formats; use server-generated filenames rather than trusting client-supplied names/extensions.
- Prevent script execution in public upload directories using Apache `.htaccess` rules where Apache is used. Add equivalent Nginx rules for Nginx deployments.
- Enforce POS price-override and discount permissions on the server, not only by hiding controls in the UI.
- Keep cashier discounts disabled by default (`POS_CASHIER_MAX_DISCOUNT_PERCENT=0`) unless a deliberate policy is configured.
- Restrict POS quick-product creation to administrators.
- Require a reason for authorized administrator price/discount adjustments and record the event in `order_status_history` using `pos_price_override` or `pos_discount_applied`.
- Keep customer Telegram OTP, phone-contact ownership verification, one-use registration links, and attempt limits enabled.

### Additional checks still required before production

The source review identified other areas that should be reviewed separately; the recent three-part hardening patch does not automatically resolve them:

- Apply spreadsheet-safe escaping to all CSV exports, especially free-text customer names, correction notes, and reasons, to mitigate CSV formula injection.
- Add suitable throttling and abuse controls to payment polling/retry endpoints while preserving provider callback verification.
- Configure trusted proxies to the actual reverse-proxy/tunnel addresses; do not blindly trust forwarded client IP headers from arbitrary sources.
- Run `composer audit` against the current `composer.lock`, update vulnerable dependencies within compatible version constraints, and run regression tests after updates.
- Configure HTTPS, secure session cookies, `APP_DEBUG=false`, least-privilege database credentials, private backups, and server-level upload execution restrictions for the actual hosting stack.

Do not treat the checklist as proof of a completed penetration test. Test authorization and payment/business logic in a disposable environment with dedicated test accounts and sandbox payment credentials. Do not run load tests or destructive tests against a public demo/production instance without a separately controlled test plan.

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
git commit -m "Update SK Coffee Aura README and recent POS/report features"
git push origin HEAD
```

If the remote rejects the push, inspect and reconcile the incoming commits. Do not use `git push --force` to resolve a normal push rejection. If a secret was ever committed, rotate it; deleting it in a later commit does not remove it from Git history.

## Troubleshooting

### Route not found

```bash
php artisan route:clear
php artisan optimize:clear
php artisan route:list
```

Confirm the route exists in `routes/web.php`, the route name is unique, the controller import is correct, and the action method actually exists (for example, the Telegram registration form POST route invokes `CustomerTelegramRegistrationController::store`, while the verification GET route invokes `show`).

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

### POS price/discount audit appears empty

The **POS Price & Discount Audit** page reads `order_status_history` events `pos_price_override` and `pos_discount_applied`. Only adjustments recorded after the POS audit-logging code was installed are visible; older sales are not automatically backfilled. A price event is expected only when the sale price differs from the product catalogue price, and a discount event is expected only for a non-zero discount. Check the selected date/staff/event filters and inspect `order_status_history` with a read-only query if the page remains empty.

### POS image upload is blocked or a file cannot be displayed

Confirm the upload is a valid supported image (JPEG, PNG, GIF, or WEBP), check PHP upload limits and writable image directories, and inspect `storage/logs/laravel.log`. The included `.htaccess` execution restriction applies to Apache; configure equivalent Nginx rules if the application is served by Nginx. Do not remove upload validation to make an unsupported file pass.

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
- The browser-side Khmer translator translates approved exact UI strings. It is not a general-purpose translator for all arbitrary/dynamic content; newly added UI phrases must be merged into both `lang/km.json` and `public/assets/lang/km-ui.json` where appropriate.
- Product Sales Analytics uses the currently saved order items and item-price subtotal before item-specific discounts; it can differ from net amount collected. Historical item edits are not necessarily reconstructed as a line-by-line time series.
- Order Correction Audit and POS Price & Discount Audit can only display events that the relevant audit handlers actually recorded. Legacy activity that was never logged cannot be recreated reliably.
- Item-specific discount entry is the current POS workflow. Older orders may contain legacy discount data in the earlier order-level format; preserve those historical totals and verify them when comparing old and new reports.
- POS receipt correction is intentionally restricted to eligible order/payment/stock states. Website, external-payment, delivery, ledger-linked, unpaid, or unverifiable orders may require their own workflow rather than direct POS receipt correction.
- The POS Price & Discount Audit is an application audit trail, not independent proof of physical cash collection or refund payment. Reconcile with cash and provider records.
- The recent upload and POS authorization hardening patch does not by itself complete the outstanding CSV-export, payment-throttling, trusted-proxy, or dependency-audit work listed above.
- Backup archives protect the configured database and three managed upload locations, not the full server/application deployment. Keep separate secure copies of source, environment settings, and infrastructure configuration.
- A failed database import may partially change the target database; use the `pre_restore` safety archive to recover.
- Telegram and ABA PayWay require valid external configuration and reachable HTTPS endpoints for callbacks/webhooks.
- PayWay is documented for sandbox testing unless production credentials, callback URL, and provider approval are separately configured.
- CSV exports are spreadsheet-compatible CSV rather than native `.xlsx`. Admin report PDFs use the browser's print-to-PDF workflow where implemented; customer order receipts are generated as downloadable PDF files through the configured PDF renderer.
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

**SK Coffee Aura** — manage the shop, serve customers, secure operations, and keep every order accountable.
