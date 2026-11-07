<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Costumer;
use App\Models\User;
use App\Models\OrderType;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

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
            'order_status' => ['nullable', 'in:pending,confirmed,preparing,ready,completed,cancelled'],
            'source' => ['nullable', 'in:pos,website'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $query = Order::query()
            ->with(['customer', 'cashier', 'orderType', 'paymentMethod', 'payments'])
            ->withCount(['orderItems', 'payments', 'delivery', 'review', 'cashTransactions']);

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
        if (! empty($validated['start_date'])) {
            $query->whereDate('order_date', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('order_date', '<=', $validated['end_date']);
        }

        $orders = $query->orderByDesc('order_date')
            ->orderByDesc('order_id')
            ->paginate(10)
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

        return view('admin.older', compact('orders', 'summary'));
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