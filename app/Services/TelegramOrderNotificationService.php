<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\TelegramOrderNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramOrderNotificationService
{
    public function __construct(
        private readonly TelegramBotService $telegram,
        private readonly OrderFulfillmentService $fulfillment,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->telegram->isConfigured()
            && filled(config('services.telegram.webhook_secret'))
            && count(config('services.telegram.admin_ids', [])) > 0;
    }

    /**
     * Notify paid website orders and POS home-delivery orders. POS Cash on
     * Delivery is intentionally announced before payment; staff collect cash
     * when they mark the delivery completed.
     */
    public function notifyPaidWebsiteOrder(Order $order): bool
    {
        $order->loadMissing(['orderType', 'paymentMethod']);
        if (! $this->isEligibleOrder($order)) {
            return false;
        }

        if (! $this->isConfigured()) {
            Log::notice('Telegram order notification skipped because Telegram configuration is incomplete.', [
                'order_id' => $order->order_id,
            ]);
            return false;
        }

        $order = Order::query()
            ->with(['customer', 'orderType', 'delivery', 'orderItems.product', 'payments', 'paymentMethod'])
            ->find($order->order_id);

        if (! $order || ! $this->isEligibleOrder($order)) {
            return false;
        }

        $chatId = (string) config('services.telegram.group_chat_id');

        try {
            TelegramOrderNotification::query()->firstOrCreate(
                ['order_id' => $order->order_id],
                ['chat_id' => $chatId, 'status' => 'pending', 'last_status' => $order->order_status]
            );
        } catch (QueryException $exception) {
            // A concurrent payment callback may have inserted the unique order row first.
            if (! TelegramOrderNotification::query()->where('order_id', $order->order_id)->exists()) {
                throw $exception;
            }
        }

        $notification = DB::transaction(function () use ($order, $chatId) {
            $row = TelegramOrderNotification::query()
                ->where('order_id', $order->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($row->status === 'sent' && $row->message_id) {
                return null;
            }

            if ($row->status === 'sending' && $row->updated_at && $row->updated_at->gt(now()->subMinutes(2))) {
                return null;
            }

            if ($row->status === 'failed' && $row->updated_at && $row->updated_at->gt(now()->subMinutes(2))) {
                return null;
            }

            $row->update([
                'chat_id' => $chatId,
                'status' => 'sending',
                'last_error' => null,
                'last_status' => $order->order_status,
            ]);

            return $row->fresh();
        }, 3);

        if (! $notification) {
            return false;
        }

        try {
            $message = $this->telegram->sendMessage(
                $chatId,
                $this->messageText($order),
                $this->replyMarkup($order, $chatId)
            );

            $messageId = (int) ($message['message_id'] ?? 0);
            if ($messageId < 1) {
                throw new \RuntimeException('Telegram returned a message without a valid message_id.');
            }

            $notification->update([
                'message_id' => $messageId,
                'status' => 'sent',
                'sent_at' => now(),
                'last_status' => $order->order_status,
                'last_error' => null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->order_id,
                'event_type' => 'telegram_notified',
                'from_status' => $order->order_status,
                'to_status' => $order->order_status,
                'actor_type' => 'system',
                'actor_label' => 'Telegram Bot',
                'note' => 'Order notification was sent to the administrator group.',
                'created_at' => now(),
            ]);

            return true;
        } catch (Throwable $exception) {
            $notification->update([
                'status' => 'failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 4000),
            ]);

            Log::warning('Telegram could not send an order notification.', [
                'order_id' => $order->order_id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function isEligibleOrder(Order $order): bool
    {
        $source = strtolower((string) ($order->order_source ?? ''));
        if ($source === 'website') {
            return (bool) $order->is_paid;
        }

        if ($source !== 'pos' || ! $this->fulfillment->isDeliveryOrder($order)) {
            return false;
        }

        $paymentMethod = strtolower(trim((string) $order->paymentMethod?->payment_name));
        $isCashOnDelivery = in_array($paymentMethod, ['cash on delivery', 'cash-on-delivery', 'cod'], true);

        return (bool) $order->is_paid || $isCashOnDelivery;
    }

    public function refreshOrderMessage(Order $order): void
    {
        $notification = TelegramOrderNotification::query()
            ->where('order_id', $order->order_id)
            ->where('status', 'sent')
            ->whereNotNull('message_id')
            ->first();

        if (! $notification) {
            return;
        }

        $order->loadMissing(['customer', 'orderType', 'delivery', 'orderItems.product', 'paymentMethod']);

        try {
            $this->telegram->editMessageText(
                (string) $notification->chat_id,
                (int) $notification->message_id,
                $this->messageText($order),
                $this->replyMarkup($order, (string) $notification->chat_id)
            );

            $notification->update([
                'last_status' => $order->order_status,
                'last_error' => null,
            ]);
        } catch (Throwable $exception) {
            // Telegram returns this response when the dashboard submits a save
            // that does not change the rendered notification. That is not a failure.
            if (str_contains(strtolower($exception->getMessage()), 'message is not modified')) {
                $notification->update([
                    'last_status' => $order->order_status,
                    'last_error' => null,
                ]);
                return;
            }

            $notification->update(['last_error' => mb_substr($exception->getMessage(), 0, 4000)]);
            Log::warning('Telegram order message could not be refreshed after a status change.', [
                'order_id' => $order->order_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function validCallbackSignature(int $orderId, string $targetStatus, string $chatId, string $provided): bool
    {
        $expected = $this->callbackSignature($orderId, $targetStatus, $chatId);

        return hash_equals($expected, $provided);
    }

    private function callbackSignature(int $orderId, string $targetStatus, string $chatId): string
    {
        return substr(hash_hmac(
            'sha256',
            $orderId.'|'.$targetStatus.'|'.$chatId,
            (string) config('app.key')
        ), 0, 24);
    }

    private function replyMarkup(Order $order, string $chatId): array
    {
        $nextStatus = $this->fulfillment->nextStatus($order);
        if (! $nextStatus) {
            return ['inline_keyboard' => []];
        }

        $isDelivery = $this->fulfillment->isDeliveryOrder($order);
        $paymentMethodName = strtolower(trim((string) $order->paymentMethod?->payment_name));
        $isCod = in_array($paymentMethodName, ['cash on delivery', 'cash-on-delivery', 'cod'], true);
        $labels = [
            'confirmed' => '✅ Confirm order',
            'preparing' => '👩‍🍳 Start preparing',
            'ready' => $isDelivery ? '📦 Ready for dispatch' : '📦 Ready for pickup',
            'out_for_delivery' => '🚚 Out for delivery',
            'completed' => $isCod ? '✅ Delivered & COD collected' : ($isDelivery ? '✅ Mark delivered' : '✅ Mark collected'),
        ];

        $callbackData = implode('|', [
            'oc',
            (string) $order->order_id,
            $nextStatus,
            $this->callbackSignature((int) $order->order_id, $nextStatus, $chatId),
        ]);

        return [
            'inline_keyboard' => [[[
                'text' => $labels[$nextStatus] ?? 'Advance order',
                'callback_data' => $callbackData,
            ]]],
        ];
    }

    private function oneLine(?string $value, string $fallback = '—'): string
    {
        $clean = preg_replace('/\s+/u', ' ', trim((string) $value));
        return is_string($clean) && $clean !== '' ? $clean : $fallback;
    }

    private function messageText(Order $order): string
    {
        $isDelivery = $this->fulfillment->isDeliveryOrder($order);
        $status = ucfirst(str_replace('_', ' ', (string) $order->order_status));
        $source = strtolower((string) ($order->order_source ?? 'website'));
        $paymentMethodName = strtolower(trim((string) $order->paymentMethod?->payment_name));
        $isCod = in_array($paymentMethodName, ['cash on delivery', 'cash-on-delivery', 'cod'], true);
        $name = $this->oneLine($order->customer?->name, 'Customer not recorded');
        $phone = $this->oneLine($order->customer?->phone, 'Not provided');
        $type = $isDelivery ? 'Home delivery' : 'Store pickup';
        $heading = $source === 'pos' ? 'NEW POS DELIVERY ORDER' : 'NEW PAID WEBSITE ORDER';
        $text = "☕ {$heading} #{$order->order_id}\n";
        if ($isCod && $order->is_paid) {
            $collectedAmount = (float) ($order->cod_collected_amount ?? $order->final_price);
            $text .= 'Payment: PAID · Cash on Delivery collected $'.number_format($collectedAmount, 2)." at handoff\n";
            if ($order->cod_collected_at) {
                $text .= 'Collected at: '.$order->cod_collected_at->format('d M Y H:i')."\n";
            }
        } elseif ($isCod) {
            $text .= 'Payment: CASH ON DELIVERY · Collect $'.number_format((float) $order->final_price, 2)." at handoff\n";
        } elseif ($paymentMethodName === 'cash') {
            $text .= "Payment: PAID · Cash collected by staff\n";
        } elseif ($paymentMethodName === 'khqr' || $source === 'website') {
            $text .= "Payment: VERIFIED · ABA KHQR\n";
        } else {
            $text .= 'Payment: '.strtoupper($this->oneLine($order->paymentMethod?->payment_name, 'PAID'))."\n";
        }
        $text .= "Customer: {$name}\nPhone: {$phone}\nFulfilment: {$type}\n";
        if ($source === 'pos' && filled($order->order_channel ?? null)) {
            $channels = [
                'walk_in' => 'Walk-in / In-store', 'facebook' => 'Facebook page / Messenger',
                'instagram' => 'Instagram', 'telegram' => 'Telegram message',
                'phone' => 'Phone call', 'referral' => 'Friend / Referral', 'other' => 'Other',
            ];
            $text .= 'Received via: '.($channels[$order->order_channel] ?? ucfirst(str_replace('_', ' ', $order->order_channel)))."\n";
        }

        if ($isDelivery) {
            $text .= 'Address: '.$this->oneLine($order->delivery?->address, 'Address missing')."\n";
            $text .= 'Delivery fee: $'.number_format((float) ($order->delivery?->cost ?? 0), 2)."\n";
        }

        $paidPayment = $order->payments->firstWhere('status', 'paid') ?? $order->payments->sortByDesc('id')->first();
        if ($paidPayment?->tran_id) {
            $text .= 'Payment ref: '.$this->oneLine($paidPayment->tran_id)."\n";
        }

        $text .= "\nITEMS\n";
        foreach ($order->orderItems->take(20) as $item) {
            $productName = $this->oneLine($item->product?->product_name, 'Product');
            $text .= '• '.(int) $item->quantity.' × '.$productName.' — $'.number_format((float) $item->price * (int) $item->quantity, 2)."\n";
        }

        if ($order->orderItems->count() > 20) {
            $text .= '• Plus '.($order->orderItems->count() - 20)." more item(s)\n";
        }

        $text .= "\nTotal: $".number_format((float) $order->final_price, 2)."\n";
        if ($order->customer_note) {
            $text .= 'Customer note: '.$this->oneLine($order->customer_note)."\n";
        }
        $text .= "Current status: {$status}\n";
        $text .= $this->fulfillment->nextStatus($order)
            ? "Use the action button to advance exactly one step."
            : 'This order has reached a final fulfillment state.';

        // Telegram sendMessage limit is 4096 characters. This content is plain text.
        return mb_substr($text, 0, 3900);
    }
}
