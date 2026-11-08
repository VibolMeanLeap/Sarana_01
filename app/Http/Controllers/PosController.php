<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Costumer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Settings;
use App\Services\PayWayService;
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
        $products = Product::query()
            ->with('category')
            ->where('status', 'Active')
            ->orderBy('product_name')
            ->get();
        $customers = Costumer::query()->orderBy('name')->get();
        $orderTypes = OrderType::query()->orderBy('order_name')->get();
        $paymentMethods = PaymentMethod::query()->orderBy('payment_method_id')->get();

        return view('pos.dashboard', compact('categories', 'products', 'customers', 'orderTypes', 'paymentMethods'));
    }

    /**
     * Save a POS order. Cash is completed at the counter; KHQR remains unpaid
     * until PayWay's server-to-server Check Transaction API verifies it.
     * Card is rejected until a real card-terminal integration is configured.
     */
    public function checkout(Request $request, PayWayService $payWay)
    {
        $validated = $request->validate([
            'cart' => 'required|string|max:20000',
            'customer_id' => 'nullable|integer|exists:customer,customer_id',
            'order_type_id' => 'required|integer|exists:order_type,order_type_id',
            'payment_method_id' => 'required|integer|exists:payment_method,payment_method_id',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

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
        ])->validate()['items'];

        $paymentMethod = PaymentMethod::query()->findOrFail($validated['payment_method_id']);
        $methodName = Str::lower(trim((string) $paymentMethod->payment_name));

        if (! in_array($methodName, ['cash', 'khqr'], true)) {
            return back()->withInput()->with('error', 'Card payment is not connected to a card terminal yet. Please choose Cash or KHQR.');
        }

        if ($methodName === 'khqr' && ! $payWay->isConfigured()) {
            return back()->withInput()->with('error', 'ABA PayWay is not configured. Add your sandbox merchant ID and API key to the local .env file first.');
        }

        try {
            $result = DB::transaction(function () use ($validated, $items, $methodName, $paymentMethod, $payWay) {
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
                foreach ($items as $item) {
                    $product = $products->get((int) $item['product_id']);
                    $quantity = (int) $item['quantity'];

                    if (! $product || ($product->isStockTracked() && (int) $product->qty < $quantity)) {
                        throw ValidationException::withMessages([
                            'cart' => 'Not enough stock for '.($product?->product_name ?? 'one of the selected products').'. Please refresh and try again.',
                        ]);
                    }

                    $subtotal += (float) $product->price * $quantity;
                }

                $discount = (float) ($validated['discount'] ?? 0);
                $finalPrice = round($subtotal * (1 - ($discount / 100)), 2);
                $isCash = $methodName === 'cash';

                $order = Order::create([
                    'customer_id' => $validated['customer_id'] ?: null,
                    'cashier_id' => auth()->id(),
                    'order_type_id' => $validated['order_type_id'],
                    'total_amount' => round($subtotal, 2),
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'exchange_rate' => Settings::query()->value('exchange_rate') ?: 4100,
                    'payment_method_id' => $paymentMethod->payment_method_id,
                    'is_paid' => $isCash,
                    'payment_status' => $isCash ? 'Paid' : 'Unpaid',
                    'order_status' => $isCash ? 'completed' : 'pending',
                    'order_source' => 'pos',
                ]);

                foreach ($items as $item) {
                    $product = $products->get((int) $item['product_id']);
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $product->product_id,
                        'quantity' => (int) $item['quantity'],
                        'price' => (float) $product->price,
                        'unit_cost' => (float) $product->cost,
                    ]);
                }

                if ($isCash) {
                    return ['order' => $order, 'payment' => null];
                }

                $customer = $order->customer;
                $customerName = trim((string) ($customer?->name ?: auth()->user()?->name ?: 'POS Customer'));
                $nameParts = preg_split('/\s+/', $customerName, 2) ?: ['POS Customer'];
                $lineItems = $order->orderItems()->with('product')->get()->take(10)->map(fn ($line) => [
                    'name' => (string) ($line->product?->product_name ?? 'Coffee Corner item'),
                    'quantity' => (int) $line->quantity,
                    'price' => (float) $line->price,
                ])->values()->all();

                $transactionId = 'CC'.now('UTC')->format('ymdHis').Str::upper(Str::random(4));
                $currency = strtoupper((string) config('services.payway.currency', 'USD'));
                $qr = $payWay->generateQr([
                    'tran_id' => $transactionId,
                    'amount' => $finalPrice,
                    'currency' => $currency,
                    'first_name' => (function_exists('mb_substr') ? mb_substr((string) ($nameParts[0] ?? 'POS'), 0, 20) : substr((string) ($nameParts[0] ?? 'POS'), 0, 20)),
                    'last_name' => (function_exists('mb_substr') ? mb_substr((string) ($nameParts[1] ?? ''), 0, 20) : substr((string) ($nameParts[1] ?? ''), 0, 20)),
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

                return ['order' => $order, 'payment' => $payment];
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            Log::error('POS checkout database error.', [
                'cashier_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'The sale could not be saved. No changes should be committed; refresh the POS screen and try again.');
        } catch (Throwable $exception) {
            Log::error('POS checkout failed.', [
                'cashier_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'The sale or KHQR request failed. Check the server log and your PayWay configuration before retrying.');
        }

        if ($result['payment']) {
            return redirect()->route('pos.payments.show', $result['payment']->id)
                ->with('success', 'Order #'.$result['order']->order_id.' was created. Scan KHQR; it remains unpaid until ABA verifies it.');
        }

        return redirect()->route('pos.dashboard')
            ->with('success', 'Cash sale completed. Receipt / order #'.$result['order']->order_id.' has been saved.');
    }
}
