<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderFulfillmentService
{
    private const DELIVERY_SEQUENCE = [
        'pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed',
    ];

    private const PICKUP_SEQUENCE = [
        'pending', 'confirmed', 'preparing', 'ready', 'completed',
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
     * COD is only settled here, at the final delivery handoff, after an explicit
     * confirmation from a staff member or the authorised Telegram admin action.
     */
    public function transitionLocked(Order $order, string $targetStatus, array $actor = []): void
    {
        $order->loadMissing(['orderType', 'delivery', 'paymentMethod']);

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

        $isCodCollection = $isCashOnDelivery && ! $order->is_paid && $targetStatus === 'completed';
        if ($isCodCollection) {
            $actorType = (string) ($actor['actor_type'] ?? 'system');
            // Telegram's authenticated, allow-listed "Delivered & COD collected" button
            // is itself the explicit acknowledgement; all dashboard users must tick it.
            $telegramConfirmed = $actorType === 'telegram_admin';
            if (! $telegramConfirmed && ! (bool) ($actor['cod_collected_confirmed'] ?? false)) {
                throw ValidationException::withMessages([
                    'cod_collected_confirmed' => 'Confirm that the driver received the cash from the customer before marking this order delivered.',
                ]);
            }

            $amountDue = round((float) ($order->final_price ?? $order->total_amount ?? 0), 2);
            $receivedAmount = $actor['cod_collected_amount'] ?? null;
            if ($telegramConfirmed && ($receivedAmount === null || $receivedAmount === '')) {
                $receivedAmount = $amountDue;
            }

            if (! is_numeric($receivedAmount) || round((float) $receivedAmount, 2) !== $amountDue) {
                throw ValidationException::withMessages([
                    'cod_collected_amount' => 'The cash received must match the exact order amount of $'.number_format($amountDue, 2).'. Record any difference separately before completing this order.',
                ]);
            }

            $collectionNote = trim((string) ($actor['cod_collection_note'] ?? ''));
            if ($collectionNote === '') {
                $collectionNote = $telegramConfirmed
                    ? 'Cash received at delivery handoff; confirmed by the authorised Telegram administrator.'
                    : 'Cash received from the customer at delivery handoff.';
            }

            $order->cod_collected_amount = number_format($amountDue, 2, '.', '');
            $order->cod_collected_at = now();
            $order->cod_collected_by = $actor['actor_id'] ?? null;
            $order->cod_collection_note = mb_substr($collectionNote, 0, 500);
            $order->is_paid = true;
            $order->payment_status = 'Paid';
            $order->save();

            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'payment_collected',
                'from_status' => $currentStatus,
                'to_status' => $currentStatus,
                'actor_type' => $actorType,
                'actor_id' => $actor['actor_id'] ?? null,
                'telegram_user_id' => $actor['telegram_user_id'] ?? null,
                'telegram_username' => $actor['telegram_username'] ?? null,
                'actor_label' => $actor['actor_label'] ?? null,
                'note' => 'Cash on Delivery collected: $'.number_format($amountDue, 2).'. '.$collectionNote,
                'created_at' => now(),
            ]);
        }

        $order->order_status = $targetStatus;
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
            'note' => $isCodCollection
                ? 'Delivery completed and COD payment collection recorded. '.trim((string) ($actor['note'] ?? ''))
                : ($actor['note'] ?? null),
            'created_at' => now(),
        ]);
    }

    public function isCashOnDelivery(Order $order): bool
    {
        $order->loadMissing('paymentMethod');
        $method = strtolower(trim((string) $order->paymentMethod?->payment_name));

        return in_array($method, ['cash on delivery', 'cash-on-delivery', 'cod'], true);
    }
}
