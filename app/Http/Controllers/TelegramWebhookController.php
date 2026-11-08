<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TelegramOrderNotification;
use App\Services\OrderFulfillmentService;
use App\Services\TelegramBotService;
use App\Services\TelegramOrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotService $telegram,
        OrderFulfillmentService $fulfillment,
        TelegramOrderNotificationService $notifications
    ): JsonResponse {
        $secret = (string) config('services.telegram.webhook_secret');
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || $providedSecret === '' || ! hash_equals($secret, $providedSecret)) {
            return response()->json(['ok' => false], 403);
        }

        $callback = $request->input('callback_query');
        if (! is_array($callback)) {
            return response()->json(['ok' => true]);
        }

        $callbackId = (string) ($callback['id'] ?? '');
        $from = is_array($callback['from'] ?? null) ? $callback['from'] : [];
        $telegramUserId = (string) ($from['id'] ?? '');
        $allowedAdminIds = array_map('strval', (array) config('services.telegram.admin_ids', []));

        if ($telegramUserId === '' || ($from['is_bot'] ?? false) || ! in_array($telegramUserId, $allowedAdminIds, true)) {
            $this->answerSafely($telegram, $callbackId, 'You are not authorised to update orders.', true);
            return response()->json(['ok' => true]);
        }

        $message = is_array($callback['message'] ?? null) ? $callback['message'] : [];
        $chatId = (string) data_get($message, 'chat.id', '');
        $expectedChatId = (string) config('services.telegram.group_chat_id');
        $messageId = (int) ($message['message_id'] ?? 0);

        if ($chatId === '' || $expectedChatId === '' || ! hash_equals($expectedChatId, $chatId)) {
            $this->answerSafely($telegram, $callbackId, 'This action is not from the configured administrator group.', true);
            return response()->json(['ok' => true]);
        }

        $parts = explode('|', (string) ($callback['data'] ?? ''));
        if (count($parts) !== 4 || $parts[0] !== 'oc' || ! ctype_digit($parts[1])) {
            $this->answerSafely($telegram, $callbackId, 'This order action is invalid.', true);
            return response()->json(['ok' => true]);
        }

        [, $orderIdText, $targetStatus, $signature] = $parts;
        $orderId = (int) $orderIdText;

        if (! $notifications->validCallbackSignature($orderId, $targetStatus, $chatId, $signature)) {
            $this->answerSafely($telegram, $callbackId, 'This order action could not be verified.', true);
            return response()->json(['ok' => true]);
        }

        $notification = TelegramOrderNotification::query()
            ->where('order_id', $orderId)
            ->where('chat_id', $chatId)
            ->where('status', 'sent')
            ->first();

        if (! $notification || (int) $notification->message_id !== $messageId) {
            $this->answerSafely($telegram, $callbackId, 'This notification is no longer valid.', true);
            return response()->json(['ok' => true]);
        }

        try {
            $order = $fulfillment->transition($orderId, $targetStatus, [
                'actor_type' => 'telegram_admin',
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $from['username'] ?? null,
                'actor_label' => trim(($from['first_name'] ?? '').' '.($from['last_name'] ?? '')) ?: 'Telegram administrator',
                'note' => 'Fulfillment status advanced from the configured Telegram administrator group.',
            ]);

            $notifications->refreshOrderMessage($order);
            $this->answerSafely(
                $telegram,
                $callbackId,
                'Order #'.$orderId.' updated to '.str_replace('_', ' ', $targetStatus).'.'
            );
        } catch (ValidationException $exception) {
            $order = Order::query()->with(['customer', 'orderType', 'delivery', 'orderItems.product'])->find($orderId);
            if ($order) {
                $notifications->refreshOrderMessage($order);
            }
            $this->answerSafely($telegram, $callbackId, 'This action is stale or out of sequence. The latest order status is shown in the group.', true);
        } catch (Throwable $exception) {
            Log::error('Telegram order callback failed.', [
                'order_id' => $orderId,
                'telegram_user_id' => $telegramUserId,
                'message' => $exception->getMessage(),
            ]);
            $this->answerSafely($telegram, $callbackId, 'The system could not update this order. Please try again or use the admin dashboard.', true);
        }

        return response()->json(['ok' => true]);
    }

    private function answerSafely(TelegramBotService $telegram, string $callbackId, string $text, bool $showAlert = false): void
    {
        if ($callbackId === '') {
            return;
        }

        try {
            $telegram->answerCallbackQuery($callbackId, $text, $showAlert);
        } catch (Throwable $exception) {
            Log::notice('Telegram callback acknowledgement failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
