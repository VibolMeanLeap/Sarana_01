@foreach ($replies as $reply)
    @php
        $isCustomer = $reply->sender_type === 'customer';
        $isSystem = $reply->sender_type === 'system';
    @endphp
    <div data-reply-id="{{ $reply->id }}">
        <div class="thread-label {{ $isCustomer ? 'text-end' : '' }} mb-1">{{ $isCustomer ? 'You' : ($isSystem ? 'Automatic store response' : ($reply->sender?->name ?: 'Store team')) }}</div>
        <div class="thread-message {{ $isCustomer ? 'customer' : ($isSystem ? 'system' : 'staff') }}">{{ $reply->body }}</div>
        <div class="thread-date {{ $isCustomer ? 'text-end' : '' }} mt-1">{{ optional($reply->created_at)->format('d M Y, h:i A') }}</div>
    </div>
@endforeach
