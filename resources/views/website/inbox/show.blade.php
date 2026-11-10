@extends('layout.web')
@section('web_title', 'Conversation #'.$contactMessage->id.' · '.($shopSettings?->shop_name ?: 'Coffee Corner'))
@section('content')
@php($latestReplyId = (int) ($contactMessage->replies->max('id') ?? 0))
<style>
    .thread-page{background:#f6f9f6;min-height:70vh;padding:38px 0 72px}.thread-shell{max-width:900px}.thread-card{background:#fff;border:1px solid #e5eee7;border-radius:20px;box-shadow:0 10px 28px rgba(23,59,38,.04)}.thread-message{max-width:88%;border-radius:17px;padding:15px 17px;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.65}.thread-message.customer{margin-left:auto;background:#edf6ef;color:#244b32;border:1px solid #dcecdf}.thread-message.staff{margin-right:auto;background:#f6f8f6;color:#34483a;border:1px solid #e8eee9}.thread-message.system{margin:0 auto;background:#fff8e8;color:#765a20;border:1px solid #f1e2bb;max-width:100%}.thread-label{font-size:.72rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em;color:#748178}.thread-date{font-size:.75rem;color:#8a958d}.thread-compose textarea{border:1px solid #dce8df;border-radius:13px;min-height:115px;box-shadow:none}.thread-compose textarea:focus{border-color:#39865c;box-shadow:0 0 0 3px #39865c18}.thread-live{font-size:.76rem;color:#77847b}.thread-live-dot{display:inline-block;width:7px;height:7px;background:#26965a;border-radius:50%;margin-right:7px}
</style>
<section class="thread-page">
    <div class="container thread-shell">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><a href="{{ route('web.inbox') }}" class="btn btn-outline-success rounded-pill px-3"><i class="bi bi-arrow-left me-1"></i>Back to inbox</a><span id="thread-status-badge" class="badge rounded-pill {{ $contactMessage->status === 'resolved' ? 'text-bg-success' : ($contactMessage->status === 'in_progress' ? 'text-bg-warning' : 'text-bg-primary') }}">{{ ucfirst(str_replace('_',' ',$contactMessage->status)) }}</span></div>
        <div class="thread-card p-3 p-md-4 mb-3"><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.12em">Enquiry #{{ $contactMessage->id }} · {{ ucfirst(str_replace('_',' ',$contactMessage->topic)) }}</div><h1 class="h3 fw-bold mb-1" style="color:#183326">Conversation with the shop</h1><p class="small text-muted mb-0">Started {{ optional($contactMessage->created_at)->format('d M Y, h:i A') }}@if($contactMessage->order_reference) · Order #{{ $contactMessage->order_reference }}@endif</p></div>
        <div id="thread-reply-feedback" aria-live="polite">
            @if(session('success'))<div class="alert alert-success border-0 rounded-4">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger rounded-4"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        </div>
        <div class="thread-card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Conversation</h2><span class="thread-live"><span class="thread-live-dot"></span>Live updates</span></div>
            <div class="d-grid gap-3" id="thread-replies" data-last-reply-id="{{ $latestReplyId }}" aria-live="polite">
                <div data-original-enquiry>
                    <div class="thread-label text-end mb-1">You · Original enquiry</div>
                    <div class="thread-message customer">{{ $contactMessage->message }}</div>
                    <div class="thread-date text-end mt-1">{{ optional($contactMessage->created_at)->format('d M Y, h:i A') }}</div>
                </div>
                @include('website.inbox.partials.reply-items', ['replies' => $contactMessage->replies])
            </div>
        </div>
        <div class="thread-card p-3 p-md-4 thread-compose"><h2 class="h5 fw-bold mb-1">Reply to the shop</h2><p class="small text-muted mb-3">Your reply will be added to this private conversation and sent to the team inbox.</p>
            <form id="thread-reply-form" method="POST" action="{{ route('web.inbox.reply', $contactMessage->id) }}">@csrf
                <label for="reply-body" class="form-label small fw-bold">Your message</label><textarea id="reply-body" name="body" class="form-control mb-3" maxlength="3000" minlength="2" placeholder="Write your follow-up or answer..." required>{{ old('body') }}</textarea>
                <div class="d-flex justify-content-between align-items-center gap-2"><span class="small text-muted">New replies appear here automatically.</span><button id="thread-send-button" type="submit" class="btn btn-success rounded-pill px-4"><i class="bi bi-send me-2"></i>Send reply</button></div>
            </form>
        </div>
    </div>
</section>
<script>
(() => {
    const thread = document.getElementById('thread-replies');
    const form = document.getElementById('thread-reply-form');
    const sendButton = document.getElementById('thread-send-button');
    const feedback = document.getElementById('thread-reply-feedback');
    const statusBadge = document.getElementById('thread-status-badge');
    if (!thread || !form) return;

    const baseUpdatesUrl = @json(route('web.inbox.thread-updates', $contactMessage->id));
    let lastReplyId = Number(thread.dataset.lastReplyId || 0);
    let polling = false;

    const updateHeaderBadges = (count) => {
        document.querySelectorAll('[data-store-inbox-count]').forEach((badge) => {
            badge.textContent = String(count);
            badge.hidden = Number(count) < 1;
        });
    };

    const setFeedback = (message, type = 'success') => {
        feedback.innerHTML = '';
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type + ' border-0 rounded-4';
        alert.textContent = message;
        feedback.appendChild(alert);
    };

    const updateStatus = (status) => {
        if (!statusBadge || !status) return;
        statusBadge.textContent = status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
        statusBadge.className = 'badge rounded-pill ' + (status === 'resolved' ? 'text-bg-success' : (status === 'in_progress' ? 'text-bg-warning' : 'text-bg-primary'));
    };

    const poll = async () => {
        if (polling || document.visibilityState !== 'visible') return;
        polling = true;
        try {
            const url = new URL(baseUpdatesUrl, window.location.origin);
            url.searchParams.set('after_id', String(lastReplyId));
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store', credentials: 'same-origin'
            });
            if (!response.ok) return;
            const data = await response.json();
            if (data.html) thread.insertAdjacentHTML('beforeend', data.html);
            lastReplyId = Math.max(lastReplyId, Number(data.last_reply_id || 0));
            thread.dataset.lastReplyId = String(lastReplyId);
            updateHeaderBadges(data.unread_count ?? 0);
            updateStatus(data.status);
        } catch (error) {
            // The conversation remains readable while offline; polling will retry.
        } finally {
            polling = false;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (sendButton.disabled) return;
        const originalButton = sendButton.innerHTML;
        sendButton.disabled = true;
        sendButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Sending';
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 422) {
                const firstError = Object.values(data.errors || {}).flat()[0] || 'Please check your message and try again.';
                setFeedback(firstError, 'danger');
                return;
            }
            if (!response.ok) {
                setFeedback(data.message || 'Your reply could not be sent. Please try again.', 'danger');
                return;
            }
            if (data.reply_html) thread.insertAdjacentHTML('beforeend', data.reply_html);
            lastReplyId = Math.max(lastReplyId, Number(data.last_reply_id || 0));
            thread.dataset.lastReplyId = String(lastReplyId);
            form.querySelector('textarea[name="body"]').value = '';
            updateStatus(data.status);
            updateHeaderBadges(data.unread_count ?? 0);
            setFeedback(data.message || 'Your message has been sent to the shop.');
        } catch (error) {
            setFeedback('Connection interrupted. Check your conversation before sending again to avoid a duplicate reply.', 'warning');
        } finally {
            sendButton.disabled = false;
            sendButton.innerHTML = originalButton;
        }
    });

    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') poll(); });
    window.setInterval(poll, 7000);
})();
</script>
@endsection
