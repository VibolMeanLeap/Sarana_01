<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    private const STATUS_LABELS = [
        'pending' => 'Awaiting confirmation',
        'confirmed' => 'Order confirmed',
        'preparing' => 'Preparing',
        'ready' => 'Ready for dispatch',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    private const NEXT_DELIVERY_STATUS = [
        'pending' => 'confirmed',
        'confirmed' => 'preparing',
        'preparing' => 'ready',
        'ready' => 'out_for_delivery',
        'out_for_delivery' => 'delivered',
    ];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,confirmed,preparing,ready,out_for_delivery,delivered,cancelled'],
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

        $summary = [
            'count' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'preparing' => (clone $query)->whereIn('status', ['preparing', 'ready'])->count(),
            'on_way' => (clone $query)->where('status', 'out_for_delivery')->count(),
            'delivered' => (clone $query)->where('status', 'delivered')->count(),
            'fees' => (float) (clone $query)->sum('cost'),
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

            $status = match (strtolower((string) $order->order_status)) {
                'confirmed' => 'confirmed',
                'preparing' => 'preparing',
                'ready' => 'ready',
                'out_for_delivery' => 'out_for_delivery',
                'completed' => 'delivered',
                default => 'pending',
            };

            Delivery::create($validated + ['status' => $status]);

            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'delivery_created',
                'from_status' => null,
                'to_status' => $status,
                'actor_type' => 'admin',
                'actor_id' => auth()->id(),
                'actor_label' => auth()->user()?->name,
                'note' => 'Delivery record created from the administrator dashboard.',
                'created_at' => now(),
            ]);
        }, 3);

        $updatedOrder = Order::query()->with(['customer', 'orderType', 'delivery', 'orderItems.product'])->find($validated['order_id']);
        if ($updatedOrder) {
            app(\App\Services\TelegramOrderNotificationService::class)->refreshOrderMessage($updatedOrder);
        }

        return redirect()->route('delivery.index')->with('success', 'Delivery created for the selected paid delivery order.');
    }

    public function edit(Delivery $delivery)
    {
        $delivery->load(['order.customer', 'order.orderType']);
        $current = strtolower((string) $delivery->status);
        $allowedStatuses = [$current => self::STATUS_LABELS[$current] ?? ucfirst(str_replace('_', ' ', $current))];
        if (isset(self::NEXT_DELIVERY_STATUS[$current])) {
            $next = self::NEXT_DELIVERY_STATUS[$current];
            $allowedStatuses[$next] = self::STATUS_LABELS[$next];
        }
        if (! in_array($current, ['delivered', 'cancelled'], true)) {
            $allowedStatuses['cancelled'] = self::STATUS_LABELS['cancelled'];
        }

        return view('admin.delivery.edit', compact('delivery', 'allowedStatuses'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', 'in:pending,confirmed,preparing,ready,out_for_delivery,delivered,cancelled'],
        ]);

        DB::transaction(function () use ($delivery, $validated) {
            // Always lock the order first, then the delivery row. Telegram and the
            // shared fulfillment service use the same lock order to reduce deadlocks.
            $order = Order::query()->with('orderType')->where('order_id', $delivery->order_id)->lockForUpdate()->firstOrFail();
            $lockedDelivery = Delivery::query()->where('delivery_id', $delivery->delivery_id)->lockForUpdate()->firstOrFail();
            $order->setRelation('delivery', $lockedDelivery);
            $current = strtolower((string) $lockedDelivery->status);
            $target = $validated['status'];

            $lockedDelivery->address = $validated['address'];
            $lockedDelivery->cost = $validated['cost'];

            if ($target !== $current) {
                $next = self::NEXT_DELIVERY_STATUS[$current] ?? null;
                $isCancellation = $target === 'cancelled';
                if (! $isCancellation && $target !== $next) {
                    throw ValidationException::withMessages(['status' => 'Delivery must move forward one step at a time.']);
                }
                if ($isCancellation && in_array($current, ['delivered', 'cancelled'], true)) {
                    throw ValidationException::withMessages(['status' => 'A delivered or cancelled order is final and cannot be changed.']);
                }

                if ($isCancellation) {
                    $previousOrderStatus = (string) $order->order_status;
                    $order->order_status = 'cancelled';
                    $order->save();
                    $lockedDelivery->status = 'cancelled';
                    $lockedDelivery->save();

                    OrderStatusHistory::create([
                        'order_id' => $order->order_id,
                        'event_type' => 'status_changed',
                        'from_status' => $previousOrderStatus,
                        'to_status' => 'cancelled',
                        'actor_type' => 'admin',
                        'actor_id' => auth()->id(),
                        'actor_label' => auth()->user()?->name,
                        'note' => 'Delivery cancelled by an administrator. Payment remains recorded; process any refund or adjustment separately.',
                        'created_at' => now(),
                    ]);
                } else {
                    $orderTarget = $target === 'delivered' ? 'completed' : $target;
                    app(OrderFulfillmentService::class)->transitionLocked($order, $orderTarget, [
                        'actor_type' => 'admin',
                        'actor_id' => auth()->id(),
                        'actor_label' => auth()->user()?->name,
                        'note' => 'Delivery status updated from the administrator delivery register.',
                    ]);
                    // The fulfillment service owns the status change; keep updated address and fee.
                    $lockedDelivery->refresh();
                    $lockedDelivery->address = $validated['address'];
                    $lockedDelivery->cost = $validated['cost'];
                    $lockedDelivery->save();
                }
            } else {
                $lockedDelivery->save();
            }
        }, 3);

        $updatedOrder = Order::query()->with(['customer', 'orderType', 'delivery', 'orderItems.product'])->find($delivery->order_id);
        if ($updatedOrder) {
            app(\App\Services\TelegramOrderNotificationService::class)->refreshOrderMessage($updatedOrder);
        }

        return redirect()->route('delivery.index')->with('success', 'Delivery details and fulfillment status updated.');
    }

    /** Cancel the delivery and order while preserving payment and operational history. */
    public function destroy(Delivery $delivery)
    {
        DB::transaction(function () use ($delivery) {
            $order = Order::query()->where('order_id', $delivery->order_id)->lockForUpdate()->firstOrFail();
            $lockedDelivery = Delivery::query()->where('delivery_id', $delivery->delivery_id)->lockForUpdate()->firstOrFail();
            $current = strtolower((string) $lockedDelivery->status);

            if (in_array($current, ['delivered', 'cancelled'], true) || $order->order_status === 'completed') {
                throw ValidationException::withMessages(['delivery' => 'A completed or already-cancelled delivery is locked to preserve its history.']);
            }

            $previousStatus = (string) $order->order_status;
            $order->order_status = 'cancelled';
            $order->save();
            $lockedDelivery->status = 'cancelled';
            $lockedDelivery->save();

            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'status_changed',
                'from_status' => $previousStatus,
                'to_status' => 'cancelled',
                'actor_type' => 'admin',
                'actor_id' => auth()->id(),
                'actor_label' => auth()->user()?->name,
                'note' => 'Delivery cancelled by an administrator. Payment remains recorded; process any refund or adjustment separately.',
                'created_at' => now(),
            ]);
        }, 3);

        $updatedOrder = Order::query()->with(['customer', 'orderType', 'delivery', 'orderItems.product'])->find($delivery->order_id);
        if ($updatedOrder) {
            app(\App\Services\TelegramOrderNotificationService::class)->refreshOrderMessage($updatedOrder);
        }

        return back()->with('success', 'Delivery and order cancelled. Payment and delivery history have been preserved.');
    }
}
