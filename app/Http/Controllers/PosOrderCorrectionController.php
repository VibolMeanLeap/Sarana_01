<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Costumer;
use App\Models\Order;
use App\Models\OrderCorrection;
use App\Models\OrderItem;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Support\StoreDeliveryFee;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class PosOrderCorrectionController extends Controller
{
    /**
     * Only paid, completed, cash POS orders without an external payment or
     * already-posted cash-ledger entry can use this controlled correction flow.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $orders = $this->eligibleOrdersQuery()
            ->with(['customer', 'cashier', 'orderType', 'paymentMethod'])
            ->withCount('orderItems')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    if (ctype_digit($search)) {
                        $nested->where('order_id', (int) $search);
                    }

                    $nested->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('order_date')
            ->orderByDesc('order_id')
            ->paginate(12)
            ->withQueryString();

        return view('pos.order-corrections.index', array_merge(
            compact('orders', 'search'),
            $this->checkoutLayoutData()
        ));
    }

    public function edit(int $orderId)
    {
        $order = Order::query()
            ->with([
                'customer',
                'cashier',
                'orderType',
                'paymentMethod',
                'orderItems.product',
                'corrections.createdBy',
                'corrections.adjustedBy',
            ])
            ->findOrFail($orderId);

        try {
            $this->assertEligibleOrder($order);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?: 'This order is not eligible for correction.';
            return redirect()->route('pos.corrections.index')->with('error', $message);
        }

        $usedProductIds = $order->orderItems->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $products = Product::query()
            ->where(function (Builder $query) use ($usedProductIds) {
                $query->where('status', 'Active');
                if ($usedProductIds !== []) {
                    $query->orWhereIn('product_id', $usedProductIds);
                }
            })
            ->orderBy('product_name')
            ->get(['product_id', 'product_name', 'price', 'cost', 'qty', 'product_type', 'status']);

        $productOptions = $products->map(fn (Product $product) => [
            'id' => (int) $product->product_id,
            'name' => (string) $product->product_name,
            'price' => (float) $product->price,
            'status' => (string) $product->status,
        ])->values();

        $history = $order->corrections;
        $pendingAdjustment = $history->firstWhere('payment_adjustment_status', 'pending');

        $initialItems = old('items', $order->orderItems->map(fn (OrderItem $item) => [
            'product_id' => (int) $item->product_id,
            'quantity' => (int) $item->quantity,
            'unit_price' => (float) $item->price,
        ])->values()->all());

        return view('pos.order-corrections.edit', array_merge(
            compact(
                'order',
                'products',
                'history',
                'pendingAdjustment',
                'initialItems',
                'productOptions'
            ),
            $this->checkoutLayoutData()
        ));
    }

    public function update(Request $request, int $orderId)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customer,customer_id'],
            'discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'reason' => ['required', Rule::in([
                'wrong_product',
                'wrong_quantity',
                'wrong_customer',
                'wrong_price_discount',
                'other',
            ])],
            'notes' => ['required', 'string', 'min:5', 'max:2000'],
            'confirmation' => ['required', Rule::in(['CORRECT'])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:product,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'confirmation.in' => 'Type CORRECT exactly to confirm this completed-order correction.',
            'items.min' => 'A corrected order must contain at least one product.',
            'items.*.product_id.distinct' => 'Each product may appear only once. Combine quantities for duplicate products.',
        ]);

        try {
            $correctionId = DB::transaction(function () use ($validated, $orderId): int {
                $order = Order::query()
                    ->with(['customer', 'orderType', 'paymentMethod'])
                    ->where('order_id', $orderId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertEligibleOrder($order);

                if (OrderCorrection::query()
                    ->where('order_id', $order->order_id)
                    ->where('payment_adjustment_status', 'pending')
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'payment_adjustment' => 'Settle the pending cash difference or refund before making another correction.',
                    ]);
                }

                $oldItems = OrderItem::query()
                    ->where('order_id', $order->order_id)
                    ->orderBy('order_item_id')
                    ->lockForUpdate()
                    ->get();

                if ($oldItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'This completed order has no original line items to correct.',
                    ]);
                }

                $oldProductIds = $oldItems->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
                $newProductIds = collect($validated['items'])
                    ->pluck('product_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $allProductIds = $oldProductIds->merge($newProductIds)->unique()->sort()->values();

                $products = Product::query()
                    ->whereIn('product_id', $allProductIds)
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                if ($products->count() !== $allProductIds->count()) {
                    throw ValidationException::withMessages([
                        'items' => 'One or more products no longer exist. No changes were saved.',
                    ]);
                }

                foreach ($newProductIds as $productId) {
                    $product = $products->get((int) $productId);
                    if ($product->status !== 'Active' && ! $oldProductIds->contains((int) $productId)) {
                        throw ValidationException::withMessages([
                            'items' => 'Newly added products must be active. Existing inactive order products may remain.',
                        ]);
                    }
                }

                $oldStockByProduct = [];
                $oldUnitCosts = [];
                foreach ($oldItems as $oldItem) {
                    $product = $products->get((int) $oldItem->product_id);
                    $oldUnitCosts[(int) $oldItem->product_id] = (float) $oldItem->unit_cost;
                    if ($product && $product->isStockTracked()) {
                        $productId = (int) $oldItem->product_id;
                        $oldStockByProduct[$productId] = ($oldStockByProduct[$productId] ?? 0) + (int) $oldItem->quantity;
                    }
                }

                $newStockByProduct = [];
                $normalisedItems = [];
                $subtotal = 0.0;

                foreach ($validated['items'] as $line) {
                    $productId = (int) $line['product_id'];
                    $product = $products->get($productId);
                    $quantity = (int) $line['quantity'];
                    $unitPrice = round((float) $line['unit_price'], 2);
                    $subtotal += $quantity * $unitPrice;

                    if ($product->isStockTracked()) {
                        $newStockByProduct[$productId] = ($newStockByProduct[$productId] ?? 0) + $quantity;
                    }

                    $normalisedItems[] = [
                        'product_id' => $productId,
                        'product_name' => (string) $product->product_name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'unit_cost' => array_key_exists($productId, $oldUnitCosts)
                            ? $oldUnitCosts[$productId]
                            : (float) $product->cost,
                    ];
                }

                // Existing completed stock must be traceable before it can be reversed.
                // This prevents a correction from adding stock that was never deducted.
                $this->assertStockLedgerMatchesOrder($order->order_id, $oldStockByProduct);

                foreach ($newStockByProduct as $productId => $newQuantity) {
                    $product = $products->get((int) $productId);
                    $availableAfterRestore = (int) $product->qty + (int) ($oldStockByProduct[$productId] ?? 0);
                    if ($newQuantity > $availableAfterRestore) {
                        throw ValidationException::withMessages([
                            'items' => 'Insufficient stock for '.$product->product_name.'. Available after reversing this order: '.$availableAfterRestore.'.',
                        ]);
                    }
                }

                $newCustomer = null;
                if (! empty($validated['customer_id'])) {
                    $newCustomer = Costumer::query()
                        ->where('customer_id', (int) $validated['customer_id'])
                        ->firstOrFail();
                }

                $beforeSnapshot = $this->makeSnapshot(
                    $order,
                    $order->customer,
                    $oldItems->map(fn (OrderItem $item) => [
                        'product_id' => (int) $item->product_id,
                        'product_name' => (string) ($products->get((int) $item->product_id)?->product_name ?? 'Removed product'),
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->price,
                        'unit_cost' => (float) $item->unit_cost,
                    ])->values()->all()
                );

                $newTotal = round($subtotal, 2);
                $newDiscount = round((float) $validated['discount'], 2);
                $newFinal = round($newTotal * (1 - ($newDiscount / 100)), 2);
                $oldFinal = round((float) $order->final_price, 2);
                $difference = round($newFinal - $oldFinal, 2);
                $adjustmentStatus = abs($difference) < 0.01 ? 'not_required' : 'pending';

                $afterSnapshot = [
                    'customer' => $newCustomer ? [
                        'customer_id' => (int) $newCustomer->customer_id,
                        'name' => (string) $newCustomer->name,
                        'phone' => (string) $newCustomer->phone,
                    ] : null,
                    'total_amount' => $newTotal,
                    'discount_percent' => $newDiscount,
                    'final_price' => $newFinal,
                    'items' => $normalisedItems,
                ];

                $correction = OrderCorrection::create([
                    'order_id' => $order->order_id,
                    'created_by' => auth()->id(),
                    'reason' => $validated['reason'],
                    'notes' => trim($validated['notes']),
                    'before_snapshot' => $beforeSnapshot,
                    'after_snapshot' => $afterSnapshot,
                    'original_total' => $oldFinal,
                    'corrected_total' => $newFinal,
                    'difference_amount' => $difference,
                    'payment_adjustment_status' => $adjustmentStatus,
                ]);

                // Reverse the original stock first. The database insert trigger,
                // when installed, will deduct the corrected lines as they are added.
                $qtyBeforeCorrectedSale = [];
                foreach ($oldStockByProduct as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    $product->qty = (int) $product->qty + (int) $quantity;
                    $product->save();

                    StockLog::create([
                        'product_id' => (int) $productId,
                        'user_id' => auth()->id(),
                        'type' => 'IN',
                        'quantity' => (int) $quantity,
                        'reason' => 'Order correction',
                        'note' => 'Stock returned for POS order #'.$order->order_id.'; correction ID '.$correction->correction_id.'.',
                    ]);
                }

                foreach ($newStockByProduct as $productId => $quantity) {
                    $qtyBeforeCorrectedSale[(int) $productId] = (int) $products->get((int) $productId)->qty;
                }

                OrderItem::query()->where('order_id', $order->order_id)->delete();

                foreach ($normalisedItems as $line) {
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['unit_price'],
                        'unit_cost' => $line['unit_cost'],
                    ]);
                }

                // The project may be running with the supplied stock trigger or with a
                // schema that does not have it. Detect what actually happened per item
                // and apply the OUT movement only when the trigger did not do so.
                foreach ($newStockByProduct as $productId => $quantity) {
                    $beforeQty = (int) ($qtyBeforeCorrectedSale[(int) $productId] ?? 0);
                    $afterQty = (int) Product::query()->where('product_id', (int) $productId)->value('qty');
                    $expectedWithTrigger = $beforeQty - (int) $quantity;

                    if ($afterQty === $expectedWithTrigger) {
                        // The order_items INSERT trigger deducted stock and created its Sale log.
                        continue;
                    }

                    if ($afterQty === $beforeQty) {
                        $product = $products->get((int) $productId);
                        $product->qty = $expectedWithTrigger;
                        $product->save();

                        StockLog::create([
                            'product_id' => (int) $productId,
                            'user_id' => auth()->id(),
                            'type' => 'OUT',
                            'quantity' => (int) $quantity,
                            'reason' => 'Order correction',
                            'note' => 'Corrected sale issued for POS order #'.$order->order_id.'; correction ID '.$correction->correction_id.'.',
                        ]);
                        continue;
                    }

                    throw ValidationException::withMessages([
                        'items' => 'Inventory changed unexpectedly while saving. The correction was rolled back; please refresh stock and try again.',
                    ]);
                }

                $order->update([
                    'customer_id' => $newCustomer?->customer_id,
                    'total_amount' => $newTotal,
                    'discount' => $newDiscount,
                    'final_price' => $newFinal,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->order_id,
                    'event_type' => 'order_corrected',
                    'from_status' => 'completed',
                    'to_status' => 'completed',
                    'actor_type' => 'admin',
                    'actor_id' => auth()->id(),
                    'actor_label' => auth()->user()?->name ?? 'Administrator',
                    'note' => 'Completed POS order corrected. Reason: '.$validated['reason'].'. '.$validated['notes']
                        .($adjustmentStatus === 'pending'
                            ? ' Cash adjustment pending: '.($difference > 0 ? 'collect ' : 'refund ').number_format(abs($difference), 2).' USD.'
                            : ' No cash adjustment required.'),
                    'created_at' => now(),
                ]);

                return (int) $correction->correction_id;
            }, 3);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with(
                'error',
                'The completed order could not be corrected. The database transaction was rolled back; no partial order or stock update was saved.'
            );
        }

        return redirect()->route('pos.corrections.edit', $orderId)
            ->with('success', 'Order #'.$orderId.' was corrected and its original values were saved in the correction history.'.$this->adjustmentMessage($correctionId));
    }

    public function settleAdjustment(Request $request, int $orderId, int $correctionId)
    {
        $validated = $request->validate([
            'adjustment_type' => ['required', Rule::in(['collected', 'refunded'])],
            'payment_adjustment_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($validated, $orderId, $correctionId) {
            $order = Order::query()->where('order_id', $orderId)->lockForUpdate()->firstOrFail();
            $correction = OrderCorrection::query()
                ->where('correction_id', $correctionId)
                ->where('order_id', $order->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($correction->payment_adjustment_status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment_adjustment' => 'This correction has no pending cash adjustment.',
                ]);
            }

            $expectedType = (float) $correction->difference_amount > 0 ? 'collected' : 'refunded';
            if ($validated['adjustment_type'] !== $expectedType) {
                throw ValidationException::withMessages([
                    'adjustment_type' => 'The adjustment action does not match the correction amount.',
                ]);
            }

            $correction->update([
                'payment_adjustment_status' => 'settled',
                'payment_adjustment_type' => $validated['adjustment_type'],
                'payment_adjustment_note' => trim($validated['payment_adjustment_note']),
                'payment_adjusted_by' => auth()->id(),
                'payment_adjusted_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'order_correction_payment_adjusted',
                'from_status' => 'completed',
                'to_status' => 'completed',
                'actor_type' => 'admin',
                'actor_id' => auth()->id(),
                'actor_label' => auth()->user()?->name ?? 'Administrator',
                'note' => 'Correction #'.$correction->correction_id.' cash difference marked as '.$validated['adjustment_type'].'. '.$validated['payment_adjustment_note'],
                'created_at' => now(),
            ]);

            return true;
        }, 3);

        return redirect()->route('pos.corrections.edit', $orderId)
            ->with('success', 'Cash adjustment marked as confirmed. The confirmation note was added to the audit history.');
    }

    /**
     * The shared POS layout includes the checkout modal on every page that
     * extends layout.pos. Supply its dropdown data to correction pages too.
     */
    private function checkoutLayoutData(): array
    {
        $customers = Costumer::query()
            ->orderBy('name')
            ->get(['customer_id', 'name', 'phone']);

        $orderTypes = OrderType::query()
            ->orderBy('order_name')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->whereIn(DB::raw('LOWER(TRIM(payment_name))'), [
                'cash',
                'khqr',
                'cash on delivery',
                'cash-on-delivery',
                'cod',
            ])
            ->orderBy('payment_method_id')
            ->get();

        $deliveryFee = StoreDeliveryFee::current();

        return compact('customers', 'orderTypes', 'paymentMethods', 'deliveryFee');
    }

    private function eligibleOrdersQuery(): Builder
    {
        return Order::query()
            ->where('order_source', 'pos')
            ->where('is_paid', true)
            ->whereRaw("LOWER(TRIM(COALESCE(order_status, ''))) = 'completed'")
            ->whereHas('paymentMethod', fn (Builder $query) => $query->whereRaw("LOWER(TRIM(COALESCE(payment_name, ''))) = 'cash'"))
            ->whereHas('orderType', fn (Builder $query) => $query->whereRaw("LOWER(TRIM(COALESCE(order_name, ''))) <> 'delivery'"))
            ->whereDoesntHave('payments')
            ->whereDoesntHave('cashTransactions');
    }

    private function assertEligibleOrder(Order $order): void
    {
        if (($order->order_source ?? '') !== 'pos') {
            throw ValidationException::withMessages(['order' => 'Only orders originally created in the POS can be corrected here.']);
        }

        if (! (bool) $order->is_paid || strtolower((string) $order->order_status) !== 'completed') {
            throw ValidationException::withMessages(['order' => 'Only paid and completed orders can be corrected.']);
        }

        if (strtolower(trim((string) $order->paymentMethod?->payment_name)) !== 'cash') {
            throw ValidationException::withMessages(['order' => 'This first correction workflow supports completed Cash sales only. KHQR and other payment methods must use their own refund or adjustment workflow.']);
        }

        if (strtolower(trim((string) $order->orderType?->order_name)) === 'delivery' || $order->delivery()->exists()) {
            throw ValidationException::withMessages(['order' => 'Delivery orders cannot be corrected here because their fulfillment and delivery records need a separate adjustment workflow.']);
        }

        if (Payment::query()->where('order_id', $order->order_id)->exists()) {
            throw ValidationException::withMessages(['order' => 'This order has an external payment record. Use the payment provider refund or adjustment process instead.']);
        }

        if (CashTransaction::query()->where('order_id', $order->order_id)->exists()) {
            throw ValidationException::withMessages(['order' => 'This order is already linked to the cash ledger. Reconcile its accounting entry before correcting the sale.']);
        }
    }

    /**
     * Compare append-only stock movement history for this order with the active
     * order lines. Historical orders without verifiable stock movements are
     * intentionally blocked rather than risking an incorrect inventory increase.
     */
    private function assertStockLedgerMatchesOrder(int $orderId, array $oldStockByProduct): void
    {
        if ($oldStockByProduct === []) {
            return;
        }

        $productIds = array_keys($oldStockByProduct);
        $candidateLogs = StockLog::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('reason', ['Sale', 'Sale correction', 'Sale reversal', 'Order correction'])
            ->where('note', 'like', '%#'.$orderId.'%')
            ->get(['product_id', 'type', 'quantity', 'note']);

        $netByProduct = [];
        foreach ($candidateLogs as $log) {
            $note = (string) ($log->note ?? '');
            if (! preg_match('/#'.preg_quote((string) $orderId, '/').'(?![0-9])/u', $note)) {
                continue;
            }

            $productId = (int) $log->product_id;
            $type = strtolower(trim((string) $log->type));
            $quantity = (int) $log->quantity;
            $netByProduct[$productId] = ($netByProduct[$productId] ?? 0)
                + ($type === 'out' ? $quantity : ($type === 'in' ? -$quantity : 0));
        }

        foreach ($oldStockByProduct as $productId => $expectedQuantity) {
            if ((int) ($netByProduct[(int) $productId] ?? 0) !== (int) $expectedQuantity) {
                throw ValidationException::withMessages([
                    'items' => 'Stock history for this order cannot be verified for one or more products. To protect inventory, this order cannot be edited automatically. Please audit its stock movements first.',
                ]);
            }
        }
    }

    private function makeSnapshot(Order $order, ?Costumer $customer, array $items): array
    {
        return [
            'customer' => $customer ? [
                'customer_id' => (int) $customer->customer_id,
                'name' => (string) $customer->name,
                'phone' => (string) $customer->phone,
            ] : null,
            'total_amount' => (float) $order->total_amount,
            'discount_percent' => (float) $order->discount,
            'final_price' => (float) $order->final_price,
            'items' => $items,
        ];
    }

    private function adjustmentMessage(int $correctionId): string
    {
        $correction = OrderCorrection::query()->find($correctionId);
        if (! $correction || $correction->payment_adjustment_status === 'not_required') {
            return ' No extra collection or refund is required.';
        }

        $amount = number_format(abs((float) $correction->difference_amount), 2);
        return (float) $correction->difference_amount > 0
            ? ' Please collect the additional $'.$amount.' and record the confirmation.'
            : ' Please refund $'.$amount.' and record the confirmation.';
    }
}
