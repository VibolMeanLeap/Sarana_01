<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Settings;
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
    public function home()
    {
        // Keep the public homepage connected to real catalogue records. The old
        // page used hard-coded demo cards and a browser-only cart that never
        // reached the Laravel session cart.
        $products = Product::query()
            ->with('category')
            ->where('status', 'Active')
            ->where(function ($query) { $query->where('qty', '>', 0)->orWhereRaw("LOWER(TRIM(COALESCE(product_type, ''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')"); })
            ->orderByDesc('product_id')
            ->limit(8)
            ->get();

        $offersByProduct = Offer::query()
            ->activeNow()
            ->whereIn('product_id', $products->pluck('product_id'))
            ->get()
            ->keyBy('product_id');

        return view('website.main', compact('products', 'offersByProduct'));
    }

    public function shop()
    {
        $products = Product::query()
            ->with('category')
            ->where('status', 'Active')
            ->where(function ($query) { $query->where('qty', '>', 0)->orWhereRaw("LOWER(TRIM(COALESCE(product_type, ''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')"); })
            ->orderBy('category_id')
            ->orderBy('product_name')
            ->paginate(12);

        // Only scheduled offers that are live right now and whose products can be purchased
        // are promoted. The same offer records are also used for cart and checkout pricing.
        $offers = Offer::query()
            ->with('product')
            ->activeNow()
            ->whereHas('product', fn ($query) => $query
                ->where('status', 'Active')
                ->where(function ($productQuery) { $productQuery->where('qty', '>', 0)->orWhereRaw("LOWER(TRIM(COALESCE(product_type, ''))) IN ('non-stock','non stock','nonstock','no stock','service','digital','untracked')"); }))
            ->orderByDesc('discount')
            ->get();

        $offersByProduct = $offers->keyBy('product_id');

        return view('website.shop', compact('products', 'offers', 'offersByProduct'));
    }

    public function cart(Request $request)
    {
        [$items, $subtotal] = $this->cartItems($request);

        return view('website.cart', compact('items', 'subtotal'));
    }

    public function addToCart(Request $request, int $productId)
    {
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
        if ($product->isStockTracked() && $currentQuantity + 1 > (int) $product->qty) {
            return back()->with('cart_error', 'You cannot add more than the available stock.');
        }

        $cart[$productId] = $currentQuantity + 1;
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
            return back()->with('cart_error', 'Requested quantity exceeds available stock.');
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

        $orderTypes = OrderType::query()->orderBy('order_type_id')->get();
        return view('website.checkout', compact('items', 'subtotal', 'orderTypes'));
    }

    public function placeOrder(Request $request, PayWayService $payWay)
    {
        $validated = $request->validate([
            'order_type_id' => 'required|integer|exists:order_type,order_type_id',
            'customer_note' => 'nullable|string|max:500',
            'delivery_address' => 'nullable|string|max:255',
        ]);

        if (! $payWay->isConfigured()) {
            return back()->withInput()->with('error', 'Online KHQR payment is not configured yet. Ask the administrator to add the ABA PayWay sandbox credentials.');
        }

        $cart = (array) $request->session()->get('web_cart', []);
        if ($cart === []) {
            return redirect()->route('web.cart')->with('error', 'Your cart is empty.');
        }

        $orderType = OrderType::findOrFail($validated['order_type_id']);
        $isDelivery = strtolower((string) $orderType->order_name) === 'delivery';
        if ($isDelivery && blank($validated['delivery_address'] ?? null)) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Please enter a delivery address for delivery orders.',
            ]);
        }

        $paymentMethod = PaymentMethod::query()
            ->whereRaw('LOWER(payment_name) = ?', ['khqr'])
            ->first();
        if (! $paymentMethod) {
            return back()->withInput()->with('error', 'The KHQR payment method is missing from payment_method. Import the database patch first.');
        }

        try {
            $customer = Costumer::query()->where('user_id', auth()->id())->first();
            if (! $customer) {
                $user = auth()->user();
                $customer = Costumer::create([
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ]);
            }

            $result = DB::transaction(function () use ($cart, $validated, $orderType, $customer, $paymentMethod, $payWay) {
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
                        throw ValidationException::withMessages(['cart' => 'There is not enough stock for '.($product?->product_name ?? 'a selected product').'.']);
                    }
                    $offer = $activeOffers->get((int) $productId);
                    $unitPrice = $offer ? (float) $offer->new_price : (float) $product->price;
                    $subtotal += $unitPrice * $quantity;
                }

                $order = Order::create([
                    'customer_id' => $customer->customer_id,
                    'cashier_id' => null,
                    'order_type_id' => $orderType->order_type_id,
                    'total_amount' => round($subtotal, 2),
                    'discount' => 0,
                    'final_price' => round($subtotal, 2),
                    'exchange_rate' => Settings::query()->value('exchange_rate'),
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'is_paid' => false,
                    'payment_status' => 'Unpaid',
                    'order_status' => 'pending',
                    'order_source' => 'website',
                    'customer_note' => $validated['customer_note'] ?? null,
                ]);

                foreach ($cart as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    $offer = $activeOffers->get((int) $productId);
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $product->product_id,
                        'quantity' => (int) $quantity,
                        // Save the actual price paid so order history remains accurate after
                        // an offer expires or the product's regular price changes.
                        'price' => $offer ? (float) $offer->new_price : (float) $product->price,
                        'unit_cost' => (float) $product->cost,
                    ]);
                }

                if (strtolower((string) $orderType->order_name) === 'delivery') {
                    Delivery::create([
                        'order_id' => $order->order_id,
                        'address' => $validated['delivery_address'],
                        'cost' => 0,
                        'status' => 'pending',
                    ]);
                }

                $payment = $this->createPayWayPayment($order, $customer, $payWay);
                return compact('order', 'payment');
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Website checkout failed.', [
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'We could not create your order or KHQR code. No order stock changes were saved. Please try again or contact the shop.');
        }

        $request->session()->forget('web_cart');
        return redirect()->route('web.payments.show', $result['payment']->id)
            ->with('success', 'Order #'.$result['order']->order_id.' was created. Scan the KHQR code to pay.');
    }

    public function showOrder(int $orderId)
    {
        $order = $this->ownedOrder($orderId)->with([
            'orderItems.product', 'orderType', 'paymentMethod', 'delivery',
        ])->firstOrFail();
        $payment = $order->payments()->latest('id')->first();

        return view('website.order', compact('order', 'payment'));
    }

    public function orderStatus(int $orderId): JsonResponse
    {
        $order = $this->ownedOrder($orderId)->firstOrFail();

        return response()->json([
            'order_id' => $order->order_id,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'paid' => (bool) $order->is_paid,
        ])->header('Cache-Control', 'no-store');
    }

    public function retryPayWay(int $orderId, PayWayService $payWay)
    {
        $order = $this->ownedOrder($orderId)->with(['customer', 'orderItems.product'])->firstOrFail();

        if ($order->is_paid) {
            return redirect()->route('web.orders.show', $order->order_id)->with('success', 'This order is already paid.');
        }
        if ($order->order_status === 'cancelled') {
            return back()->with('error', 'A cancelled order cannot receive another payment attempt.');
        }
        if (! $payWay->isConfigured()) {
            return back()->with('error', 'ABA PayWay is not configured.');
        }

        $latest = $order->payments()->latest('id')->first();
        if ($latest && $latest->status === 'pending' && (! $latest->expires_at || $latest->expires_at->isFuture())) {
            return redirect()->route('web.payments.show', $latest->id);
        }
        if ($latest && $latest->status === 'pending') {
            $latest->update(['status' => 'expired']);
        }

        try {
            $payment = DB::transaction(fn () => $this->createPayWayPayment($order, $order->customer, $payWay), 3);
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
        $response = $payWay->generateQr([
            'tran_id' => $tranId,
            'amount' => (float) $order->final_price,
            'currency' => $currency,
            'first_name' => (function_exists('mb_substr') ? mb_substr((string) ($nameParts[0] ?? 'Customer'), 0, 20) : substr((string) ($nameParts[0] ?? 'Customer'), 0, 20)),
            'last_name' => (function_exists('mb_substr') ? mb_substr((string) ($nameParts[1] ?? ''), 0, 20) : substr((string) ($nameParts[1] ?? ''), 0, 20)),
            'email' => (string) (auth()->user()?->email ?? ''),
            'phone' => (string) ($customer?->phone ?? ''),
            'items' => $lineItems,
            'callback_url' => route('web.payments.payway.callback'),
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

        $activeOffers = Offer::query()
            ->activeNow()
            ->whereIn('product_id', $productIds)
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
            $unitPrice = $offer ? (float) $offer->new_price : $originalPrice;
            $lineTotal = $unitPrice * $quantity;
            $items->push((object) [
                'product' => $product,
                'quantity' => $quantity,
                'offer' => $offer,
                'original_price' => $originalPrice,
                'unit_price' => $unitPrice,
                'line_saving' => max(0, ($originalPrice - $unitPrice) * $quantity),
                'line_total' => $lineTotal,
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
