<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Costumer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Settings;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Services\PayWayService;
use App\Services\TelegramOrderNotificationService;
use App\Support\StoreDeliveryFee;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class PosController extends Controller
{
    public function index()
    {
        $categories = Category::query()->orderBy('category_name')->get();
        $suppliers = Supplier::query()->orderBy('name')->get();
        $products = Product::query()
            ->leftJoinSub($this->salesByProductQuery(), 'product_sales', function ($join) {
                $join->on('product.product_id', '=', 'product_sales.product_id');
            })
            ->select('product.*')
            ->selectRaw('COALESCE(product_sales.units_sold, 0) AS units_sold')
            ->with('category')
            ->where('product.status', 'Active')
            ->orderBy('product.product_name')
            ->get();
        $customers = Costumer::query()->orderBy('name')->get();
        $orderTypes = OrderType::query()->orderBy('order_name')->get();
        $paymentMethods = PaymentMethod::query()
            ->whereIn(DB::raw('LOWER(TRIM(payment_name))'), ['cash', 'khqr', 'cash on delivery', 'cash-on-delivery', 'cod'])
            ->orderBy('payment_method_id')
            ->get();
        $deliveryFee = StoreDeliveryFee::current();

        return view('pos.dashboard', compact(
            'categories', 'suppliers', 'products', 'customers', 'orderTypes', 'paymentMethods', 'deliveryFee'
        ));
    }

    /**
     * Aggregate sold quantities from paid orders, excluding cancelled orders.
     * Shared by the initial POS render and the catalogue-only refresh endpoint.
     */
    private function salesByProductQuery()
    {
        return DB::table('order_items')
            ->join('orders', 'orders.order_id', '=', 'order_items.order_id')
            ->where('orders.is_paid', 1)
            ->where(function ($query) {
                $query->whereNull('orders.order_status')
                    ->orWhere('orders.order_status', '<>', 'cancelled');
            })
            ->select('order_items.product_id')
            ->selectRaw('SUM(COALESCE(order_items.quantity, 0)) AS units_sold')
            ->groupBy('order_items.product_id');
    }

    /**
     * Return the latest active catalogue without reloading the POS page.
     * The browser updates only the product grid and leaves the in-progress cart intact.
     */
    public function refreshCatalog()
    {
        $products = Product::query()
            ->leftJoinSub($this->salesByProductQuery(), 'product_sales', function ($join) {
                $join->on('product.product_id', '=', 'product_sales.product_id');
            })
            ->select('product.*')
            ->selectRaw('COALESCE(product_sales.units_sold, 0) AS units_sold')
            ->with('category')
            ->where('product.status', 'Active')
            ->orderBy('product.product_name')
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => (int) $product->product_id,
                    'name' => (string) $product->product_name,
                    'category_id' => (string) ($product->category_id ?? 'none'),
                    'category_name' => (string) ($product->category?->category_name ?? 'General'),
                    'price' => (float) $product->price,
                    'qty' => (int) $product->qty,
                    'stock_tracked' => $product->isStockTracked(),
                    'units_sold' => (int) ($product->units_sold ?? 0),
                    'image_url' => $product->image ? asset('images/'.$product->image) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'products' => $products->values(),
        ]);
    }

    /**
     * Create a product from POS using the same fields and rules as the Product tab.
     * Opening stock is written to the stock ledger. Product images use the existing
     * public/images storage convention. The catalogue price is never affected by
     * per-order price overrides.
     */
    public function storeQuickProduct(Request $request)
    {
        $request->merge([
            'product_name' => trim((string) $request->input('product_name', '')),
        ]);

        $validated = $request->validate([
            'product_name' => 'required|string|max:255|unique:product,product_name',
            'category_id' => 'required|integer|exists:category,category_id',
            'supplier_id' => 'nullable|integer|exists:suppliers,supplier_id',
            'price' => 'required|numeric|min:0|max:99999999.99',
            'cost' => 'required|numeric|min:0|max:99999999.99',
            'qty' => 'required_if:product_type,stock|nullable|integer|min:0|max:2147483647',
            'product_type' => 'required|in:stock,non-stock',
            'status' => 'required|in:Active,Inactive',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:4096',
        ]);

        if (($validated['product_type'] ?? 'stock') === 'non-stock' && (int) ($validated['qty'] ?? 0) > 0) {
            throw ValidationException::withMessages([
                'qty' => 'Non-stock products do not keep an inventory quantity. Set quantity to 0.',
            ]);
        }

        $isStockTracked = $validated['product_type'] === 'stock';
        $initialQty = $isStockTracked ? (int) ($validated['qty'] ?? 0) : 0;
        $imageName = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = now()->format('YmdHis').'_'.Str::random(8).'.'.$image->getClientOriginalExtension();
            $imageDirectory = public_path('images');
            if (! is_dir($imageDirectory)) {
                mkdir($imageDirectory, 0755, true);
            }
            $image->move($imageDirectory, $imageName);
        }

        try {
            $product = DB::transaction(function () use ($validated, $initialQty, $imageName) {
                $product = Product::create([
                    'product_name' => trim($validated['product_name']),
                    'category_id' => (int) $validated['category_id'],
                    'supplier_id' => ! empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
                    'price' => round((float) $validated['price'], 2),
                    'cost' => round((float) $validated['cost'], 2),
                    'qty' => $initialQty,
                    'product_type' => $validated['product_type'],
                    'description' => $validated['description'] ?? null,
                    'status' => $validated['status'],
                    'image' => $imageName,
                ]);

                if ($initialQty > 0 && $product->isStockTracked()) {
                    StockLog::create([
                        'product_id' => $product->product_id,
                        'user_id' => auth()->id(),
                        'type' => 'IN',
                        'quantity' => $initialQty,
                        'reason' => 'Opening stock',
                        'note' => 'Opening quantity recorded when product #'.$product->product_id.' was created from POS.',
                    ]);
                }

                return $product->load(['category', 'supplier']);
            }, 3);
        } catch (Throwable $exception) {
            if ($imageName && is_file(public_path('images/'.$imageName))) {
                @unlink(public_path('images/'.$imageName));
            }
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => $product->status === 'Active'
                ? 'Product created successfully and added to the active POS catalogue.'
                : 'Product created successfully as inactive. It will not appear in the POS catalogue until activated.',
            'product' => [
                'id' => (int) $product->product_id,
                'name' => (string) $product->product_name,
                'category_id' => (string) ($product->category_id ?? 'none'),
                'category_name' => (string) ($product->category?->category_name ?? 'General'),
                'price' => (float) $product->price,
                'qty' => (int) $product->qty,
                'stock_tracked' => $product->isStockTracked(),
                'units_sold' => 0,
                'status' => (string) $product->status,
                'image_url' => $product->image ? asset('images/'.$product->image) : null,
            ],
        ], 201);
    }

    /**
     * Create a POS order. Staff can record walk-in, social-page, Telegram,
     * phone and referral orders. Delivery orders require recipient details and
     * an address. Cash-on-delivery is collected when staff mark the delivery
     * completed; KHQR is verified server-to-server by ABA.
     */
    public function checkout(Request $request, PayWayService $payWay)
    {
        $validated = $request->validate([
            'cart' => 'required|string|max:20000',
            'customer_id' => 'nullable|integer|exists:customer,customer_id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:255',
            'customer_note' => 'nullable|string|max:500',
            'order_channel' => 'nullable|in:walk_in,facebook,instagram,telegram,phone,referral,other',
            'order_type_id' => 'required|integer|exists:order_type,order_type_id',
            'payment_method_id' => 'required|integer|exists:payment_method,payment_method_id',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $orderType = OrderType::query()->findOrFail($validated['order_type_id']);
        $isDelivery = Str::lower(trim((string) $orderType->order_name)) === 'delivery';

        if ($isDelivery) {
            $hasSelectedCustomer = ! empty($validated['customer_id'] ?? null);
            $request->validate([
                'delivery_address' => 'required|string|max:255',
                'customer_name' => $hasSelectedCustomer ? 'nullable|string|max:255' : 'required|string|max:255',
                'customer_phone' => $hasSelectedCustomer ? 'nullable|string|max:50' : 'required|string|max:50',
            ]);
        }

        $items = json_decode($validated['cart'], true);
        if (! is_array($items) || count($items) < 1 || count($items) > 50) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty or contains too many items. Please rebuild the order.',
            ]);
        }

        $items = Validator::make(['items' => $items], [
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|distinct|exists:product,product_id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.price_override' => 'sometimes|boolean',
            'items.*.unit_price' => 'nullable|numeric|min:0|max:99999999.99',
        ])->validate()['items'];

        foreach ($items as $item) {
            if (! empty($item['price_override']) && (! array_key_exists('unit_price', $item) || $item['unit_price'] === null || $item['unit_price'] === '')) {
                throw ValidationException::withMessages([
                    'cart' => 'An adjusted product price is missing. Please review the cart and try again.',
                ]);
            }
        }

        $paymentMethod = PaymentMethod::query()->findOrFail($validated['payment_method_id']);
        $methodName = Str::lower(trim((string) $paymentMethod->payment_name));
        $isCash = $methodName === 'cash';
        $isKhqr = $methodName === 'khqr';
        $isCashOnDelivery = in_array($methodName, ['cash on delivery', 'cash-on-delivery', 'cod'], true);

        if (! $isCash && ! $isKhqr && ! $isCashOnDelivery) {
            return back()->withInput()->with('error', 'This payment method is not connected yet. Please choose Cash, KHQR, or Cash on Delivery.');
        }

        if ($isCashOnDelivery && ! $isDelivery) {
            return back()->withInput()->with('error', 'Cash on Delivery is available only for home-delivery orders.');
        }

        if ($isKhqr && ! $payWay->isConfigured()) {
            return back()->withInput()->with('error', 'ABA PayWay is not configured. Add your sandbox merchant ID and API key to the local .env file first.');
        }

        $deliveryFee = $isDelivery ? StoreDeliveryFee::current() : 0.0;
        $orderChannel = $validated['order_channel'] ?? 'walk_in';

        try {
            $result = DB::transaction(function () use (
                $validated, $items, $methodName, $isCash, $isKhqr, $isCashOnDelivery,
                $isDelivery, $deliveryFee, $orderChannel, $orderType, $paymentMethod, $payWay
            ) {
                $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
                $products = Product::query()
                    ->whereIn('product_id', $productIds)
                    ->where('status', 'Active')
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                if ($products->count() !== count($productIds)) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more products are no longer available. Please refresh the POS screen.',
                    ]);
                }

                $subtotal = 0.0;
                $salePrices = [];
                foreach ($items as $item) {
                    $product = $products->get((int) $item['product_id']);
                    $quantity = (int) $item['quantity'];

                    if (! $product || ($product->isStockTracked() && (int) $product->qty < $quantity)) {
                        throw ValidationException::withMessages([
                            'cart' => 'Not enough stock for '.($product?->product_name ?? 'one of the selected products').'. Please refresh and try again.',
                        ]);
                    }

                    // An override is per-order only. The catalogue price is never changed.
                    $salePrice = ! empty($item['price_override'])
                        ? round((float) $item['unit_price'], 2)
                        : (float) $product->price;
                    $salePrices[$product->product_id] = $salePrice;
                    $subtotal += $salePrice * $quantity;
                }

                $customer = null;
                if (! empty($validated['customer_id'])) {
                    $customer = Costumer::query()
                        ->where('customer_id', $validated['customer_id'])
                        ->lockForUpdate()
                        ->firstOrFail();
                } elseif ($isDelivery) {
                    $customer = Costumer::create([
                        'user_id' => null,
                        'name' => trim((string) $validated['customer_name']),
                        'phone' => trim((string) $validated['customer_phone']),
                    ]);
                }

                if ($isDelivery && (! $customer || blank($customer->name) || blank($customer->phone))) {
                    throw ValidationException::withMessages([
                        'customer_name' => 'Delivery orders require a customer name and phone number.',
                    ]);
                }

                $discount = (float) ($validated['discount'] ?? 0);
                $totalAmount = round($subtotal + $deliveryFee, 2);
                $finalPrice = round($totalAmount * (1 - ($discount / 100)), 2);
                $isPaidAtCreation = $isCash;
                $initialStatus = $isDelivery ? 'pending' : ($isPaidAtCreation ? 'completed' : 'pending');

                $order = Order::create([
                    'customer_id' => $customer?->customer_id,
                    'cashier_id' => auth()->id(),
                    'order_type_id' => $orderType->order_type_id,
                    'total_amount' => $totalAmount,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'exchange_rate' => Settings::query()->value('exchange_rate') ?: 4100,
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'is_paid' => $isPaidAtCreation,
                    'payment_status' => $isPaidAtCreation ? 'Paid' : 'Unpaid',
                    'order_status' => $initialStatus,
                    'order_source' => 'pos',
                    'order_channel' => $orderChannel,
                    'customer_note' => $validated['customer_note'] ?? null,
                ]);

                foreach ($items as $item) {
                    $product = $products->get((int) $item['product_id']);
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $product->product_id,
                        'quantity' => (int) $item['quantity'],
                        'price' => $salePrices[$product->product_id] ?? (float) $product->price,
                        'unit_cost' => (float) $product->cost,
                    ]);
                }

                if ($isDelivery) {
                    Delivery::create([
                        'order_id' => $order->order_id,
                        'address' => trim((string) $validated['delivery_address']),
                        'cost' => $deliveryFee,
                        'status' => 'pending',
                    ]);
                }

                OrderStatusHistory::create([
                    'order_id' => $order->order_id,
                    'event_type' => 'order_created',
                    'from_status' => null,
                    'to_status' => $initialStatus,
                    'actor_type' => 'staff',
                    'actor_id' => auth()->id(),
                    'actor_label' => auth()->user()?->name ?? 'POS Staff',
                    'note' => 'POS order received via '.str_replace('_', ' ', $orderChannel).($isDelivery ? '; home delivery requested.' : '.'),
                    'created_at' => now(),
                ]);

                // Cash on Delivery does not become revenue until the delivery is
                // marked completed (and cash collected) by an authorised admin.
                if (! $isKhqr) {
                    return ['order' => $order->fresh(['customer', 'orderType', 'delivery', 'paymentMethod']), 'payment' => null];
                }

                $customerName = trim((string) ($customer?->name ?: auth()->user()?->name ?: 'POS Customer'));
                $nameParts = preg_split('/\s+/', $customerName, 2) ?: ['POS Customer'];
                $lineItems = $order->orderItems()->with('product')->get()->take(10)->map(fn ($line) => [
                    'name' => (string) ($line->product?->product_name ?? 'Store item'),
                    'quantity' => (int) $line->quantity,
                    'price' => (float) $line->price,
                ])->values()->all();

                $transactionId = 'CC'.now('UTC')->format('ymdHis').Str::upper(Str::random(4));
                $currency = strtoupper((string) config('services.payway.currency', 'USD'));
                $qr = $payWay->generateQr([
                    'tran_id' => $transactionId,
                    'amount' => $finalPrice,
                    'currency' => $currency,
                    'first_name' => function_exists('mb_substr') ? mb_substr((string) ($nameParts[0] ?? 'POS'), 0, 20) : substr((string) ($nameParts[0] ?? 'POS'), 0, 20),
                    'last_name' => function_exists('mb_substr') ? mb_substr((string) ($nameParts[1] ?? ''), 0, 20) : substr((string) ($nameParts[1] ?? ''), 0, 20),
                    'email' => (string) ($customer?->user?->email ?: auth()->user()?->email ?: ''),
                    'phone' => (string) ($customer?->phone ?: auth()->user()?->phone ?: ''),
                    'items' => $lineItems,
                    'callback_url' => config('services.payway.callback_url') ?: route('web.payments.payway.callback'),
                    'return_params' => ['order_id' => $order->order_id, 'source' => 'pos'],
                ]);

                $payment = Payment::create([
                    'order_id' => $order->order_id,
                    'provider' => 'aba_payway',
                    'tran_id' => $transactionId,
                    'amount' => $finalPrice,
                    'currency' => $currency,
                    'status' => 'pending',
                    'qr_string' => $qr['qrString'] ?? null,
                    'qr_image' => $qr['qrImage'] ?? null,
                    'abapay_deeplink' => $qr['abapay_deeplink'] ?? null,
                    'app_store' => $qr['app_store'] ?? null,
                    'play_store' => $qr['play_store'] ?? null,
                    'expires_at' => $qr['_expires_at'] ?? now()->addMinutes((int) config('services.payway.lifetime', 15)),
                    'raw_response' => $qr,
                ]);

                return ['order' => $order->fresh(['customer', 'orderType', 'delivery', 'paymentMethod']), 'payment' => $payment];
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            Log::error('POS checkout database error.', [
                'cashier_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'The order could not be saved. No changes should be committed; refresh the POS screen and try again.');
        } catch (Throwable $exception) {
            Log::error('POS checkout failed.', [
                'cashier_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'The order or KHQR request failed. Check the server log and your PayWay configuration before retrying.');
        }

        if ($result['payment']) {
            return redirect()->route('pos.payments.show', $result['payment']->id)
                ->with('success', 'Order #'.$result['order']->order_id.' was created. Scan KHQR; it remains unpaid until ABA verifies it.');
        }

        $telegramNotified = null;
        if ($isDelivery && ($isCash || $isCashOnDelivery)) {
            $telegramNotified = app(TelegramOrderNotificationService::class)->notifyPaidWebsiteOrder($result['order']);
        }

        if ($isCashOnDelivery) {
            $confirmation = 'Delivery order #'.$result['order']->order_id.' was saved. Collect payment when the delivery is completed.';
        } elseif ($isDelivery) {
            $confirmation = 'Paid delivery order #'.$result['order']->order_id.' was saved.';
        } else {
            $confirmation = 'Cash sale completed. Receipt / order #'.$result['order']->order_id.' has been saved.';
        }

        if ($isDelivery && $telegramNotified === true) {
            $confirmation .= ' Telegram notification sent to the administrator group.';
        } elseif ($isDelivery && $telegramNotified === false) {
            $confirmation .= ' However, the Telegram notification could not be sent. Check Telegram configuration and Laravel logs.';
        }

        return redirect()->route('pos.dashboard')->with('success', $confirmation);
    }
}
