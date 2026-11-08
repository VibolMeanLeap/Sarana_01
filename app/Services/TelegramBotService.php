<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotService
{
    public function isConfigured(): bool
    {
        return filled(config('services.telegram.bot_token'))
            && filled(config('services.telegram.group_chat_id'));
    }

    public function sendMessage(string $chatId, string $text, array $replyMarkup = []): array
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
            'protect_content' => true,
        ];

        if ($replyMarkup !== []) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $this->request('sendMessage', $payload);
    }

    public function editMessageText(string $chatId, int $messageId, string $text, array $replyMarkup = []): array
    {
        return $this->request('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'disable_web_page_preview' => true,
            'reply_markup' => $replyMarkup !== [] ? $replyMarkup : ['inline_keyboard' => []],
        ]);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text, bool $showAlert = false): void
    {
        $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    private function request(string $method, array $payload): array
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $url = 'https://api.telegram.org/bot'.$token.'/'.$method;
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(12)
            ->post($url, $payload);

        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || ! ($body['ok'] ?? false)) {
            $description = is_array($body) ? (string) ($body['description'] ?? '') : '';
            throw new RuntimeException('Telegram API request failed for '.$method.'. '.($description ?: 'HTTP '.$response->status()));
        }

        $result = $body['result'] ?? [];

        return is_array($result) ? $result : [];
    }
}
