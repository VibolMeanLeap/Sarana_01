<?php

use App\Http\Controllers\AccountingCategoryController;
use App\Http\Controllers\AuthContoller;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\CustomerOrderController;
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
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockLogController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/* Staff sign-in (admin and cashier). */
Route::get('/', [AuthContoller::class, 'showLogin'])->name('showlogin');
Route::post('/login/process', [AuthContoller::class, 'processlogin'])->name('process_login');
Route::post('/logout', [AuthContoller::class, 'logout'])->name('process_logout');

/* Public coffee shop pages. The catalogue can be browsed without an account. */
Route::get('/web', [CustomerOrderController::class, 'home'])->name('web.home');
Route::get('/web/shop', [CustomerOrderController::class, 'shop'])->name('web.shop');
Route::get('/web/cart', [CustomerOrderController::class, 'cart'])->name('web.cart');
Route::post('/web/cart/items/{productId}', [CustomerOrderController::class, 'addToCart'])->whereNumber('productId')->name('web.cart.add');
Route::patch('/web/cart/items/{productId}', [CustomerOrderController::class, 'updateCart'])->whereNumber('productId')->name('web.cart.update');
Route::delete('/web/cart/items/{productId}', [CustomerOrderController::class, 'removeFromCart'])->whereNumber('productId')->name('web.cart.remove');
// ABA PayWay callback is public; status is re-verified server-to-server before an order is marked paid.
Route::post('/web/payments/payway/callback', [PayWayPaymentController::class, 'callback'])->middleware('throttle:60,1')->name('web.payments.payway.callback');
Route::get('/web/register', [CostumerController::class, 'webRegister'])->name('webRegister');
Route::get('/web/login', [CostumerController::class, 'webLogin'])->name('webLogin');
Route::post('/web/register/process', [CostumerController::class, 'register'])->name('register');
Route::post('/web/login/process', [CostumerController::class, 'login'])->name('login');
Route::post('/web/logout', [CostumerController::class, 'logout'])->name('logout');

/* Customer account pages. Customers cannot enter staff tools. */
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/web/account', [CostumerController::class, 'account'])->name('web.account');
    Route::get('/web/checkout', [CustomerOrderController::class, 'checkoutPage'])->name('web.checkout');
    Route::post('/web/checkout', [CustomerOrderController::class, 'placeOrder'])->name('web.checkout.place');
    Route::get('/web/orders/{orderId}', [CustomerOrderController::class, 'showOrder'])->whereNumber('orderId')->name('web.orders.show');
    Route::get('/web/orders/{orderId}/status', [CustomerOrderController::class, 'orderStatus'])->whereNumber('orderId')->name('web.orders.status');
    Route::post('/web/orders/{orderId}/payway/retry', [CustomerOrderController::class, 'retryPayWay'])->whereNumber('orderId')->name('web.orders.payway.retry');
    Route::get('/web/payments/{payment}', [PayWayPaymentController::class, 'show'])->whereNumber('payment')->name('web.payments.show');
    Route::get('/web/payments/{payment}/status', [PayWayPaymentController::class, 'status'])->whereNumber('payment')->name('web.payments.status');
    Route::get('/web/payments/{payment}/success', [PayWayPaymentController::class, 'success'])->whereNumber('payment')->name('web.payments.success');
});

/* POS is available to admins and cashiers only. */
Route::middleware(['auth', 'role:admin,cashier'])->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos.dashboard');
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

    // People and access
    Route::get('/admin/user', [AuthContoller::class, 'index'])->name('auth.list');
    Route::get('/admin/user/create', [AuthContoller::class, 'create'])->name('auth.create');
    Route::post('/admin/user/store', [AuthContoller::class, 'store'])->name('auth.store');
    Route::delete('/admin/user/{user_id}', [AuthContoller::class, 'delete'])->name('auth.delete');
    Route::get('/admin/user/edit/{user_id}', [AuthContoller::class, 'edit'])->name('auth.edit');
    Route::post('/admin/user/update/{user_id}', [AuthContoller::class, 'update'])->name('auth.update');
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

    Route::get('/admin/older_items', [OrderItemController::class, 'index'])->name('admin.older_items');

    // Keep create/store/edit/update/delete under /admin/orders and name them admin.orders.*.
    // Do not use Route::resource('orders', ...) here: that conflicts with the cashier /orders URL.
    Route::resource('admin/orders', OrderController::class)
        ->except(['index', 'show'])
        ->names('admin.orders');

    Route::resource('order_items', OrderItemController::class)->except(['show']);

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
    Route::get('/admin/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::get('/admin/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/admin/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/admin/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Shop configuration
    Route::get('/admin/meanleap/setting', [SettingController::class, 'index'])->name('setting.list');
    Route::get('/admin/meanleap/setting/create', [SettingController::class, 'create'])->name('setting.create');
    Route::post('/admin/meanleap/setting/store', [SettingController::class, 'store'])->name('setting.store');
    Route::get('/admin/meanleap/setting/edit/{id}', [SettingController::class, 'edit'])->name('setting.edit');
    Route::post('/admin/meanleap/setting/update/{id}', [SettingController::class, 'update'])->name('setting.update');
    Route::post('/admin/meanleap/setting/delete/{id}', [SettingController::class, 'delete'])->name('setting.delete');
    Route::post('/admin/meanleap/setting/staff', [SettingController::class, 'storeStaffAccount'])->name('setting.staff.store');
});
