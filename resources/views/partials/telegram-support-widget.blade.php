@php
    $storeTelegramBotUsername = ltrim(trim((string) config('services.telegram.bot_username', 'Sa1ona_Bot')), '@');
    if (preg_match('/^[A-Za-z0-9_]{5,32}$/', $storeTelegramBotUsername) !== 1) {
        $storeTelegramBotUsername = 'Sa1ona_Bot';
    }
@endphp
<a class="store-telegram-fab"
    href="https://t.me/{{ $storeTelegramBotUsername }}"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Open the Coffee Corner support bot on Telegram (no website login required)"
    title="Chat with our support bot on Telegram">
    <span class="store-telegram-fab__avatar" aria-hidden="true">🤖</span>
    <span class="store-telegram-fab__copy">
        <strong>Need help?</strong>
        <small>Chat with our Telegram bot</small>
    </span>
    <span class="store-telegram-fab__telegram" aria-hidden="true"><i class="bi bi-telegram"></i></span>
</a>
