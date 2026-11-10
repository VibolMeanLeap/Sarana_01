@if ($messages->isEmpty())
    <div class="cs-list-empty">
        <div class="cs-list-empty-icon"><i class="bi bi-chat-square-dots"></i></div>
        <strong>No conversations found</strong>
        <span>Customer messages will appear here automatically.</span>
    </div>
@else
    <div class="cs-conversation-items">
        @foreach ($messages as $item)
            @php
                $name = $item->name ?: 'Store customer';
                $initial = mb_strtoupper(mb_substr($name, 0, 1));
                $unreadReplies = (int) ($item->unread_customer_replies_count ?? 0);
                $unread = $unreadReplies > 0 || $item->read_at === null;
                $latestReply = $item->latestReply;
                $latestCustomerReply = $item->latestCustomerReply;
                if ($latestReply?->sender_type === 'system') {
                    $preview = $latestCustomerReply?->body ?: $item->message;
                    $previewSender = $latestCustomerReply ? 'Customer: ' : 'Customer: ';
                } elseif ($latestReply?->sender_type === 'admin') {
                    $preview = $latestReply->body;
                    $previewSender = 'You: ';
                } elseif ($latestReply?->sender_type === 'customer') {
                    $preview = $latestReply->body;
                    $previewSender = 'Customer: ';
                } else {
                    $preview = $item->message;
                    $previewSender = 'Customer: ';
                }
                $status = $item->status ?: 'new';
                $statusLabel = $status === 'in_progress' ? 'In progress' : ucfirst($status);
            @endphp
            <a
                href="{{ route('admin.customer-service.index', array_merge(request()->only(['q', 'status', 'page']), ['conversation' => $item->id])) }}"
                data-cs-open-thread
                data-thread-url="{{ route('admin.customer-service.thread', $item) }}"
                data-conversation-id="{{ $item->id }}"
                class="cs-conversation-item {{ (int) $selectedId === (int) $item->id ? 'is-active' : '' }} {{ $unread ? 'is-unread' : '' }}"
                aria-current="{{ (int) $selectedId === (int) $item->id ? 'true' : 'false' }}"
            >
                <span class="cs-list-avatar">{{ $initial }}</span>
                <span class="cs-list-main">
                    <span class="cs-list-topline">
                        <strong class="cs-list-name">{{ $name }}</strong>
                        <time>{{ optional($item->latestReply?->created_at ?: $item->updated_at)->diffForHumans(null, true, true) }}</time>
                    </span>
                    <span class="cs-list-preview">{{ $previewSender }}{{ \Illuminate\Support\Str::limit($preview, 76) }}</span>
                    <span class="cs-list-bottomline">
                        <span class="cs-topic-mini">{{ ucfirst(str_replace('_', ' ', $item->topic ?: 'other')) }}</span>
                        <span class="cs-status-dot cs-status-dot--{{ $status }}">{{ $statusLabel }}</span>
                    </span>
                </span>
                @if ($unread)
                    <span class="cs-unread-indicator" title="Unread customer message">
                        @if ($unreadReplies > 0) {{ $unreadReplies }} @else <i class="bi bi-circle-fill"></i> @endif
                    </span>
                @endif
            </a>
        @endforeach
    </div>
    @if ($messages->hasPages())
        <div class="cs-list-pagination">{{ $messages->links() }}</div>
    @endif
@endif
