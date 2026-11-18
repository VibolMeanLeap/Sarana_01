<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TelegramOrderNotification;
use App\Services\CustomerTelegramOtpService;
use App\Services\OrderFulfillmentService;
use App\Services\TelegramBotService;
use App\Services\TelegramOrderNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Validation\ValidationException;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotService $telegram,
        OrderFulfillmentService $fulfillment,
        TelegramOrderNotificationService $notifications,
        CustomerTelegramOtpService $customerTelegramOtp
    ): JsonResponse {
        $secret = (string) config('services.telegram.webhook_secret');
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || $providedSecret === '' || ! hash_equals($secret, $providedSecret)) {
            return response()->json(['ok' => false], 403);
        }

        // Customer account verification starts with /start, then requires the
        // user's own Telegram contact before an OTP can be issued.
        $incomingMessage = $request->input('message');
        if (is_array($incomingMessage)) {
            $chatId = (string) data_get($incomingMessage, 'chat.id', '');
            $chatType = (string) data_get($incomingMessage, 'chat.type', '');
            $senderId = (string) data_get($incomingMessage, 'from.id', '');
            $isBot = (bool) data_get($incomingMessage, 'from.is_bot', false);
            $messageText = trim((string) ($incomingMessage['text'] ?? ''));

            if ($chatType === 'private' && $chatId !== '' && $senderId !== ''
                && hash_equals($chatId, $senderId) && ! $isBot) {

                // A request_contact button creates a contact update. The service
                // verifies contact.user_id equals the message sender before
                // comparing the phone with the website registration.
                $sharedContact = $incomingMessage['contact'] ?? null;
                if (is_array($sharedContact)) {
                    try {
                        $customerTelegramOtp->handleTelegramContact(
                            $chatId,
                            $senderId,
                            (string) ($sharedContact['phone_number'] ?? ''),
                            isset($sharedContact['user_id']) ? (string) $sharedContact['user_id'] : null
                        );
                    } catch (Throwable $exception) {
                        Log::notice('Customer Telegram phone verification was rejected.', [
                            'exception' => $exception::class,
                            'message' => $exception->getMessage(),
                        ]);

                        $notice = $exception instanceof RuntimeException
                            ? $exception->getMessage()
                            : 'We could not verify your phone number right now. Please return to the SK Coffee website and try again.';

                        $replyMarkup = str_contains($notice, 'Share my phone number button')
                            ? $customerTelegramOtp->phoneRequestKeyboard()
                            : ['remove_keyboard' => true];

                        $this->sendCustomerNoticeSafely(
                            $telegram,
                            $chatId,
                            $notice,
                            $replyMarkup
                        );
                    }

                    return response()->json(['ok' => true]);
                }

                if (preg_match('/^\/start(?:@[A-Za-z0-9_]+)?(?:\s+([A-Za-z0-9_-]{20,80}))?$/', $messageText, $matches)) {
                    $linkToken = (string) ($matches[1] ?? '');

                    if ($linkToken === '') {
                        $this->sendCustomerNoticeSafely(
                            $telegram,
                            $chatId,
                            "☕ Welcome to SK Coffee!\n\nWe are happy to help you. To verify your customer account, open the private Telegram link from the website registration page.\n\nNeed assistance? Tap Contact Admin below.",
                            $this->contactAdminKeyboard()
                        );
                    } else {
                        try {
                            // Keep the existing secure OTP flow intact. This call still
                            // binds the chat and sends the phone-sharing request keyboard.
                            $customerTelegramOtp->handleTelegramStart($linkToken, $chatId);

                            // Inline URL buttons cannot be combined with Telegram's
                            // request_contact reply keyboard, so show admin contact
                            // in a separate message instead of replacing that keyboard.
                            $this->sendCustomerNoticeSafely(
                                $telegram,
                                $chatId,
                                'Need help with account verification? Contact our administrator here:',
                                $this->contactAdminKeyboard()
                            );
                        } catch (Throwable $exception) {
                            Log::notice('Customer Telegram verification start was rejected.', [
                                'exception' => $exception::class,
                                'message' => $exception->getMessage(),
                            ]);
                            $this->sendCustomerNoticeSafely(
                                $telegram,
                                $chatId,
                                'This registration link is invalid, expired, or already used. Return to the SK Coffee website and generate a new Telegram link. If you need help, contact our administrator below.',
                                $this->contactAdminKeyboard()
                            );
                        }
                    }
                }
            }

            return response()->json(['ok' => true]);
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


    /** Build a safe Telegram inline URL button for the configured support account. */
    private function contactAdminKeyboard(): array
    {
        $username = ltrim(trim((string) config('services.telegram.contact_admin_username', 'Cho3Nab')), '@');

        // Telegram usernames contain only letters, numbers, and underscores.
        // Do not construct a URL from an invalid or unexpected configuration value.
        if (preg_match('/^[A-Za-z0-9_]{5,32}$/', $username) !== 1) {
            return [];
        }

        return [
            'inline_keyboard' => [[
                [
                    'text' => '👨‍💼 Contact Admin',
                    'url' => 'https://t.me/'.$username,
                ],
            ]],
        ];
    }

    private function sendCustomerNoticeSafely(TelegramBotService $telegram, string $chatId, string $text, array $replyMarkup = []): void
    {
        try {
            $telegram->sendMessage($chatId, $text, $replyMarkup);
        } catch (Throwable $exception) {
            Log::warning('Telegram customer verification notice could not be delivered.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
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
