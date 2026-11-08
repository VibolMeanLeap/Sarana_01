<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    private const STATUSES = ['pending', 'out_for_delivery', 'delivered', 'cancelled'];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,out_for_delivery,delivered,cancelled'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'in:10,15,25,50,100'],
        ]);

        $query = Delivery::with(['order.customer', 'order.orderType'])
            ->when(!empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->when(!empty($validated['start_date']), fn ($q) => $q->whereHas('order', fn ($order) => $order->whereDate('order_date', '>=', $validated['start_date'])))
            ->when(!empty($validated['end_date']), fn ($q) => $q->whereHas('order', fn ($order) => $order->whereDate('order_date', '<=', $validated['end_date'])))
            ->when(!empty($validated['search']), function ($q) use ($validated) {
                $term = trim($validated['search']);
                $q->where(function ($nested) use ($term) {
                    $nested->where('delivery_id', 'like', "%{$term}%")
                        ->orWhere('order_id', 'like', "%{$term}%")
                        ->orWhere('address', 'like', "%{$term}%")
                        ->orWhereHas('order.customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%"));
                });
            });

        $summaryQuery = clone $query;
        $summary = [
            'count' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'on_way' => (clone $summaryQuery)->where('status', 'out_for_delivery')->count(),
            'delivered' => (clone $summaryQuery)->where('status', 'delivered')->count(),
            'fees' => (float) (clone $summaryQuery)->sum('cost'),
        ];

        $direction = $validated['sort_dir'] ?? 'desc';
        $perPage = (int) ($validated['per_page'] ?? 15);
        $deliveries = $query
            ->orderBy(DB::table('orders')->select('order_date')->whereColumn('orders.order_id', 'delivery.order_id'), $direction)
            ->orderByDesc('delivery_id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.delivery', compact('deliveries', 'summary'));
    }

    public function create()
    {
        $orders = Order::with(['customer', 'orderType'])
            ->where('is_paid', true)
            ->where('order_status', '!=', 'cancelled')
            ->whereDoesntHave('delivery')
            ->whereHas('orderType', fn ($query) => $query->whereRaw('LOWER(order_name) = ?', ['delivery']))
            ->orderByDesc('order_date')
            ->get();

        return view('admin.delivery.create', compact('orders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,order_id', 'unique:delivery,order_id'],
            'address' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        DB::transaction(function () use ($validated) {
            $order = Order::with('orderType')->where('order_id', $validated['order_id'])->lockForUpdate()->firstOrFail();

            if (! $order->is_paid || strtolower((string) $order->orderType?->order_name) !== 'delivery' || $order->order_status === 'cancelled') {
                throw ValidationException::withMessages([
                    'order_id' => 'Choose a paid, non-cancelled order whose order type is Delivery.',
                ]);
            }

            if ($order->delivery()->exists()) {
                throw ValidationException::withMessages(['order_id' => 'A delivery record already exists for this order.']);
            }

            Delivery::create($validated + ['status' => 'pending']);
        });

        return redirect()->route('delivery.index')->with('success', 'Delivery created for the selected paid delivery order.');
    }

    public function edit(Delivery $delivery)
    {
        $delivery->load(['order.customer', 'order.orderType']);
        return view('admin.delivery.edit', compact('delivery'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', 'in:pending,out_for_delivery,delivered,cancelled'],
        ]);

        $current = strtolower((string) $delivery->status);
        $next = $validated['status'];
        if (in_array($current, ['delivered', 'cancelled'], true) && $next !== $current) {
            throw ValidationException::withMessages(['status' => 'Delivered and cancelled deliveries are final states and cannot be reopened.']);
        }
        $allowedTransitions = [
            'pending' => ['pending', 'out_for_delivery', 'cancelled'],
            'out_for_delivery' => ['out_for_delivery', 'delivered', 'cancelled'],
        ];
        if (isset($allowedTransitions[$current]) && ! in_array($next, $allowedTransitions[$current], true)) {
            throw ValidationException::withMessages(['status' => 'This delivery status transition is not allowed.']);
        }

        $delivery->update($validated);
        return redirect()->route('delivery.index')->with('success', 'Delivery details and fulfillment status updated.');
    }

    /** Cancel a delivery without deleting its operational history. */
    public function destroy(Delivery $delivery)
    {
        if (in_array(strtolower((string) $delivery->status), ['delivered', 'cancelled'], true)) {
            return back()->with('error', 'Delivered or already-cancelled delivery records are locked to preserve history.');
        }

        $delivery->update(['status' => 'cancelled']);
        return back()->with('success', 'Delivery cancelled. The delivery history has been preserved.');
    }
}
