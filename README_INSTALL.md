# Coffee Corner Aura — Delivery Fee Settings & Optional Fields Patch

This is an overlay patch for an existing Laravel installation. It does not replace the whole application, `.env`, Composer dependencies, uploaded images, or the live database.

## What this patch changes

### Editable delivery fee
- Adds a `delivery_fee` setting to the existing `settings` table.
- Adds a **Home delivery fee (USD)** field to Admin → Settings → Store profile.
- The saved setting is used by new delivery orders created from the POS and website checkout, and is shown on the public shipping-information page.
- Pickup, takeaway and dine-in orders do not receive a delivery fee.
- The current `.env` value (`WEB_DELIVERY_FEE`) remains a fallback until an administrator saves a value in Store Settings.
- Enter `0.00` to make delivery free. New fee changes do not alter existing order totals.
- The manual delivery-record form pre-fills the currently configured fee as a convenience; staff can still adjust it for that record.

### Optional form fields
The patch marks nullable inputs as genuinely optional for the shared browser-side validator. This includes the checkout and POS order notes, customer contact phone/order reference, customer review comment, COD collection notes, private customer-service note, expense notes, and stock-log notes. Existing required and conditional fields remain required.

## Installation (recommended for the existing database)

1. **Back up the project folder and export your database** in phpMyAdmin.
2. Extract this ZIP into the Laravel project root (the folder containing `artisan` and `app/`). Merge folders and allow the listed files to be replaced.
3. In phpMyAdmin, select the same database configured in the project's `.env` file, open the **Import** tab, and import:

   `database/11_admin_delivery_fee_settings.sql`

   This SQL is safe to run more than once. It adds the column only when it is missing. The uploaded database dump was named `sarona01`; use the actual database selected by your `.env` if it has a different name.

4. From the Laravel project root, run:

   ```bash
   php artisan optimize:clear
   ```

5. Sign in as an administrator and open **Settings → Store profile → Edit store profile**. Set **Home delivery fee (USD)** and save. Enter `0.00` for free delivery.

### Alternative for installations with a healthy Laravel migration history

Instead of importing the SQL script, run `php artisan migrate` to apply the included migration:

`database/migrations/2026_11_09_000001_add_delivery_fee_to_settings_table.php`

Use either the phpMyAdmin SQL import or the Laravel migration. Do not run `migrate:fresh`, `db:wipe`, or any command that recreates the database.

## Verification checklist

1. Set the delivery fee to `$2.50` in Store Settings and save.
2. Open the POS, choose a Delivery order, and confirm the fee displayed is `$2.50` and included in the order total.
3. Open website checkout, choose Delivery, and confirm the same fee and total. Choose Takeaway and verify no delivery fee is added.
4. Open the website Shipping Information page and verify it shows the saved fee.
5. Set the fee to `$0.00` and verify new delivery orders show free delivery.
6. Leave optional fields blank: website phone/order reference, website order note, review comment, POS order note, COD collection note, internal customer-service note, expense note, and stock-log note. These should not block submission.
7. Verify required fields still block invalid submissions, especially customer name/phone/address for new POS delivery orders, website delivery address when Delivery is selected, and cash amount/confirmation when completing an unpaid COD order.

## Notes

- The saved Store Settings value overrides `WEB_DELIVERY_FEE` after the database column exists and an administrator saves a value.
- A `NULL` database value keeps the `.env` fallback active until the first save.
- Existing orders retain their historical totals; the fee change applies to new orders only.
- PHP syntax checks were run on the changed controllers, model, helper and migration. Full Laravel/browser integration tests require the project's Composer dependencies and a running MariaDB database, so perform the verification checklist on a backed-up local copy before using it in production or a graduation demo.
