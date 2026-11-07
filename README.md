# Coffee Corner — POS & Inventory Management

A Laravel-based coffee shop management demonstration built around a MariaDB/MySQL database. The project brings together staff access, products and categories, orders, stock tracking, purchasing, customer and supplier records, finance, delivery, offers, reviews, and store settings.

## Demo account

After importing the supplied demo database, sign in at `/` with:

| Field | Value |
|---|---|
| Username | `demo.admin` |
| Password | `Demo@123` |

Change this password before using the project beyond a local demonstration.

## Requirements

- PHP 8.3 or newer with the extensions required by Laravel
- Composer
- MySQL 8+ or MariaDB 10.4+
- A web browser

## Local setup

1. Create a database named `sarona` using `utf8mb4`.
2. Import [`database/sarona_demo.sql`](database/sarona_demo.sql) using phpMyAdmin or the MySQL command line. This dump contains the project-specific tables, demo records, indexes, foreign keys, and stock/order triggers.
3. Install PHP dependencies:

   ```bash
   composer install
   ```

4. Create your local environment file:

   ```bash
   cp .env.example .env
   ```

   On Windows Command Prompt, use `copy .env.example .env` instead. Update the database username/password in `.env` if your local MySQL settings differ.

5. Generate the Laravel application key and clear cached configuration:

   ```bash
   php artisan key:generate
   php artisan optimize:clear
   ```

6. Start the development server:

   ```bash
   php artisan serve
   ```

7. Open `http://127.0.0.1:8000` and use the demo account above.

> **Database note:** Use the supplied SQL dump for this demo database. The repository currently contains only partial Laravel migrations; running `php artisan migrate:fresh` is not a replacement for importing the SQL dump and may destroy data.

## What was polished

The graduation-demo pass includes a responsive administrator workspace, data-driven dashboard, database-backed POS catalogue preview, corrected role-based access, safer stock/purchase/order-item updates, and a coherent sample database. See [`UPGRADE_NOTES.md`](UPGRADE_NOTES.md) for the change and verification summary.

## Main modules

- **Overview:** live totals based on saved orders, products, customers, and stock; recent orders; low-stock watch; product stock chart.
- **Point of Sale:** product catalogue loaded from the database, category filtering, search, cart quantity controls, discount and tax preview.
- **Catalogue:** product, category, and offer management.
- **Operations:** orders, order items, purchases, inventory/stock logs, income and expenses, delivery, and reviews. Inventory purchases are tracked separately from operating expenses in the profit calculation so COGS is not counted twice.
- **People:** staff accounts, customers, and suppliers.
- **Settings:** store profile and configuration.

## Important demo limitation

The POS screen is intentionally presented as an **interactive checkout preview**. It reads real products and stock from the database and calculates a cart total in the browser, but the existing project does not yet implement a complete server-side POS payment/checkout workflow. It therefore does not create an order or charge a payment method. Existing order records can be managed from the Orders module.

## Presentation tip

For a clean graduation demonstration, use the prepared demo database and the administrator account. A suggested walkthrough is in [`DEMO_PRESENTATION.md`](DEMO_PRESENTATION.md).
