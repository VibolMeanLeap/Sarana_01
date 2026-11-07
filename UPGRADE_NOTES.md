# Graduation Demo Upgrade Notes

This package keeps the original Coffee Corner/Laravel project identity and its MariaDB schema, while tightening the demo experience and correcting several issues that could interrupt a live presentation.

## What changed

### Presentation and navigation
- Rebuilt the administrator shell with a consistent Coffee Corner visual identity, responsive navigation, clearer page hierarchy, and working sign-out behavior.
- Replaced placeholder dashboard counters with values queried from orders, products, customers, and inventory.
- Added a product-aware POS catalogue with search, category filters, stock visibility, cart quantities, discount calculations, and an explicit checkout-preview notice.
- Added a graduation walkthrough and a setup guide in `README.md` and `DEMO_PRESENTATION.md`.

### Application behavior
- Consolidated duplicate/conflicting route definitions and protected administrator modules with authenticated admin-role middleware.
- Corrected the demo login flow and added a seeded administrator account with a hashed password.
- Added validation and relationship checks around products, suppliers, finance categories, deliveries, reviews, and orders.
- Synchronized inventory quantities and stock history when purchases or order items are created, edited, or reversed.
- Recalculates a parent order total after an order item changes; the SQL update trigger refreshes its final price and payment status.
- Separates inventory-purchase cash outflows from operating expenses in net-profit calculations, avoiding the double-counting of purchase cost and cost of goods sold.
- Corrected legacy model/table/primary-key/timestamp mismatches and removed unresolved merge-conflict text from the admin layout.

### Demo database
- Added `database/sarona_demo.sql`, a fresh importable demo database with coherent Coffee Corner products, categories, orders, order items, customers, suppliers, finance entries, deliveries, reviews, and stock history.
- Aligned product quantities with the movement log balances and aligned each saved order total with its order items.
- Added unique username indexing and corrected the users/roles data so the demo administrator can authenticate.
- Included existing product image assets referenced by the demo seed data.

## Demo credentials

- Administrator username: `demo.admin`
- Administrator password: `Demo@123`
- A cashier staff record is included in the SQL seed for role/relationship demonstration; the current staff portal login is intentionally restricted to the administrator role.

Change sample passwords before any deployment beyond a local demonstration.

## Verification performed

- PHP syntax checked across the application, route, bootstrap, and database PHP files.
- Checked that Blade templates reference defined route names and that explicit controller actions exist.
- Checked for unresolved merge-conflict markers and accidental `.env` inclusion.
- Checked demo SQL insert column/value counts and foreign-key seed references.
- Reconciled sample order totals to their line items and sample product quantities to their stock movements.
- Confirmed the demo product images exist and verified both seeded password hashes.

## Known scope limits

- The POS is a front-end checkout preview. It does not persist a new order, record a payment, or connect to a payment provider.
- The legacy project contains partial and duplicate migrations. Import `database/sarona_demo.sql` into a new database named `sarona`; do not use `php artisan migrate:fresh` as a substitute.
- Full Laravel integration tests and a live database import were not run in the packaging environment because Composer dependencies/vendor files and a MySQL/MariaDB server were not available there. Complete the local setup in `README.md` and test the live workflow before presenting.
