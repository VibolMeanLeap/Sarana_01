@php
    $customerName = $contactMessage->name ?: 'Store customer';
    $customerInitial = mb_strtoupper(mb_substr($customerName, 0, 1));
    $status = $contactMessage->status ?: 'new';
    $statusLabel = $status === 'in_progress' ? 'In progress' : ucfirst($status);
    $replyCount = $contactMessage->replies->count();
    $lastReplyId = isset($lastReplyId) ? (int) $lastReplyId : (int) ($contactMessage->replies->max('id') ?? 0);
@endphp
<section
    class="cs-chat-panel"
    id="csActiveThread"
    data-cs-thread-panel
    data-conversation-id="{{ $contactMessage->id }}"
    data-thread-updates-url="{{ route('admin.customer-service.thread-updates', $contactMessage) }}"
    data-reply-url="{{ route('admin.customer-service.reply', $contactMessage) }}"
    data-update-url="{{ route('admin.customer-service.update', $contactMessage) }}"
    data-last-reply-id="{{ $lastReplyId }}"
>
    <header class="cs-chat-header">
        <div class="cs-chat-person">
            <span class="cs-chat-avatar">{{ $customerInitial }}</span>
            <div class="min-w-0">
                <h2>{{ $customerName }}</h2>
                <p>{{ $contactMessage->email }}@if($contactMessage->phone) <span>·</span> {{ $contactMessage->phone }} @endif</p>
            </div>
        </div>
        <div class="cs-chat-header-actions">
            <span class="cs-case-status cs-case-status--{{ $status }}" data-cs-status-label>
                <i class="bi {{ $status === 'resolved' ? 'bi-check-circle-fill' : ($status === 'in_progress' ? 'bi-hourglass-split' : 'bi-stars') }}"></i>
                {{ $statusLabel }}
            </span>
            <button type="button" class="cs-icon-btn" data-bs-toggle="collapse" data-bs-target="#csCustomerDetails" aria-expanded="false" aria-controls="csCustomerDetails" title="Conversation details">
                <i class="bi bi-info-circle"></i>
            </button>
        </div>
    </header>

    <div class="collapse cs-details-collapse" id="csCustomerDetails">
        <div class="cs-details-grid">
            <div><span>Topic</span><strong>{{ ucfirst(str_replace('_', ' ', $contactMessage->topic ?: 'other')) }}</strong></div>
            <div><span>Order reference</span><strong>{{ $contactMessage->order_reference ? '#'.$contactMessage->order_reference : 'Not provided' }}</strong></div>
            <div><span>Phone</span><strong>{{ $contactMessage->phone ?: 'Not provided' }}</strong></div>
            <div><span>Received</span><strong>{{ optional($contactMessage->created_at)->format('d M Y, h:i A') }}</strong></div>
        </div>
        @if (!$contactMessage->user_id)
            <div class="cs-guest-notice"><i class="bi bi-info-circle me-1"></i>This enquiry has no linked Store Account. Replies are stored in this staff conversation, but are not delivered to a customer account inbox.</div>
        @endif
    </div>

    <div class="cs-thread-scroll" id="csThreadBody" aria-live="polite" aria-relevant="additions text">
        <div class="cs-day-divider"><span>Original enquiry</span></div>
        <article class="cs-message cs-message--customer" data-original-message>
            <div class="cs-message-avatar">{{ $customerInitial }}</div>
            <div class="cs-message-content">
                <div class="cs-message-meta"><strong>{{ $customerName }}</strong><time>{{ optional($contactMessage->created_at)->format('d M, h:i A') }}</time></div>
                <div class="cs-message-bubble">{{ $contactMessage->message }}</div>
            </div>
        </article>

        <div class="cs-live-replies" id="csReplyFeed">
            @include('admin.customer-service.partials.reply-items', ['replies' => $contactMessage->replies, 'customerName' => $customerName])
        </div>
        @if ($replyCount === 0)
            <div class="cs-no-replies" data-cs-no-replies><i class="bi bi-chat-heart me-1"></i>You're all caught up. Send a reply to continue this conversation.</div>
        @endif
    </div>

    <div class="cs-composer-wrap">
        <div class="cs-composer-error" data-cs-feedback role="status" aria-live="polite"></div>
        <form method="POST" action="{{ route('admin.customer-service.reply', $contactMessage) }}" data-cs-reply-form>
            @csrf
            <textarea name="body" rows="2" maxlength="3000" minlength="2" required placeholder="Write a message to {{ $customerName }}…" aria-label="Write a customer reply"></textarea>
            <div class="cs-composer-bottom">
                <span><i class="bi bi-lock me-1"></i>Private to this conversation</span>
                <button type="submit" class="cs-send-button"><span>Send</span><i class="bi bi-send-fill"></i></button>
            </div>
        </form>
    </div>

    <details class="cs-manage-details">
        <summary><i class="bi bi-sliders me-2"></i>Manage conversation <span>Update status or private staff note</span></summary>
        <form method="POST" action="{{ route('admin.customer-service.update', $contactMessage) }}" data-cs-update-form class="cs-manage-form">
            @csrf
            @method('PATCH')
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="csStatus{{ $contactMessage->id }}">Conversation status</label>
                    <select class="form-select" id="csStatus{{ $contactMessage->id }}" name="status" required>
                        <option value="new" @selected($status === 'new')>New</option>
                        <option value="in_progress" @selected($status === 'in_progress')>In progress</option>
                        <option value="resolved" @selected($status === 'resolved')>Resolved</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label" for="csAdminNote{{ $contactMessage->id }}">Private staff note</label>
                    <textarea class="form-control" id="csAdminNote{{ $contactMessage->id }}" name="admin_note" rows="2" maxlength="3000" placeholder="Visible to administrators only…">{{ $contactMessage->admin_note }}</textarea>
                </div>
            </div>
            <button type="submit" class="cs-save-status"><i class="bi bi-check2-circle me-1"></i>Save conversation details</button>
        </form>
    </details>
</section>
