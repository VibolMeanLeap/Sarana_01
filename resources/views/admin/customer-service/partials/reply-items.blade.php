@foreach ($replies as $reply)
    @php
        $isSystem = $reply->sender_type === 'system';
        $isCustomer = $reply->sender_type === 'customer';
        $senderName = $isSystem
            ? 'Coffee Corner Aura'
            : ($isCustomer ? ($customerName ?? 'Customer') : ($reply->sender?->name ?: 'Store team'));
        $initial = mb_strtoupper(mb_substr($senderName, 0, 1));
        $kind = $isSystem ? 'system' : ($isCustomer ? 'customer' : 'staff');
    @endphp
    <article class="cs-message cs-message--{{ $kind }}" data-reply-id="{{ $reply->id }}">
        @if ($kind !== 'staff')
            <div class="cs-message-avatar">{{ $kind === 'system' ? 'A' : $initial }}</div>
        @endif
        <div class="cs-message-content">
            <div class="cs-message-meta">
                <strong>{{ $senderName }}</strong>
                <time datetime="{{ optional($reply->created_at)->toIso8601String() }}">{{ optional($reply->created_at)->format('d M, h:i A') }}</time>
                @if ($kind === 'system') <span class="cs-message-tag">AUTOMATIC</span> @endif
            </div>
            <div class="cs-message-bubble">{{ $reply->body }}</div>
        </div>
        @if ($kind === 'staff')
            <div class="cs-message-avatar cs-message-avatar--staff">{{ $initial }}</div>
        @endif
    </article>
@endforeach
