<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $deliveries = Delivery::with(['order.customer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('start_date'), fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->whereDate('order_date', '>=', $request->input('start_date'))))
            ->when($request->filled('end_date'), fn ($query) => $query->whereHas('order', fn ($orderQuery) => $orderQuery->whereDate('order_date', '<=', $request->input('end_date'))))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($term) {
                    $nested->where('address', 'like', "%{$term}%")
                        ->orWhere('order_id', 'like', "%{$term}%")
                        ->orWhereHas('order.customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($request->input('sort_by') === 'delivery_id', fn ($query) => $query->orderBy('delivery_id', in_array($request->input('sort_dir'), ['asc', 'desc'], true) ? $request->input('sort_dir') : 'desc'), function ($query) use ($request) {
                $direction = in_array($request->input('sort_dir'), ['asc', 'desc'], true) ? $request->input('sort_dir') : 'desc';
                $query->orderBy(\App\Models\Order::query()->select('order_date')->whereColumn('orders.order_id', 'delivery.order_id'), $direction)
                    ->orderByDesc('delivery_id');
            })
            ->paginate(15)
            ->withQueryString();

        return view('admin.delivery', compact('deliveries'));
    }

    public function create()
    {
        // The legacy orders table has payment fields, not an order.status column.
        $orders = Order::where('is_paid', true)
            ->whereDoesntHave('delivery')
            ->orderByDesc('order_id')
            ->get();

        return view('admin.delivery.create', compact('orders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,order_id|unique:delivery,order_id',
            'address' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
        ]);

        Delivery::create([
            ...$validated,
            'status' => 'pending',
        ]);

        return redirect()->route('delivery.index')->with('success', 'Delivery created.');
    }

    public function edit(Delivery $delivery)
    {
        return view('admin.delivery.edit', compact('delivery'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'status' => 'required|in:pending,out_for_delivery,delivered,cancelled',
        ]);

        $delivery->update($validated);

        return redirect()->route('delivery.index')->with('success', 'Delivery updated.');
    }

    public function destroy(Delivery $delivery)
    {
        $delivery->delete();

        return redirect()->route('delivery.index')->with('success', 'Delivery removed.');
    }
}
