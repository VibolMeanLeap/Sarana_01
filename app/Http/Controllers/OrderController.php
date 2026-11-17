<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Costumer;
use App\Models\User;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\OrderReviewFlag;
use App\Models\StockLog;
use App\Services\AdminActionCountService;
use App\Support\AdminDateFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // ============================================
    // 1. បង្ហាញបញ្ជីការកម្ម៉ង់ទាំងអស់ (Order List)
    // ============================================
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'in:paid,unpaid'],
            'order_status' => ['nullable', 'in:pending,confirmed,preparing,ready,out_for_delivery,completed,cancelled'],
            'source' => ['nullable', 'in:pos,website'],
            'review_status' => ['nullable', 'in:open,clear'],
            'date_preset' => ['nullable', 'in:today,week,month,all,custom'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            // Keep legacy period URLs working while new links use date_preset.
            'period' => ['nullable', 'in:today,week,month'],
            'sort_by' => ['nullable', 'in:order_date,order_id,final_price'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $query = Order::query()
            ->with(['customer', 'cashier', 'orderType', 'paymentMethod', 'payments', 'delivery', 'orderItems.product', 'statusHistory', 'reviewFlags.relatedOrder'])
            ->withCount(['orderItems', 'payments', 'delivery', 'review', 'cashTransactions', 'openReviewFlags', 'corrections'])
            ->withExists([
                'corrections as has_pending_correction_adjustment' => fn ($correctionQuery) =>
                    $correctionQuery->where('payment_adjustment_status', 'pending'),
            ]);

        $search = trim((string) ($validated['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_note', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('cashier', function ($cashierQuery) use ($search) {
                        $cashierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payments', function ($paymentQuery) use ($search) {
                        $paymentQuery->where('tran_id', 'like', "%{$search}%");
                    });
            });
        }

        // Keep the old ?status=paid|unpaid query string working for bookmarked pages.
        $paymentStatus = $validated['payment_status'] ?? $request->input('status');
        if (in_array($paymentStatus, ['paid', 'unpaid'], true)) {
            $query->where('is_paid', $paymentStatus === 'paid' ? 1 : 0);
        }

        if (! empty($validated['order_status'])) {
            $query->where('order_status', $validated['order_status']);
        }
        if (! empty($validated['source'])) {
            $query->where('order_source', $validated['source']);
        }
        if (($validated['review_status'] ?? null) === 'open') {
            $query->whereHas('openReviewFlags');
        } elseif (($validated['review_status'] ?? null) === 'clear') {
            $query->whereDoesntHave('openReviewFlags');
        }
        // Use the same shared date-preset resolver as Product Sales, Reports,
        // Receipt Corrections, and the other admin list pages. Legacy ?period=
        // links remain supported for existing bookmarks.
        $dateFilter = AdminDateFilter::resolve(
            $request,
            'start_date',
            'end_date',
            ['preset' => ['period']]
        );
        AdminDateFilter::apply($query, 'order_date', $dateFilter);

        $sortBy = ['order_date' => 'order_date', 'order_id' => 'order_id', 'final_price' => 'final_price'][$validated['sort_by'] ?? 'order_date'];
        $sortDirection = $validated['sort_dir'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 10);

        // These totals are calculated before pagination so the table footer
        // represents every record matching the active filters, not this page.
        $filteredOrderCount = (clone $query)->count();
        $filteredFinalTotal = (float) (clone $query)->sum(DB::raw('COALESCE(final_price, total_amount, 0)'));

        $orders = $query->orderBy($sortBy, $sortDirection)
            ->orderByDesc('order_id')
            ->paginate($perPage)
            ->withQueryString();

        // Show the same protected receipt-correction workflow used by POS,
        // but only for orders that satisfy its eligibility rules. The edit
        // endpoint re-checks eligibility server-side before any change is saved.
        $orders->getCollection()->each(function (Order $order): void {
            $blockReason = $this->receiptCorrectionBlockReason($order);
            $hasPendingAdjustment = $blockReason === null
                && (bool) ($order->has_pending_correction_adjustment ?? false);

            $order->setAttribute('can_correct_receipt', $blockReason === null && ! $hasPendingAdjustment);
            $order->setAttribute('can_settle_correction_adjustment', $hasPendingAdjustment);
            $order->setAttribute('correction_block_reason', $hasPendingAdjustment
                ? 'A previous receipt correction has a pending cash collection or refund. Open the correction workspace to confirm it before making another correction.'
                : $blockReason);
        });

        $summary = [
            'total' => Order::count(),
            'pending' => Order::where('is_paid', 0)
                ->where('order_status', '<>', 'cancelled')
                ->count(),
            'completed' => Order::where('order_status', 'completed')->count(),
            'revenue_today' => Order::where('is_paid', 1)
                ->whereDate('order_date', today())
                ->sum('final_price'),
        ];

        if ($request->expectsJson()) {
            $orderStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];

            return response()->json([
                'success' => true,
                'replace_table' => $orders->currentPage() === 1,
                'rows_html' => view('admin.partials.orders-table-body', compact('orders', 'orderStatuses'))->render(),
                'pagination_html' => view('admin.partials.orders-pagination', compact('orders'))->render(),
                'summary' => $summary,
                'filtered_order_count' => $filteredOrderCount,
                'filtered_final_total' => $filteredFinalTotal,
                'action_counts' => app(AdminActionCountService::class)->get(),
                'latest_order_id' => (int) (Order::max('order_id') ?? 0),
                'checked_at' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view('admin.older', compact('orders', 'summary', 'filteredOrderCount', 'filteredFinalTotal', 'perPage', 'dateFilter'));
    }

    /**
     * Explain the same eligibility and preflight checks enforced by the POS
     * correction workflow. Keep this list aligned with
     * PosOrderCorrectionController::assertEligibleOrder() and its stock checks.
     */
    private function receiptCorrectionBlockReason(Order $order): ?string
    {
        if (strtolower(trim((string) $order->order_source)) !== 'pos') {
            return 'Website orders use the storefront payment and fulfilment workflow. They cannot be corrected through the POS receipt tool.';
        }

        if (! (bool) $order->is_paid) {
            return 'This order has not been paid. Complete and record payment before correcting a receipt.';
        }

        $status = strtolower(trim((string) $order->order_status));
        if ($status !== 'completed') {
            return 'This order is not completed yet. Only completed orders can be corrected.';
        }

        if (strtolower(trim((string) $order->paymentMethod?->payment_name)) !== 'cash') {
            return 'This correction workflow supports POS cash sales only. KHQR and other payment methods need their own payment-provider refund or adjustment workflow.';
        }

        if (! $order->orderType) {
            return 'The order type record is missing. Restore or review the order type before correcting this receipt.';
        }

        if (strtolower(trim((string) $order->orderType->order_name)) === 'delivery' || $order->delivery) {
            return 'This is a delivery order or has a delivery record. Its fulfilment and delivery history must be adjusted through the delivery workflow.';
        }

        if ((int) ($order->payments_count ?? 0) > 0 || $order->payments->isNotEmpty()) {
            return 'This order has an external payment record. Use the payment provider refund or adjustment process before changing the receipt.';
        }

        if ((int) ($order->cash_transactions_count ?? 0) > 0) {
            return 'This order is already posted to the cash ledger. Reconcile its accounting entry before correcting the sale.';
        }

        if ((bool) ($order->has_pending_correction_adjustment ?? false)) {
            // This is not permanently blocked: the row action opens the correction
            // workspace directly to the existing collect/refund confirmation panel.
            return null;
        }

        $items = $order->orderItems;
        if ($items->isEmpty()) {
            return 'This order has no original product lines. The receipt cannot be rebuilt safely.';
        }

        $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($productIds->contains(0) || $items->contains(fn ($item) => ! $item->product)) {
            return 'One or more products on this receipt no longer exist. Restore or audit the product records before correcting the order.';
        }

        $stockByProduct = [];
        foreach ($items as $item) {
            $product = $item->product;
            if ($product && $product->isStockTracked()) {
                $productId = (int) $item->product_id;
                $stockByProduct[$productId] = ($stockByProduct[$productId] ?? 0) + (int) $item->quantity;
            }
        }

        return $this->stockCorrectionBlockReason((int) $order->order_id, $stockByProduct);
    }

    /** Return a visible explanation if original stock deductions cannot be verified. */
    private function stockCorrectionBlockReason(int $orderId, array $expectedStock): ?string
    {
        if ($expectedStock === []) {
            return null;
        }

        $candidateLogs = StockLog::query()
            ->whereIn('product_id', array_keys($expectedStock))
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

        foreach ($expectedStock as $productId => $quantity) {
            if ((int) ($netByProduct[(int) $productId] ?? 0) !== (int) $quantity) {
                return 'The stock movement history does not match the products on this receipt. Audit the stock history first; automatic correction is disabled to protect inventory.';
            }
        }

        return null;
    }

    /** Print a full order receipt for administrator reconciliation. */
    public function receipt($orderId)
    {
        $order = Order::with([
            'customer', 'cashier', 'orderType', 'paymentMethod', 'payments',
            'orderItems.product', 'reviewFlags.relatedOrder',
        ])->findOrFail($orderId);

        return view('admin.orders.receipt', compact('order'));
    }

    /** Add an audit marker without editing, cancelling, or deleting the order. */
    public function storeReviewFlag(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        $validated = $request->validate([
            'reason' => ['required', Rule::in([
                'incorrect_total', 'wrong_customer', 'payment_mismatch',
                'possible_duplicate', 'wrong_items', 'receipt_needed', 'other',
            ])],
            'notes' => ['required', 'string', 'min:5', 'max:2000'],
            'related_order_id' => [
                'nullable', 'integer', 'exists:orders,order_id',
                Rule::notIn([(string) $order->order_id]),
            ],
        ]);

        OrderReviewFlag::create([
            'order_id' => $order->order_id,
            'related_order_id' => $validated['related_order_id'] ?? null,
            'created_by' => $request->user()?->getAuthIdentifier(),
            'reason' => $validated['reason'],
            'notes' => trim($validated['notes']),
            'status' => 'open',
        ]);

        return redirect()->back()->with('success', 'Order #' . $order->order_id . ' was marked for review. The original sale was not changed.');
    }

    /** Resolve an audit marker while keeping its history available on the order. */
    public function resolveReviewFlag(Request $request, $orderId, $flagId)
    {
        $order = Order::findOrFail($orderId);
        $flag = OrderReviewFlag::query()
            ->where('order_id', $order->order_id)
            ->where('flag_id', $flagId)
            ->firstOrFail();

        if ($flag->status === 'open') {
            $flag->update([
                'status' => 'resolved',
                'resolved_by' => $request->user()?->getAuthIdentifier(),
                'resolved_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Review marker #' . $flag->flag_id . ' was resolved. Its audit note remains attached to the order.');
    }

    // ============================================
    // 2. បង្ហាញ Form សម្រាប់បង្កើតការកម្ម៉ង់ថ្មី
    // ============================================
    public function create()
    {
        $customers      = Costumer::all();
        $cashiers       = User::all();
        $orderTypes     = OrderType::all();
        $paymentMethods = PaymentMethod::all();

        return view('admin.older.create', compact('customers', 'cashiers', 'orderTypes', 'paymentMethods'));
    }

    // ============================================
    // 3. រក្សាទុកទិន្នន័យការកម្ម៉ង់ថ្មីចូល Database
    // ============================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customer,customer_id',
            'order_type_id' => 'required|exists:order_type,order_type_id',
            'payment_method_id' => 'nullable|exists:payment_method,payment_method_id',
            'total_amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $discount = (float) ($validated['discount'] ?? 0);
        $totalAmount = (float) $validated['total_amount'];
        $validated['cashier_id'] = auth()->id();
        $validated['discount'] = $discount;
        $validated['final_price'] = round($totalAmount * (1 - ($discount / 100)), 2);
        $validated['is_paid'] = false;
        $validated['payment_status'] = 'Unpaid';
        $validated['order_status'] = 'pending';
        $validated['order_source'] = 'pos';

        Order::create($validated);

        return redirect()->route('admin.orders.index')->with('success', 'Order created successfully.');
    }

    // ============================================
    // 4. បង្ហាញ Form កែប្រែទិន្នន័យការកម្ម៉ង់
    // ============================================
    public function edit($id)
    {
        $order = Order::withCount(['orderItems', 'payments'])->findOrFail($id);
        if ($order->is_paid || $order->payments_count > 0 || $order->order_items_count > 0 || $order->order_status === 'cancelled') {
            return redirect()->route('admin.orders.index')->with('error', 'Orders with items, payment attempts, or a final status are locked. Use the order workflow or a refund/adjustment process.');
        }
        $customers      = Costumer::all();
        $cashiers       = User::all();
        $orderTypes     = OrderType::all();
        $paymentMethods = PaymentMethod::all();

        return view('admin.older.edit', compact('order', 'customers', 'cashiers', 'orderTypes', 'paymentMethods'));
    }

    // ============================================
    // 5. ធ្វើបច្ចុប្បន្នភាពទិន្នន័យថ្មីទៅក្នុង Database
    // ============================================
    public function update(Request $request, $id)
    {
        $order = Order::withCount(['orderItems', 'payments'])->findOrFail($id);
        if ($order->is_paid || $order->payments_count > 0 || $order->order_items_count > 0 || $order->order_status === 'cancelled') {
            return redirect()->route('admin.orders.index')->with('error', 'This order is locked because it has items, payment activity, or a final status.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customer,customer_id',
            'order_type_id' => 'required|exists:order_type,order_type_id',
            'payment_method_id' => 'nullable|exists:payment_method,payment_method_id',
            'total_amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        $discount = (float) ($validated['discount'] ?? 0);
        $totalAmount = (float) $validated['total_amount'];
        $validated['discount'] = $discount;
        $validated['final_price'] = round($totalAmount * (1 - ($discount / 100)), 2);
        $validated['is_paid'] = false;
        $validated['payment_status'] = 'Unpaid';

        $order->update($validated);

        return redirect()->route('admin.orders.index')->with('success', 'Order updated successfully.');
    }

    // ============================================
    // 6. លុបការកម្ម៉ង់ចោល
    // ============================================
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $hasLinkedRecords = $order->orderItems()->exists()
            || $order->delivery()->exists()
            || $order->review()->exists()
            || $order->payments()->exists()
            || (bool) $order->is_paid
            || \App\Models\CashTransaction::where('order_id', $order->order_id)->exists();

        if ($hasLinkedRecords) {
            return back()->with('error', 'This order has linked items, delivery, review, or finance records. Remove those records first to preserve the audit trail.');
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }
}