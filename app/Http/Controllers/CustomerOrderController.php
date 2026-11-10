<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Costumer;
use App\Models\Delivery;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Review;
use App\Models\Settings;
use App\Models\StockLog;
use App\Services\PayWayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerOrderController extends Controller
{
    private const NON_STOCK_SQL = "LOWER(TRIM(COALESCE(product_type, ''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')";

    public function home()
    {
        // Backward-compatible controller fallback: the single storefront is /store/shop.
        return redirect()->route('web.shop', [], 301);
    }

    public function shop(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:category,category_id'],
            'sort' => ['nullable', 'in:recommended,price_low,price_high,name,newest'],
        ]);

        $query = Product::query()
            ->with('category')
            ->where('status', 'Active')
            ->where(fn ($builder) => $builder->where('qty', '>', 0)->orWhereRaw(self::NON_STOCK_SQL));

        if (filled($filters['search'] ?? null)) {
            $term = trim($filters['search']);
            $query->where(fn ($builder) => $builder
                ->where('product_name', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%')
                ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('category_name', 'like', '%'.$term.'%'))
            );
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }

        match ($filters['sort'] ?? 'recommended') {
            'price_low' => $query->orderBy('price')->orderBy('product_name'),
            'price_high' => $query->orderByDesc('price')->orderBy('product_name'),
            'name' => $query->orderBy('product_name'),
            'newest' => $query->orderByDesc('product_id'),
            default => $query->orderBy('category_id')->orderBy('product_name'),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::query()
            ->whereIn('category_id', Product::query()
                ->select('category_id')
                ->where('status', 'Active')
                ->where(fn ($builder) => $builder->where('qty', '>', 0)->orWhereRaw(self::NON_STOCK_SQL))
            )
            ->orderBy('category_name')
            ->get();

        $offers = Offer::query()
            ->with('product')
            ->activeNow()
            ->whereHas('product', fn ($query) => $query
                ->where('status', 'Active')
                ->where(fn ($builder) => $builder->where('qty', '>', 0)->orWhereRaw(self::NON_STOCK_SQL)))
            ->orderByDesc('discount')
            ->get();

        $offersByProduct = $offers->keyBy('product_id');

        // The single storefront page also displays approved reviews, so no homepage route is needed.
        $approvedReviews = Review::query()
            ->with('customer')
            ->where('is_approved', true)
            ->whereHas('order', fn ($query) => $query->where('is_paid', true)->where('order_status', 'completed'))
            ->orderByDesc('review_id')
            ->limit(6)
            ->get();

        $reviewSummary = Review::query()
            ->where('is_approved', true)
            ->whereHas('order', fn ($query) => $query->where('is_paid', true)->where('order_status', 'completed'))
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as average')
            ->first();

        return view('website.shop', compact(
            'products', 'offers', 'offersByProduct', 'categories', 'filters',
            'approvedReviews', 'reviewSummary'
        ));
    }

    public function product(int $productId)
    {
        $product = Product::query()
            ->with('category')
            ->where('status', 'Active')
            ->where('product_id', $productId)
            ->firstOrFail();

        $offer = Offer::query()->activeNow()->where('product_id', $product->product_id)->first();
        $relatedProducts = Product::query()
            ->with('category')
            ->where('status', 'Active')
            ->where('product_id', '!=', $product->product_id)
            ->where(fn ($query) => $query->where('qty', '>', 0)->orWhereRaw(self::NON_STOCK_SQL))
            ->when($product->category_id, fn ($query) => $query->where('category_id', $product->category_id))
            ->inRandomOrder()
            ->limit(4)
            ->get();
        $relatedOffers = Offer::query()->activeNow()->whereIn('product_id', $relatedProducts->pluck('product_id'))->get()->keyBy('product_id');

        return view('website.product-detail', compact('product', 'offer', 'relatedProducts', 'relatedOffers'));
    }

    public function cart(Request $request)
    {
        [$items, $subtotal] = $this->cartItems($request);

        return view('website.cart', compact('items', 'subtotal'));
    }

    public function addToCart(Request $request, int $productId)
    {
        $validated = $request->validate([
            'quantity' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
        ]);
        $quantityToAdd = (int) ($validated['quantity'] ?? 1);

        $product = Product::query()
            ->where('product_id', $productId)
            ->where('status', 'Active')
            ->first();

        if (! $product) {
            return back()->with('cart_error', 'This product is no longer available. Please refresh the menu.');
        }

        if ($product->isStockTracked() && (int) $product->qty < 1) {
            return back()->with('cart_error', 'This product is currently out of stock.');
        }

        $cart = (array) $request->session()->get('web_cart', []);
        $currentQuantity = (int) ($cart[$productId] ?? 0);
        $newQuantity = $currentQuantity + $quantityToAdd;

        if ($product->isStockTracked() && $newQuantity > (int) $product->qty) {
            return back()->with('cart_error', 'Only '.(int) $product->qty.' unit(s) are currently available.');
        }

        $cart[$productId] = $newQuantity;
        $request->session()->put('web_cart', $cart);

        return redirect()->back()->with('cart_success', $product->product_name.' was added to your cart.');
    }

    public function updateCart(Request $request, int $productId)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1|max:1000']);
        $product = Product::query()
            ->where('product_id', $productId)
            ->where('status', 'Active')
            ->firstOrFail();

        if ($product->isStockTracked() && (int) $validated['quantity'] > (int) $product->qty) {
            return back()->with('cart_error', 'Requested quantity exceeds the '.(int) $product->qty.' unit(s) currently available.');
        }

        $cart = (array) $request->session()->get('web_cart', []);
        if (! array_key_exists($productId, $cart)) {
            return back()->with('cart_error', 'This product is no longer in your cart.');
        }

        $cart[$productId] = (int) $validated['quantity'];
        $request->session()->put('web_cart', $cart);

        return back()->with('cart_success', 'Cart quantity updated.');
    }

    public function removeFromCart(Request $request, int $productId)
    {
        $cart = (array) $request->session()->get('web_cart', []);
        unset($cart[$productId]);
        $request->session()->put('web_cart', $cart);

        return back()->with('cart_success', 'Product removed from your cart.');
    }

    public function checkoutPage(Request $request)
    {
        [$items, $subtotal] = $this->cartItems($request);
        if ($items->isEmpty()) {
            return redirect()->route('web.cart')->with('error', 'Your cart is empty.');
        }

        if ($items->contains(fn ($item) => $item->stock_issue)) {
            return redirect()->route('web.cart')->with('cart_error', 'Please update quantities for products with insufficient stock before checkout.');
        }

        // Website customers choose store pickup (Takeaway) or Delivery. Dine-in remains a POS option.
        $orderTypes = OrderType::query()
            ->whereIn(DB::raw('LOWER(order_name)'), ['takeaway', 'delivery'])
            ->orderByRaw("CASE WHEN LOWER(order_name) = 'takeaway' THEN 0 ELSE 1 END")
            ->get();

        if ($orderTypes->isEmpty()) {
            return redirect()->route('web.cart')->with('error', 'Online pickup/delivery options are not configured. Please contact the shop.');
        }

        $deliveryFee = max(0, (float) config('services.ecommerce.delivery_fee', 0));
        $checkoutToken = (string) $request->session()->get('web_checkout_token', '');
        if ($checkoutToken === '') {
            $checkoutToken = (string) Str::uuid();
            $request->session()->put('web_checkout_token', $checkoutToken);
        }

        return view('website.checkout', compact('items', 'subtotal', 'orderTypes', 'deliveryFee', 'checkoutToken'));
    }

    public function placeOrder(Request $request, PayWayService $payWay)
    {
        $validated = $request->validate([
            'order_type_id' => ['required', 'integer', 'exists:order_type,order_type_id'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'checkout_token' => ['required', 'string', 'max:80'],
        ]);

        $completedCheckouts = (array) $request->session()->get('web_checkout_completed', []);
        if (isset($completedCheckouts[$validated['checkout_token']])) {
            $completed = $completedCheckouts[$validated['checkout_token']];
            if (! empty($completed['payment_id'])) {
                return redirect()->route('web.payments.show', $completed['payment_id']);
            }
            if (! empty($completed['order_id'])) {
                return redirect()->route('web.orders.show', $completed['order_id']);
            }
        }

        if (! $payWay->isConfigured()) {
            return back()->withInput()->with('error', 'Online KHQR payment is not configured yet. Ask the administrator to add the ABA PayWay sandbox credentials.');
        }

        if (! filled(config('services.payway.callback_url'))) {
            return back()->withInput()->with('error', 'Set PAYWAY_CALLBACK_URL to your public HTTPS ngrok callback before accepting website KHQR payments.');
        }

        $cart = (array) $request->session()->get('web_cart', []);
        if ($cart === []) {
            return redirect()->route('web.cart')->with('error', 'Your cart is empty.');
        }

        $orderType = OrderType::query()->where('order_type_id', $validated['order_type_id'])->firstOrFail();
        $orderTypeName = strtolower(trim((string) $orderType->order_name));
        if (! in_array($orderTypeName, ['takeaway', 'delivery'], true)) {
            throw ValidationException::withMessages(['order_type_id' => 'Choose store pickup or home delivery for a website order.']);
        }

        $isDelivery = $orderTypeName === 'delivery';
        if ($isDelivery && blank($validated['delivery_address'] ?? null)) {
            throw ValidationException::withMessages(['delivery_address' => 'Please enter a delivery address for delivery orders.']);
        }

        $paymentMethod = PaymentMethod::query()->whereRaw('LOWER(payment_name) = ?', ['khqr'])->first();
        if (! $paymentMethod) {
            return back()->withInput()->with('error', 'The KHQR payment method is missing from payment_method.');
        }

        try {
            $user = auth()->user();
            $customer = Costumer::query()->where('user_id', $user->user_id)->first();
            if (! $customer) {
                $customer = Costumer::create([
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ]);
            }

            $result = DB::transaction(function () use ($cart, $validated, $orderType, $isDelivery, $customer, $paymentMethod, $payWay) {
                $productIds = array_map('intval', array_keys($cart));
                sort($productIds);
                $products = Product::query()
                    ->whereIn('product_id', $productIds)
                    ->where('status', 'Active')
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                if ($products->count() !== count($productIds)) {
                    throw ValidationException::withMessages(['cart' => 'One or more products are unavailable. Please update your cart.']);
                }

                $activeOffers = Offer::query()
                    ->activeNow()
                    ->whereIn('product_id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                $subtotal = 0.0;
                foreach ($cart as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    $quantity = (int) $quantity;
                    if (! $product || $quantity < 1 || ($product->isStockTracked() && $quantity > (int) $product->qty)) {
                        throw ValidationException::withMessages(['cart' => 'There is not enough stock for '.($product?->product_name ?? 'a selected product').'. Please return to your cart.']);
                    }
                    $offer = $activeOffers->get((int) $productId);
                    $unitPrice = $offer && is_numeric($offer->new_price) ? (float) $offer->new_price : (float) $product->price;
                    $subtotal += $unitPrice * $quantity;
                }

                $deliveryFee = $isDelivery ? max(0, (float) config('services.ecommerce.delivery_fee', 0)) : 0.0;
                $grandTotal = round($subtotal + $deliveryFee, 2);

                $order = Order::create([
                    'customer_id' => $customer->customer_id,
                    'cashier_id' => null,
                    'order_type_id' => $orderType->order_type_id,
                    'total_amount' => $grandTotal,
                    'discount' => 0,
                    'final_price' => $grandTotal,
                    'exchange_rate' => Settings::query()->value('exchange_rate'),
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'is_paid' => false,
                    'payment_status' => 'Unpaid',
                    'order_status' => 'pending',
                    'order_source' => 'website',
                    'customer_note' => $validated['customer_note'] ?? null,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->order_id,
                    'event_type' => 'order_created',
                    'from_status' => null,
                    'to_status' => 'pending',
                    'actor_type' => 'customer',
                    'actor_id' => auth()->id(),
                    'actor_label' => $customer->name,
                    'note' => 'Website order created. Fulfillment is waiting for verified payment.',
                    'created_at' => now(),
                ]);

                foreach ($cart as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    $offer = $activeOffers->get((int) $productId);
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $product->product_id,
                        'quantity' => (int) $quantity,
                        'price' => $offer && is_numeric($offer->new_price) ? (float) $offer->new_price : (float) $product->price,
                        'unit_cost' => (float) $product->cost,
                    ]);
                }

                if ($isDelivery) {
                    Delivery::create([
                        'order_id' => $order->order_id,
                        'address' => $validated['delivery_address'],
                        'cost' => $deliveryFee,
                        'status' => 'pending',
                    ]);
                }

                $payment = $this->createPayWayPayment($order, $customer, $payWay);

                return compact('order', 'payment');
            // The transaction calls ABA's external API. Do not let Laravel retry this
            // closure automatically on a deadlock, which could create orphan QR attempts.
            }, 1);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Website checkout failed.', [
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'We could not create your order or KHQR code. No order or stock changes were saved. Please try again or contact the shop.');
        }

        $completedCheckouts[$validated['checkout_token']] = [
            'order_id' => $result['order']->order_id,
            'payment_id' => $result['payment']->id,
        ];
        $request->session()->put('web_checkout_completed', array_slice($completedCheckouts, -10, null, true));
        $request->session()->forget(['web_cart', 'web_checkout_token']);

        return redirect()->route('web.payments.show', $result['payment']->id)
            ->with('success', 'Order #'.$result['order']->order_id.' was created. Scan the KHQR code to pay.');
    }

    public function showOrder(int $orderId)
    {
        $order = $this->ownedOrder($orderId)->with([
            'orderItems.product', 'orderType', 'paymentMethod', 'delivery', 'payments', 'statusHistory', 'review',
        ])->firstOrFail();
        $payment = $order->payments->sortByDesc('id')->first();

        return view('website.order', compact('order', 'payment'));
    }

    public function orderStatus(int $orderId): JsonResponse
    {
        // Always scope the request to the authenticated customer's own order.
        $order = $this->ownedOrder($orderId)
            ->with(['delivery', 'statusHistory', 'review'])
            ->firstOrFail();

        $events = $order->statusHistory
            ->sortByDesc('created_at')
            ->take(30)
            ->values()
            ->map(function (OrderStatusHistory $event) {
                $title = match ($event->event_type) {
                    'payment_collected' => 'Cash on Delivery collected',
                    'payment_verified' => 'Payment verified',
                    'telegram_notified' => 'Shop notified',
                    default => $event->to_status
                        ? 'Status: '.str_replace('_', ' ', ucfirst($event->to_status))
                        : ucfirst(str_replace('_', ' ', $event->event_type)),
                };

                return [
                    'id' => (int) $event->history_id,
                    'event_type' => $event->event_type,
                    'to_status' => $event->to_status,
                    'title' => $title,
                    'actor_type' => $event->actor_type,
                    'actor_label' => $event->actor_label,
                    'note' => $event->note,
                    'created_at_label' => optional($event->created_at)->format('d M Y, h:i A'),
                ];
            });

        return response()->json([
            'order_id' => $order->order_id,
            'order_status' => $order->order_status,
            'delivery_status' => $order->delivery?->status,
            'payment_status' => $order->payment_status,
            'paid' => (bool) $order->is_paid,
            'final_price' => (float) $order->final_price,
            'cod_collected_amount' => $order->cod_collected_amount !== null ? (float) $order->cod_collected_amount : null,
            'cod_collected_at' => $order->cod_collected_at?->toIso8601String(),
            'cod_collected_at_label' => $order->cod_collected_at?->format('d M Y, h:i A'),
            'cod_collection_note' => $order->cod_collection_note,
            'status_history' => $events,
            'review_html' => view('website.partials.order-review', ['order' => $order])->render(),
            'updated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /** Customers may release reserved inventory by cancelling their own unpaid order. */
    public function cancelOrder(int $orderId)
    {
        DB::transaction(function () use ($orderId) {
            $order = $this->ownedOrder($orderId)->lockForUpdate()->firstOrFail();

            $alreadyPaid = (bool) $order->is_paid || $order->payments()->where('status', 'paid')->exists();
            if ($alreadyPaid) {
                throw ValidationException::withMessages(['order' => 'A paid order cannot be cancelled here. Please contact the shop for help.']);
            }
            if ($order->order_status === 'cancelled') {
                throw ValidationException::withMessages(['order' => 'This order has already been cancelled.']);
            }
            if ($order->order_status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Only an order that has not entered preparation can be cancelled online.']);
            }
            if ($order->payments()->where('status', 'review_required')->exists()) {
                throw ValidationException::withMessages(['order' => 'This payment needs staff review before the order can be cancelled. Please contact the shop.']);
            }

            $items = OrderItem::query()->where('order_id', $order->order_id)->get(['product_id', 'quantity']);
            $productIds = $items->pluck('product_id')->filter()->unique()->sort()->values();
            $products = Product::query()
                ->whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($items->groupBy('product_id') as $productId => $productItems) {
                $product = $products->get((int) $productId);
                if (! $product || ! $product->isStockTracked()) {
                    continue;
                }

                $quantity = (int) $productItems->sum('quantity');
                $product->qty = (int) $product->qty + $quantity;
                $product->save();

                StockLog::create([
                    'product_id' => $product->product_id,
                    'user_id' => null,
                    'type' => 'IN',
                    'quantity' => $quantity,
                    'reason' => 'Website order cancellation',
                    'note' => 'Reserved stock returned for customer-cancelled order #'.$order->order_id.'.',
                ]);
            }

            $order->payments()->whereIn('status', ['pending', 'failed', 'declined', 'expired'])->update(['status' => 'cancelled']);
            $previousStatus = (string) $order->order_status;
            $order->order_status = 'cancelled';
            $order->save();
            if ($order->delivery) {
                $order->delivery()->update(['status' => 'cancelled']);
            }
            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'status_changed',
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
                'actor_type' => 'customer',
                'actor_id' => auth()->id(),
                'actor_label' => auth()->user()?->name,
                'note' => 'Customer cancelled an unpaid website order; reserved stock was returned.',
                'created_at' => now(),
            ]);
        }, 3);

        return redirect()->route('web.orders.show', $orderId)->with('success', 'Your unpaid order was cancelled and reserved stock was returned.');
    }

    public function retryPayWay(int $orderId, PayWayService $payWay)
    {
        $order = $this->ownedOrder($orderId)->with(['customer', 'orderItems.product'])->firstOrFail();

        if ($order->is_paid) {
            return redirect()->route('web.orders.show', $order->order_id)->with('success', 'This order is already paid.');
        }
        if ($order->order_status === 'cancelled') {
            return back()->with('error', 'A cancelled order cannot receive another payment attempt. Add the products to your cart to place a new order.');
        }
        if (! $payWay->isConfigured()) {
            return back()->with('error', 'ABA PayWay is not configured.');
        }
        if (! filled(config('services.payway.callback_url'))) {
            return back()->with('error', 'Set PAYWAY_CALLBACK_URL to your public HTTPS ngrok callback before generating a website KHQR.');
        }

        $latest = $order->payments()->latest('id')->first();
        if ($latest && $latest->status === 'pending' && (! $latest->expires_at || $latest->expires_at->isFuture())) {
            return redirect()->route('web.payments.show', $latest->id);
        }
        if ($latest && $latest->status === 'pending') {
            $latest->update(['status' => 'expired']);
        }

        try {
            $payment = DB::transaction(fn () => $this->createPayWayPayment($order, $order->customer, $payWay), 1);
        } catch (Throwable $exception) {
            Log::error('Website PayWay retry failed.', [
                'order_id' => $order->order_id,
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);
            return back()->with('error', 'A new KHQR payment could not be created. Please try again.');
        }

        return redirect()->route('web.payments.show', $payment->id);
    }

    private function createPayWayPayment(Order $order, ?Costumer $customer, PayWayService $payWay): Payment
    {
        $nameParts = preg_split('/\s+/', trim((string) ($customer?->name ?? 'Customer')), 2) ?: ['Customer'];
        $lineItems = $order->orderItems()->with('product')->get()->take(10)->map(fn ($item) => [
            'name' => (string) ($item->product?->product_name ?? 'Coffee Corner item'),
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
        ])->values()->all();

        $tranId = 'CC'.now('UTC')->format('ymdHis').Str::upper(Str::random(4));
        $currency = strtoupper((string) config('services.payway.currency', 'USD'));
        // Do not fall back to APP_URL: on local development that may generate a
        // localhost HTTP callback which ABA cannot reach.
        $callbackUrl = (string) config('services.payway.callback_url', '');
        $response = $payWay->generateQr([
            'tran_id' => $tranId,
            'amount' => (float) $order->final_price,
            'currency' => $currency,
            'first_name' => function_exists('mb_substr') ? mb_substr((string) ($nameParts[0] ?? 'Customer'), 0, 20) : substr((string) ($nameParts[0] ?? 'Customer'), 0, 20),
            'last_name' => function_exists('mb_substr') ? mb_substr((string) ($nameParts[1] ?? ''), 0, 20) : substr((string) ($nameParts[1] ?? ''), 0, 20),
            'email' => (string) (auth()->user()?->email ?? ''),
            'phone' => (string) ($customer?->phone ?? ''),
            'items' => $lineItems,
            'callback_url' => $callbackUrl,
            'return_params' => ['order_id' => $order->order_id],
        ]);

        $expiresAt = $response['_expires_at'] ?? null;
        return Payment::create([
            'order_id' => $order->order_id,
            'provider' => 'aba_payway',
            'tran_id' => $tranId,
            'amount' => (float) $order->final_price,
            'currency' => $currency,
            'status' => 'pending',
            'qr_string' => $response['qrString'] ?? null,
            'qr_image' => $response['qrImage'] ?? null,
            'abapay_deeplink' => $response['abapay_deeplink'] ?? null,
            'app_store' => $response['app_store'] ?? null,
            'play_store' => $response['play_store'] ?? null,
            'expires_at' => $expiresAt ?: now()->addMinutes((int) config('services.payway.lifetime', 15)),
            'raw_response' => $response,
        ]);
    }

    private function cartItems(Request $request): array
    {
        $cart = (array) $request->session()->get('web_cart', []);
        if ($cart === []) {
            return [collect(), 0.0];
        }

        $productIds = array_map('intval', array_keys($cart));
        $products = Product::query()
            ->with('category')
            ->whereIn('product_id', $productIds)
            ->where('status', 'Active')
            ->get()
            ->keyBy('product_id');

        $validCart = [];
        foreach ($cart as $productId => $quantity) {
            if ($products->has((int) $productId)) {
                $validCart[(int) $productId] = max(1, (int) $quantity);
            }
        }
        if (count($validCart) !== count($cart)) {
            $request->session()->put('web_cart', $validCart);
            $request->session()->flash('cart_error', 'Unavailable products were removed from your cart. Please review your selection.');
        }
        $cart = $validCart;
        if ($cart === []) {
            return [collect(), 0.0];
        }

        $activeOffers = Offer::query()
            ->activeNow()
            ->whereIn('product_id', array_keys($cart))
            ->get()
            ->keyBy('product_id');

        $items = collect();
        $subtotal = 0.0;
        foreach ($cart as $productId => $quantity) {
            $product = $products->get((int) $productId);
            if (! $product) {
                continue;
            }
            $quantity = max(1, (int) $quantity);
            $offer = $activeOffers->get((int) $productId);
            $originalPrice = (float) $product->price;
            $unitPrice = $offer && is_numeric($offer->new_price) ? (float) $offer->new_price : $originalPrice;
            $lineTotal = $unitPrice * $quantity;
            $stockIssue = $product->isStockTracked() && $quantity > (int) $product->qty;
            $items->push((object) [
                'product' => $product,
                'quantity' => $quantity,
                'offer' => $offer,
                'original_price' => $originalPrice,
                'unit_price' => $unitPrice,
                'line_saving' => max(0, ($originalPrice - $unitPrice) * $quantity),
                'line_total' => $lineTotal,
                'stock_issue' => $stockIssue,
                'available_quantity' => $product->isStockTracked() ? (int) $product->qty : null,
            ]);
            $subtotal += $lineTotal;
        }

        return [$items, round($subtotal, 2)];
    }

    private function ownedOrder(int $orderId)
    {
        return Order::query()
            ->where('order_id', $orderId)
            ->whereHas('customer', fn ($query) => $query->where('user_id', auth()->id()));
    }
}
