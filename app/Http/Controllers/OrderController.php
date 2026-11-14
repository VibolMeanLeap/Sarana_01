<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Costumer;
use App\Models\User;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use App\Models\OrderReviewFlag;
use App\Services\AdminActionCountService;
use Carbon\Carbon;
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
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'period' => ['nullable', 'in:today,week,month'],
            'sort_by' => ['nullable', 'in:order_date,order_id,final_price'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);

        $query = Order::query()
            ->with(['customer', 'cashier', 'orderType', 'paymentMethod', 'payments', 'delivery', 'orderItems.product', 'statusHistory', 'reviewFlags.relatedOrder'])
            ->withCount(['orderItems', 'payments', 'delivery', 'review', 'cashTransactions', 'openReviewFlags']);

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
        $period = $validated['period'] ?? null;
        if ($period) {
            $periodStart = match ($period) {
                'today' => today()->toDateString(),
                'week' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'month' => now()->startOfMonth()->toDateString(),
            };
            $periodEnd = today()->toDateString();
            $query->whereDate('order_date', '>=', $periodStart)
                ->whereDate('order_date', '<=', $periodEnd);
        } else {
            if (! empty($validated['start_date'])) {
                $query->whereDate('order_date', '>=', $validated['start_date']);
            }
            if (! empty($validated['end_date'])) {
                $query->whereDate('order_date', '<=', $validated['end_date']);
            }
        }

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

        return view('admin.older', compact('orders', 'summary', 'filteredOrderCount', 'filteredFinalTotal', 'perPage'));
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