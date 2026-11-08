<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockLog;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class OrderWorkflowController extends Controller
{
    public function updateByCashier(Request $request, int $orderId)
    {
        return $this->updateStatus($request, $orderId, false);
    }

    public function updateByAdmin(Request $request, int $orderId)
    {
        return $this->updateStatus($request, $orderId, true);
    }

    private function updateStatus(Request $request, int $orderId, bool $isAdmin)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:confirmed,preparing,ready,out_for_delivery,completed,cancelled',
        ]);

        $targetStatus = $validated['order_status'];

        DB::transaction(function () use ($orderId, $targetStatus, $isAdmin): void {
            $order = Order::query()
                ->with(['orderType', 'delivery', 'paymentMethod'])
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $isAdmin) {
                // Website orders are controlled by authorised administrators only.
                // Cashiers can update only their own POS orders.
                $isOwnPosOrder = ($order->order_source ?? 'pos') === 'pos'
                    && (int) $order->cashier_id === (int) auth()->id();
                abort_unless($isOwnPosOrder, 403);
            }

            $currentStatus = strtolower((string) ($order->order_status ?: 'pending'));
            if (in_array($currentStatus, ['cancelled', 'completed'], true)) {
                throw ValidationException::withMessages([
                    'order_status' => 'This order is already in a final state and cannot be changed.',
                ]);
            }

            $actor = [
                'actor_type' => $isAdmin ? 'admin' : 'cashier',
                'actor_id' => auth()->id(),
                'actor_label' => auth()->user()?->name,
                'note' => 'Fulfillment status updated from the '.($isAdmin ? 'administrator' : 'cashier').' dashboard.',
            ];

            if ($targetStatus === 'cancelled') {
                if ((bool) $order->is_paid) {
                    throw ValidationException::withMessages([
                        'order_status' => 'A paid order cannot be cancelled here. Record the refund or adjustment first.',
                    ]);
                }

                $items = OrderItem::query()
                    ->where('order_id', $order->order_id)
                    ->get(['product_id', 'quantity']);

                $productIds = $items->pluck('product_id')->filter()->unique()->sort()->values();
                $products = Product::query()
                    ->whereIn('product_id', $productIds)
                    ->orderBy('product_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('product_id');

                $quantityByProduct = $items->groupBy('product_id')->map(
                    fn ($rows) => (int) $rows->sum('quantity')
                );

                foreach ($quantityByProduct as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    if (! $product) {
                        throw new RuntimeException("Cannot restore stock: product #{$productId} no longer exists.");
                    }

                    if (! $product->isStockTracked()) {
                        continue;
                    }

                    $product->qty = (int) $product->qty + $quantity;
                    $product->save();

                    StockLog::create([
                        'product_id' => $product->product_id,
                        'user_id' => auth()->id(),
                        'type' => 'IN',
                        'quantity' => $quantity,
                        'reason' => 'Order cancellation',
                        'note' => 'Stock returned for cancelled order #'.$order->order_id.'.',
                    ]);
                }

                Payment::query()
                    ->where('order_id', $order->order_id)
                    ->whereIn('status', ['pending', 'failed', 'declined', 'expired'])
                    ->update(['status' => 'cancelled']);

                $order->order_status = 'cancelled';
                $order->save();
                if ($order->delivery) {
                    $order->delivery->status = 'cancelled';
                    $order->delivery->save();
                }

                OrderStatusHistory::create([
                    'order_id' => $order->order_id,
                    'event_type' => 'status_changed',
                    'from_status' => $currentStatus,
                    'to_status' => 'cancelled',
                    'actor_type' => $actor['actor_type'],
                    'actor_id' => $actor['actor_id'],
                    'actor_label' => $actor['actor_label'],
                    'note' => 'Unpaid order cancelled; reserved stock was returned.',
                    'created_at' => now(),
                ]);

                return;
            }

            $paymentMethodName = strtolower(trim((string) $order->paymentMethod?->payment_name));
            $isCashOnDelivery = in_array($paymentMethodName, ['cash on delivery', 'cash-on-delivery', 'cod'], true);
            if (! $order->is_paid && ! $isCashOnDelivery) {
                throw ValidationException::withMessages([
                    'order_status' => 'Payment must be verified before an order can be processed.',
                ]);
            }

            // Website and delivery orders use the shared state machine so orders,
            // delivery records, Telegram messages and the customer timeline stay aligned.
            if (($order->order_source ?? '') === 'website' || $order->delivery || app(OrderFulfillmentService::class)->isDeliveryOrder($order)) {
                app(OrderFulfillmentService::class)->transitionLocked($order, $targetStatus, $actor);
                return;
            }

            // Preserve the legacy POS workflow for dine-in/takeaway orders without a delivery record.
            $sequence = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
            $currentIndex = array_search($currentStatus, $sequence, true);
            $targetIndex = array_search($targetStatus, $sequence, true);

            if ($currentIndex === false || $targetIndex === false || $targetIndex !== $currentIndex + 1) {
                throw ValidationException::withMessages([
                    'order_status' => 'Invalid status transition. Move the order forward one step at a time.',
                ]);
            }

            $order->order_status = $targetStatus;
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'status_changed',
                'from_status' => $currentStatus,
                'to_status' => $targetStatus,
                'actor_type' => $actor['actor_type'],
                'actor_id' => $actor['actor_id'],
                'actor_label' => $actor['actor_label'],
                'note' => $actor['note'],
                'created_at' => now(),
            ]);
        }, 3);

        $updatedOrder = Order::query()
            ->with(['customer', 'orderType', 'delivery', 'orderItems.product', 'paymentMethod'])
            ->find($orderId);
        if ($updatedOrder && (
            ($updatedOrder->order_source ?? '') === 'website'
            || $updatedOrder->delivery
            || app(OrderFulfillmentService::class)->isDeliveryOrder($updatedOrder)
        )) {
            app(\App\Services\TelegramOrderNotificationService::class)->refreshOrderMessage($updatedOrder);
        }

        $destination = $isAdmin ? 'admin.orders.index' : 'cashier.orders';
        return redirect()->route($destination)->with('success', 'Order #'.$orderId.' status updated to '.str_replace('_', ' ', $targetStatus).'.');
    }
}
