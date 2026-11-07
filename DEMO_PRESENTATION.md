# Graduation Demo Walkthrough

## 1. Introduction

“Coffee Corner is a web-based point-of-sale and inventory management system for a small coffee shop. It centralizes catalogue management, order records, stock movement, purchasing, finance, and customer/supplier information.”

## 2. Suggested live flow

1. **Sign in** — use the demo administrator account shown on the login screen.
2. **Overview dashboard** — explain that revenue and order totals come from saved database records. Point out the stock chart, low-stock alert list, and recent-order table.
3. **Products and categories** — show how the catalogue is organized and where product information is maintained.
4. **Point of Sale** — search for an item, filter by category, add it to the ticket, change quantities, and demonstrate the discount/tax calculation. Explain that this screen is currently a checkout preview and does not persist a payment.
5. **Orders and order items** — show the recorded order history and its line items.
6. **Inventory and purchases** — explain how incoming stock and sale-related stock logs support stock tracking.
7. **Finance and supporting modules** — briefly show income/expense records, suppliers, customers, delivery, offers, reviews, and store settings.

## 3. Architecture points to explain

- Laravel routes map browser requests to controllers.
- Controllers validate input and coordinate application logic.
- Eloquent models map to the existing MariaDB tables and relationships.
- Blade templates render the management interface.
- The SQL schema uses foreign keys and database triggers for selected stock/order rules.
- Administrator routes require an authenticated account with the `admin` role.

## 4. Honest scope statement

The project is a graduation demonstration rather than a production deployment. The current POS cart is a front-end preview; server-side payment processing, persisted POS checkout, comprehensive automated tests, and a full migration set for every legacy table remain future work.
