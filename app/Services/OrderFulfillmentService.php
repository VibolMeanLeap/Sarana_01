<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderFulfillmentService
{
    private const DELIVERY_SEQUENCE = [
        'pending',
        'confirmed',
        'preparing',
        'ready',
        'out_for_delivery',
        'completed',
    ];

    private const PICKUP_SEQUENCE = [
        'pending',
        'confirmed',
        'preparing',
        'ready',
        'completed',
    ];

    private const DELIVERY_STATUS_MAP = [
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'preparing' => 'preparing',
        'ready' => 'ready',
        'out_for_delivery' => 'out_for_delivery',
        'completed' => 'delivered',
    ];

    public function isDeliveryOrder(Order $order): bool
    {
        return strtolower(trim((string) $order->orderType?->order_name)) === 'delivery';
    }

    public function nextStatus(Order $order): ?string
    {
        if (! $order->is_paid && ! $this->isCashOnDelivery($order)) {
            return null;
        }

        $current = strtolower((string) ($order->order_status ?: 'pending'));
        $sequence = $this->isDeliveryOrder($order)
            ? self::DELIVERY_SEQUENCE
            : self::PICKUP_SEQUENCE;

        $index = array_search($current, $sequence, true);

        return $index === false ? null : ($sequence[$index + 1] ?? null);
    }

    public function transition(int $orderId, string $targetStatus, array $actor = []): Order
    {
        return DB::transaction(function () use ($orderId, $targetStatus, $actor): Order {
            $order = Order::query()
                ->with(['orderType', 'delivery', 'paymentMethod'])
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->transitionLocked($order, $targetStatus, $actor);

            return $order->fresh(['orderType', 'delivery', 'customer', 'orderItems.product']);
        }, 3);
    }

    /**
     * Transition an order already locked by the caller's transaction.
     * Order and delivery are persisted together so customer tracking cannot drift.
     */
    public function transitionLocked(Order $order, string $targetStatus, array $actor = []): void
    {
        $order->loadMissing(['orderType', 'delivery']);

        $isCashOnDelivery = $this->isCashOnDelivery($order);
        if (! $order->is_paid && ! $isCashOnDelivery) {
            throw ValidationException::withMessages([
                'order_status' => 'Payment must be verified before fulfillment can advance.',
            ]);
        }

        $currentStatus = strtolower((string) ($order->order_status ?: 'pending'));
        $nextStatus = $this->nextStatus($order);

        if ($nextStatus === null || $targetStatus !== $nextStatus) {
            throw ValidationException::withMessages([
                'order_status' => 'This order can only move to its next fulfillment step.',
            ]);
        }

        if ($this->isDeliveryOrder($order) && ! $order->delivery) {
            throw ValidationException::withMessages([
                'order_status' => 'Create the delivery record before advancing this delivery order.',
            ]);
        }

        $order->order_status = $targetStatus;
        if ($isCashOnDelivery && $targetStatus === 'completed') {
            // The final delivery confirmation is also the staff acknowledgement
            // that cash was collected from the customer. Only then count revenue.
            $order->is_paid = true;
            $order->payment_status = 'Paid';
        }
        $order->save();

        if ($order->delivery) {
            $deliveryStatus = self::DELIVERY_STATUS_MAP[$targetStatus] ?? null;
            if ($deliveryStatus !== null) {
                $order->delivery->status = $deliveryStatus;
                $order->delivery->save();
            }
        }

        OrderStatusHistory::create([
            'order_id' => $order->order_id,
            'event_type' => 'status_changed',
            'from_status' => $currentStatus,
            'to_status' => $targetStatus,
            'actor_type' => $actor['actor_type'] ?? 'system',
            'actor_id' => $actor['actor_id'] ?? null,
            'telegram_user_id' => $actor['telegram_user_id'] ?? null,
            'telegram_username' => $actor['telegram_username'] ?? null,
            'actor_label' => $actor['actor_label'] ?? null,
            'note' => $isCashOnDelivery && $targetStatus === 'completed'
                ? trim((string) ($actor['note'] ?? '').' Cash on delivery was collected at handoff.')
                : ($actor['note'] ?? null),
            'created_at' => now(),
        ]);
    }
    private function isCashOnDelivery(Order $order): bool
    {
        $order->loadMissing('paymentMethod');
        $method = strtolower(trim((string) $order->paymentMethod?->payment_name));

        return in_array($method, ['cash on delivery', 'cash-on-delivery', 'cod'], true);
    }

}
