# Coffee Corner Aura — COD Collection & Customer Inbox Patch

This is an **overlay patch** for the supplied Laravel project. It adds two connected workflows while preserving the existing POS, order fulfillment, Telegram notification, customer-service and accounting structure.

## What changes

### 1. Cash on Delivery (POS delivery orders)

- Choosing **Delivery → Cash on Delivery** in the POS displays a clear explanation that the order is saved as **Unpaid**.
- A COD order can progress through confirmation, preparation, ready and out-for-delivery while it remains unpaid.
- At the final handoff, a cashier or administrator must enter the amount received, tick an explicit confirmation, and may add a collection note.
- The amount must match the order's exact final price. A partial or different amount cannot mark the order paid.
- On confirmation, the order records `cod_collected_amount`, `cod_collected_at`, `cod_collected_by` and `cod_collection_note`; `is_paid` becomes true and `payment_status` becomes `Paid`.
- A separate `payment_collected` event is written to `order_status_history`, in addition to the delivery completion event.
- The authorised Telegram action labelled **Delivered & COD collected** is treated as an explicit handoff confirmation and records the exact amount due. Existing Telegram webhook authentication/administrator allowlisting remains in place.
- The customer order screen displays the COD amount due before collection and the amount/date/note after collection.

**Accounting choice:** the patch does not write another `cash_transactions` income row. Your existing reports recognise sales revenue from paid orders. Marking the COD order paid at the real cash handoff is the single source of truth and avoids double-counting revenue.

### 2. Store Account customer inbox

- A customer who is signed in and submits the website contact form gets a conversation linked to their account.
- The system immediately adds an acknowledgement reply with the enquiry reference number.
- Customers can open **My Account → Inbox**, read the shop's replies and send follow-up messages in the same thread.
- The storefront navigation shows a badge when there are unread store/system replies.
- Administrators continue to use **Admin → Customer enquiries**. Opening an enquiry shows the full conversation; the **Send customer reply** form posts a visible reply to the customer's account inbox.
- Replies from either side reopen a resolved enquiry as **In progress**.
- Customer routes only retrieve enquiries owned by the signed-in account. Guest enquiries still reach the existing admin inbox, but are not attached to a customer account and cannot appear in Store Account → Inbox.

## Install safely

1. **Back up the project and database.** Export your current `sarona01` database from phpMyAdmin before applying any changes.
2. Close the Laravel server and unzip this patch over the project root, allowing the listed files to merge/replace. Do not replace `.env`, `.git`, `vendor`, `node_modules`, `storage`, or your database dump.
3. Open phpMyAdmin and select the existing `sarona01` database.
4. Import these two SQL files in order:
   - `database/09_cod_cash_collection.sql`
   - `database/10_store_inbox_replies.sql`
5. In the project root, run:

   ```bash
   php artisan optimize:clear
   ```

6. Start the site and test the two workflows below. Do **not** run `migrate:fresh`, `db:wipe`, or any command that recreates the existing database.

The SQL changes are additive and idempotent for the supported MariaDB version: the order fields are added only if missing and the reply table is created only if missing. The original enquiry records are not deleted or rewritten.

## Test checklist

### COD test

1. Sign in to POS as a cashier or administrator.
2. Add an item, select **Delivery**, select **Cash on Delivery**, and enter the customer and delivery details.
3. Save the order. In the order queue it should display **Unpaid** and the amount due at handoff.
4. Advance it through **Confirmed → Preparing → Ready → Out for delivery**.
5. At **Out for delivery**, choose **Delivered**. Enter the amount due and tick the confirmation checkbox. Add an optional note such as `Received by driver`.
6. Save. Verify that the order is now **Paid**, the delivery is **Delivered**, and the payment collection details appear in the order activity/customer order screen.
7. Try entering an amount that differs from the due amount or leave the confirmation unticked. The server should reject the final transition.
8. The authorised Telegram **Delivered & COD collected** action is another supported final-handoff path.

### Customer inbox test

1. Sign in to the website with a customer account.
2. Open **Contact us**, submit a test enquiry and open **My Account → Inbox**.
3. Verify the automatic acknowledgement includes the enquiry reference.
4. Sign in as an administrator, open **Admin → Customer enquiries**, select the enquiry and send a reply.
5. Return to the customer account. The Inbox badge should show an unread reply. Open the conversation and verify the reply is visible.
6. Send a customer follow-up. It should appear in the admin conversation; the enquiry status should be **In progress**.

## Existing data and limitations

- Existing contact messages that have `user_id = NULL` remain in the administrator inbox. They are not automatically linked to a customer based on email or phone, because that could expose one person's enquiry to another account.
- New guest enquiries still save and notify the shop. The contact page explains that account inbox follow-up is available only when the customer signs in before sending.
- This patch stores the conversation in the database and displays unread badges when the customer next opens/refreshes the storefront. It does not promise email or push notifications; those require a separately configured delivery channel.
- COD payment is settled only when staff/authorised Telegram admin confirms the full amount was received. If the customer pays a different amount, do not force the order to Paid; resolve the discrepancy and record the approved adjustment separately.

## Files changed

Application files are supplied as an overlay, not a replacement project. See the ZIP's `PATCH_FILES.txt` for the exact file list.
