@if ($messages->isNotEmpty())
    @foreach ($messages as $message)
        <a class="inbox-row" href="{{ route('web.inbox.show', $message->id) }}">
            <div class="min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="inbox-subject">{{ ucfirst(str_replace('_', ' ', $message->topic)) }}</span>
                    <span class="badge text-bg-light">#{{ $message->id }}</span>
                    @if ($message->unread_replies_count > 0)
                        <span class="inbox-pill inbox-unread">{{ $message->unread_replies_count }} new {{ $message->unread_replies_count === 1 ? 'reply' : 'replies' }}</span>
                    @endif
                </div>
                <div class="inbox-meta">{{ \Illuminate\Support\Str::limit($message->message, 110) }}</div>
                <div class="inbox-meta mt-1">Updated {{ optional($message->updated_at)->format('d M Y, h:i A') }}</div>
            </div>
            <div class="text-end flex-shrink-0">
                <span class="inbox-pill {{ $message->status === 'resolved' ? 'text-bg-success' : ($message->status === 'in_progress' ? 'text-bg-warning' : 'text-bg-primary') }}">{{ ucfirst(str_replace('_', ' ', $message->status)) }}</span>
                <div class="small text-success fw-bold mt-2">Open conversation <i class="bi bi-arrow-right ms-1"></i></div>
            </div>
        </a>
    @endforeach
@else
    <div class="text-center py-5 px-3">
        <div class="display-5 text-success mb-3"><i class="bi bi-inbox"></i></div>
        <h2 class="h5 fw-bold">Your inbox is empty</h2>
        <p class="text-muted small mb-3">When you send an enquiry while signed in, the shop's replies will appear here.</p>
        <a href="{{ route('web.contact') }}" class="btn btn-success rounded-pill px-4">Contact the shop</a>
    </div>
@endif
@if ($messages->hasPages())
    <div class="px-3 py-3 border-top">{{ $messages->links() }}</div>
@endif
