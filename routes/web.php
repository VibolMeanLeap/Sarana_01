<?php

use App\Http\Controllers\AccountingCategoryController;
use App\Http\Controllers\AdminExportController;
use App\Http\Controllers\AdminCustomerServiceController;
use App\Http\Controllers\AuthContoller;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerInboxController;
use App\Http\Controllers\OrderWorkflowController;
use App\Http\Controllers\PayWayPaymentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CostumerController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockLogController;
use App\Http\Controllers\StorefrontPageController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminOperationsController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\LanguageController;

// Language switching: English / Khmer
Route::post('/language', [LanguageController::class, 'update'])
    ->middleware('throttle:20,1')
    ->name('language.switch');
/* Staff sign-in (admin and cashier). */
Route::get('/', [AuthContoller::class, 'showLogin'])->name('showlogin');
Route::post('/login/process', [AuthContoller::class, 'processlogin'])
    ->middleware([
        \App\Http\Middleware\ProtectLoginAttempts::class . ':name,staff',
        'throttle:10,1',
    ])
    ->name('process_login');
Route::post('/logout', [AuthContoller::class, 'logout'])->name('process_logout');
Route::get('/auth/google/{audience}', [GoogleAuthController::class, 'redirect'])
    ->where('audience', 'staff|customer')->middleware('throttle:10,1')->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->middleware('throttle:20,1')->name('google.callback');

/* Public coffee shop pages. The catalogue can be browsed without an account. */
// Keep one public storefront landing page. The old /store URL is a permanent alias.
Route::redirect('/store', '/store/shop', 301)->name('web.home');
Route::get('/store/shop', [CustomerOrderController::class, 'shop'])->name('web.shop');
Route::get('/store/about', [StorefrontPageController::class, 'about'])->name('web.about');
Route::get('/store/shipping', [StorefrontPageController::class, 'shipping'])->name('web.shipping');
Route::get('/store/returns', [StorefrontPageController::class, 'returns'])->name('web.returns');
Route::get('/store/contact', [StorefrontPageController::class, 'contact'])->name('web.contact');
Route::post('/store/contact', [StorefrontPageController::class, 'submitContact'])->middleware('throttle:5,1')->name('web.contact.submit');
Route::get('/store/product/{productId}', [CustomerOrderController::class, 'product'])->whereNumber('productId')->name('web.product.show');
Route::get('/store/cart', [CustomerOrderController::class, 'cart'])->name('web.cart');
Route::post('/store/cart/items/{productId}', [CustomerOrderController::class, 'addToCart'])->whereNumber('productId')->name('web.cart.add');
Route::patch('/store/cart/items/{productId}', [CustomerOrderController::class, 'updateCart'])->whereNumber('productId')->name('web.cart.update');
Route::delete('/store/cart/items/{productId}', [CustomerOrderController::class, 'removeFromCart'])->whereNumber('productId')->name('web.cart.remove');
// ABA PayWay callback is public; status is re-verified server-to-server before an order is marked paid.
Route::post('/store/payments/payway/callback', [PayWayPaymentController::class, 'callback'])->middleware('throttle:60,1')->name('web.payments.payway.callback');
// Temporary POST-only compatibility for QR payloads created with the previous /web callback URL.
Route::post('/web/payments/payway/callback', [PayWayPaymentController::class, 'callback'])->middleware('throttle:60,1')->name('web.payments.payway.callback.legacy');
// Telegram uses a public HTTPS webhook protected by its secret-token header and an admin ID allowlist.
Route::post('/telegram/webhook', TelegramWebhookController::class)->middleware('throttle:120,1')->name('telegram.webhook');
Route::get('/store/register', [CostumerController::class, 'webRegister'])->name('webRegister');
Route::get('/store/login', [CostumerController::class, 'webLogin'])->name('webLogin');
Route::post('/store/register/process', [CostumerController::class, 'register'])->name('register');
Route::post('/store/login/process', [CostumerController::class, 'login'])
    ->middleware([
        \App\Http\Middleware\ProtectLoginAttempts::class . ':phone,customer',
        'throttle:10,1',
    ])
    ->name('login');
Route::post('/store/logout', [CostumerController::class, 'logout'])->name('logout');

/* Customer account pages. Customers cannot enter staff tools. */
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/store/account', [CostumerController::class, 'account'])->name('web.account');
    Route::get('/store/inbox', [CustomerInboxController::class, 'index'])->name('web.inbox');
    Route::get('/store/inbox/updates', [CustomerInboxController::class, 'updates'])->name('web.inbox.updates');
    Route::get('/store/inbox/{contactMessageId}', [CustomerInboxController::class, 'show'])->whereNumber('contactMessageId')->name('web.inbox.show');
    Route::get('/store/inbox/{contactMessageId}/updates', [CustomerInboxController::class, 'threadUpdates'])->whereNumber('contactMessageId')->name('web.inbox.thread-updates');
    Route::post('/store/inbox/{contactMessageId}/reply', [CustomerInboxController::class, 'reply'])->whereNumber('contactMessageId')->middleware('throttle:10,1')->name('web.inbox.reply');
    Route::get('/store/checkout', [CustomerOrderController::class, 'checkoutPage'])->name('web.checkout');
    Route::post('/store/checkout', [CustomerOrderController::class, 'placeOrder'])->name('web.checkout.place');
    Route::get('/store/orders/{orderId}', [CustomerOrderController::class, 'showOrder'])->whereNumber('orderId')->name('web.orders.show');
    Route::get('/store/orders/{orderId}/status', [CustomerOrderController::class, 'orderStatus'])->whereNumber('orderId')->name('web.orders.status');
    Route::post('/store/orders/{orderId}/review', [ReviewController::class, 'storeFromCustomer'])->whereNumber('orderId')->name('web.orders.review');
    Route::post('/store/orders/{orderId}/cancel', [CustomerOrderController::class, 'cancelOrder'])->whereNumber('orderId')->name('web.orders.cancel');
    Route::post('/store/orders/{orderId}/payway/retry', [CustomerOrderController::class, 'retryPayWay'])->whereNumber('orderId')->name('web.orders.payway.retry');
    Route::get('/store/payments/{payment}', [PayWayPaymentController::class, 'show'])->whereNumber('payment')->name('web.payments.show');
    Route::get('/store/payments/{payment}/status', [PayWayPaymentController::class, 'status'])->whereNumber('payment')->name('web.payments.status');
    Route::get('/store/payments/{payment}/success', [PayWayPaymentController::class, 'success'])->whereNumber('payment')->name('web.payments.success');
});


// Legacy storefront bookmarks redirect to the canonical /store/shop storefront.
Route::get('/web', fn () => redirect()->route('web.shop', [], 301));
Route::get('/web/{legacyPath}', function (string $legacyPath) {
    $target = '/store/'.ltrim($legacyPath, '/');
    $query = request()->getQueryString();
    return redirect($target.($query ? '?'.$query : ''), 301);
})->where('legacyPath', '.*');

/* POS is available to admins and cashiers only. */
Route::middleware(['auth', 'role:admin,cashier'])->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos.dashboard');
    Route::get('/pos/catalog/products', [PosController::class, 'refreshCatalog'])->name('pos.catalog.products');
    Route::post('/pos/products', [PosController::class, 'storeQuickProduct'])->name('pos.products.store');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/payments/{payment}/qr', [PayWayPaymentController::class, 'showForStaff'])->whereNumber('payment')->name('pos.payments.show');
    Route::get('/pos/payments/{payment}/status', [PayWayPaymentController::class, 'statusForStaff'])->whereNumber('payment')->name('pos.payments.status');
    Route::get('/pos/payments/{payment}/success', [PayWayPaymentController::class, 'successForStaff'])->whereNumber('payment')->name('pos.payments.success');
});

/* Cashier workspace: their own POS sales plus the website-order processing queue. */
Route::middleware(['auth', 'role:cashier'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/', [CashierController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [CashierController::class, 'orders'])->name('orders');
    Route::post('/orders/{orderId}/status', [OrderWorkflowController::class, 'updateByCashier'])->whereNumber('orderId')->name('orders.status');
});

/* Administrator-only management modules. */
Route::middleware(['auth', 'administrator'])->group(function () {
    Route::get('/admin', [AuthContoller::class, 'dash'])->name('admin.dashboard');

    // AJAX endpoint for live administrator notifications and action counts.
    Route::get('/admin/operations/updates', [
        AdminOperationsController::class,
        'updates'
    ])
        ->middleware('throttle:60,1')
        ->name('admin.operations.updates');

    Route::get('/admin/customer-service/updates', [AdminCustomerServiceController::class, 'updates'])
    ->middleware('throttle:60,1')->name('admin.customer-service.updates');
Route::get('/admin/customer-service/{contactMessage}/thread', [AdminCustomerServiceController::class, 'thread'])
    ->whereNumber('contactMessage')->middleware('throttle:120,1')->name('admin.customer-service.thread');
Route::get('/admin/customer-service/{contactMessage}/thread-updates', [AdminCustomerServiceController::class, 'threadUpdates'])
    ->whereNumber('contactMessage')->middleware('throttle:120,1')->name('admin.customer-service.thread-updates');

    // Customer-service enquiries submitted from the public storefront.
    Route::get('/admin/customer-service', [AdminCustomerServiceController::class, 'index'])->name('admin.customer-service.index');
    Route::get('/admin/customer-service/{contactMessage}', [AdminCustomerServiceController::class, 'show'])->whereNumber('contactMessage')->name('admin.customer-service.show');
    Route::patch('/admin/customer-service/{contactMessage}', [AdminCustomerServiceController::class, 'update'])->whereNumber('contactMessage')->name('admin.customer-service.update');
    Route::post('/admin/customer-service/{contactMessage}/reply', [AdminCustomerServiceController::class, 'reply'])->whereNumber('contactMessage')->middleware('throttle:20,1')->name('admin.customer-service.reply');

    // Consistent CSV (Excel-compatible) and print-ready PDF exports for admin list tabs.
    Route::get('/admin/exports/{type}/excel', [AdminExportController::class, 'excel'])
        ->where('type', 'products|categories|suppliers|customers|customer-accounts|staff-accounts|accounting-categories|offers|orders|order-items|purchases|inventory|transactions|deliveries|reviews')
        ->name('admin.exports.excel');
    Route::get('/admin/exports/{type}/pdf', [AdminExportController::class, 'pdf'])
        ->where('type', 'products|categories|suppliers|customers|customer-accounts|staff-accounts|accounting-categories|offers|orders|order-items|purchases|inventory|transactions|deliveries|reviews')
        ->name('admin.exports.pdf');

    // People and access. The /admin/user page is now customer-account management only.
    Route::get('/admin/user', [CostumerController::class, 'accounts'])->name('auth.list');
    Route::patch('/admin/user/{user_id}/status', [CostumerController::class, 'updateAccountStatus'])
        ->whereNumber('user_id')->name('customer.accounts.status');

    // Customer profiles and online account access live in the Customer Accounts page.
    Route::get('/admin/user/{user_id}/edit', [CostumerController::class, 'editCustomerAccount'])
        ->whereNumber('user_id')->name('customer.accounts.edit');
    Route::put('/admin/user/{user_id}', [CostumerController::class, 'updateCustomerAccount'])
        ->whereNumber('user_id')->name('customer.accounts.update');

    // Legacy staff-account URLs now redirect to Settings. Cashier/Admin accounts
    // can only be edited or deactivated from the dedicated Settings workspace.
    Route::get('/admin/user/create', fn () => redirect()->to(route('setting.list').'#staff-accounts'))->name('auth.create');
    Route::post('/admin/user/store', fn () => redirect()->to(route('setting.list').'#staff-accounts'))->name('auth.store');
    Route::delete('/admin/user/{user_id}', fn () => redirect()->to(route('setting.list').'#staff-accounts'))->whereNumber('user_id')->name('auth.delete');
    Route::get('/admin/user/edit/{user_id}', fn () => redirect()->to(route('setting.list').'#staff-accounts'))->whereNumber('user_id')->name('auth.edit');
    Route::post('/admin/user/update/{user_id}', fn () => redirect()->to(route('setting.list').'#staff-accounts'))->whereNumber('user_id')->name('auth.update');

    Route::get('/admin/customer', [CostumerController::class, 'index'])->name('customer.list');
    Route::get('/admin/supplier', [SupplierController::class, 'index'])->name('supplier.list');
    Route::get('/admin/supplier/create', [SupplierController::class, 'create'])->name('supplier.create');
    Route::post('/admin/supplier/store', [SupplierController::class, 'store'])->name('supplier.store');
    Route::delete('/admin/supplier/{supplier_id}', [SupplierController::class, 'delete'])->name('supplier.delete');
    Route::get('/admin/supplier/edit/{supplier_id}', [SupplierController::class, 'edit'])->name('supplier.edit');
    Route::post('/admin/supplier/update/{supplier_id}', [SupplierController::class, 'update'])->name('supplier.update');

    // Catalogue
    Route::get('/admin/product', [ProductController::class, 'index'])->name('admin.product');
    Route::get('/admin/category', [CategoryController::class, 'index'])->name('admin.category');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('/admin/offer', [OfferController::class, 'index'])->name('offer.list');
    Route::get('/admin/offer/add', [OfferController::class, 'create'])->name('offer.create');
    Route::post('/admin/offer/store', [OfferController::class, 'store'])->name('offer.store');
    Route::get('/admin/offer/{offer}/edit', [OfferController::class, 'edit'])->name('offer.edit');
    Route::put('/admin/offer/{offer}', [OfferController::class, 'update'])->name('offer.update');
    Route::delete('/admin/offer/{offer}', [OfferController::class, 'destroy'])->name('offer.destroy');

    // Orders and order items
    // Canonical administrator order tab. The dashboard links to admin.orders.index.
    Route::get('/admin/orders', [OrderController::class, 'index'])->name('admin.orders.index');

    // Backward compatibility for older sidebar links/bookmarks using the misspelled URL.
    Route::get('/admin/olders', function () {
        return redirect()->route('admin.orders.index');
    })->name('admin.olders');

    Route::post('/admin/orders/{orderId}/status', [OrderWorkflowController::class, 'updateByAdmin'])
        ->whereNumber('orderId')
        ->name('admin.orders.status');

    // Non-destructive order audit markers and printable receipts.
    Route::get('/admin/orders/{orderId}/receipt', [OrderController::class, 'receipt'])
        ->whereNumber('orderId')->name('admin.orders.receipt');
    Route::post('/admin/orders/{orderId}/flags', [OrderController::class, 'storeReviewFlag'])
        ->whereNumber('orderId')->name('admin.orders.flags.store');
    Route::patch('/admin/orders/{orderId}/flags/{flagId}/resolve', [OrderController::class, 'resolveReviewFlag'])
        ->whereNumber('orderId')->whereNumber('flagId')->name('admin.orders.flags.resolve');

    Route::get('/admin/older_items', function (Request $request) {
        $params = $request->filled('order_id') ? ['search' => $request->input('order_id')] : [];
        return redirect()->route('admin.orders.index', $params);
    })->name('admin.older_items');

    // Keep create/store/edit/update/delete under /admin/orders and name them admin.orders.*.
    // Do not use Route::resource('orders', ...) here: that conflicts with the cashier /orders URL.
    Route::resource('admin/orders', OrderController::class)
        ->except(['index', 'show'])
        ->names('admin.orders');

    Route::get('/order_items/{id}/edit', [OrderItemController::class, 'edit'])->whereNumber('id')->name('order_items.edit');
    Route::put('/order_items/{id}', [OrderItemController::class, 'update'])->whereNumber('id')->name('order_items.update');
    Route::delete('/order_items/{id}', [OrderItemController::class, 'destroy'])->whereNumber('id')->name('order_items.destroy');

    // Purchasing and stock
    Route::get('/admin/purchases', [PurchaseController::class, 'index'])->name('purchase.index');
    Route::get('/admin/purchases/create', [PurchaseController::class, 'create'])->name('purchase.create');
    Route::post('/admin/purchases', [PurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/admin/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchase.edit');
    Route::put('/admin/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchase.update');
    Route::delete('/admin/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchase.destroy');

    Route::get('/admin/inventory', [StockLogController::class, 'index'])->name('inventory.index');
    Route::get('/admin/inventory/create', [StockLogController::class, 'create'])->name('inventory.create');
    Route::post('/admin/inventory', [StockLogController::class, 'store'])->name('inventory.store');
    Route::get('/admin/inventory/{log}/edit', [StockLogController::class, 'edit'])->name('inventory.edit');
    Route::put('/admin/inventory/{log}', [StockLogController::class, 'update'])->name('inventory.update');
    Route::delete('/admin/inventory/{log}', [StockLogController::class, 'destroy'])->name('inventory.destroy');

    // Finance
    Route::get('/admin/expense-income', [CashTransactionController::class, 'index'])->name('expense_income.index');
    Route::get('/admin/expense-income/create', [CashTransactionController::class, 'create'])->name('expense_income.create');
    Route::post('/admin/expense-income', [CashTransactionController::class, 'store'])->name('expense_income.store');
    Route::get('/admin/expense-income/{expense_income}/edit', [CashTransactionController::class, 'edit'])->name('expense_income.edit');
    Route::put('/admin/expense-income/{expense_income}', [CashTransactionController::class, 'update'])->name('expense_income.update');
    Route::delete('/admin/expense-income/{expense_income}', [CashTransactionController::class, 'destroy'])->name('expense_income.destroy');
    Route::post('/admin/accounting-category', [AccountingCategoryController::class, 'store'])->name('accounting-category.store');
    Route::delete('/admin/accounting-category/{category}', [AccountingCategoryController::class, 'destroy'])->name('accounting-category.destroy');

    // Consolidated administrator reporting (all routes inherit auth + administrator middleware).
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/admin/reports/export/csv', [ReportController::class, 'csv'])->name('reports.csv');
    Route::get('/admin/reports/print', [ReportController::class, 'print'])->name('reports.print');

    // Store operations
    Route::get('/admin/delivery', [DeliveryController::class, 'index'])->name('delivery.index');
    Route::get('/admin/delivery/create', [DeliveryController::class, 'create'])->name('delivery.create');
    Route::post('/admin/delivery', [DeliveryController::class, 'store'])->name('delivery.store');
    Route::get('/admin/delivery/{delivery}/edit', [DeliveryController::class, 'edit'])->name('delivery.edit');
    Route::put('/admin/delivery/{delivery}', [DeliveryController::class, 'update'])->name('delivery.update');
    Route::delete('/admin/delivery/{delivery}', [DeliveryController::class, 'destroy'])->name('delivery.destroy');
    Route::get('/admin/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/admin/reviews/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/admin/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/admin/reviews/{review}/approval', [ReviewController::class, 'setApproval'])->name('reviews.approval');
    Route::get('/admin/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::get('/admin/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/admin/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/admin/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Full database and uploaded-file backups. These routes inherit auth + administrator middleware.
    Route::get('/admin/backups', [BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('/admin/backups', [BackupController::class, 'store'])->middleware('throttle:5,1')->name('admin.backups.store');
    Route::get('/admin/backups/{filename}/download', [BackupController::class, 'download'])
        ->where('filename', '[A-Za-z0-9._-]+')->name('admin.backups.download');
    Route::post('/admin/backups/{filename}/restore', [BackupController::class, 'restoreStored'])
        ->where('filename', '[A-Za-z0-9._-]+')->middleware('throttle:backup-restore')->name('admin.backups.restore');
    Route::post('/admin/backups/restore-upload', [BackupController::class, 'restoreUploaded'])
        ->middleware('throttle:backup-restore')->name('admin.backups.restore-upload');
    Route::delete('/admin/backups/{filename}', [BackupController::class, 'destroy'])
        ->where('filename', '[A-Za-z0-9._-]+')->name('admin.backups.destroy');

    // Shop configuration
    Route::get('/admin/meanleap/setting', [SettingController::class, 'index'])->name('setting.list');
    Route::get('/admin/meanleap/setting/create', [SettingController::class, 'create'])->name('setting.create');
    Route::post('/admin/meanleap/setting/store', [SettingController::class, 'store'])->name('setting.store');
    Route::get('/admin/meanleap/setting/edit/{id}', [SettingController::class, 'edit'])->name('setting.edit');
    Route::post('/admin/meanleap/setting/update/{id}', [SettingController::class, 'update'])->name('setting.update');
    Route::post('/admin/meanleap/setting/delete/{id}', [SettingController::class, 'delete'])->name('setting.delete');
    Route::post('/admin/meanleap/setting/staff', [SettingController::class, 'storeStaffAccount'])->name('setting.staff.store');
    Route::get('/admin/meanleap/setting/staff/{user_id}/edit', [SettingController::class, 'editStaffAccount'])
        ->whereNumber('user_id')->name('setting.staff.edit');
    Route::put('/admin/meanleap/setting/staff/{user_id}', [SettingController::class, 'updateStaffAccount'])
        ->whereNumber('user_id')->name('setting.staff.update');
    Route::delete('/admin/meanleap/setting/staff/{user_id}', [SettingController::class, 'deactivateStaffAccount'])
        ->whereNumber('user_id')->name('setting.staff.deactivate');
});
