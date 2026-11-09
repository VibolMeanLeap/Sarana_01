@extends('layout.web')
@section('web_title', 'Customer inbox · '.($shopSettings?->shop_name ?: 'Coffee Corner'))
@section('content')
<style>
    .inbox-page{background:#f6f9f6;min-height:70vh;padding:42px 0 72px}.inbox-shell{max-width:1040px}.inbox-hero{background:linear-gradient(125deg,#102b1e,#1e563b);border-radius:24px;padding:clamp(25px,4vw,38px);color:#fff}.inbox-hero h1{font-weight:900;letter-spacing:-.045em}.inbox-card{background:#fff;border:1px solid #e5eee7;border-radius:18px;box-shadow:0 10px 28px rgba(23,59,38,.04)}.inbox-row{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:20px 22px;border-bottom:1px solid #edf2ee;color:inherit;text-decoration:none}.inbox-row:last-child{border-bottom:0}.inbox-row:hover{background:#f9fcfa}.inbox-subject{font-weight:850;color:#213a2c}.inbox-meta{font-size:.82rem;color:#77847b}.inbox-pill{font-size:.72rem;font-weight:800;border-radius:30px;padding:6px 10px}.inbox-unread{background:#e9f5ed;color:#176b43}
</style>
<section class="inbox-page">
    <div class="container inbox-shell">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div><div class="small fw-bold text-success text-uppercase mb-2" style="letter-spacing:.14em">Customer care</div><h1 class="h2 fw-bold mb-1" style="color:#173426">Your inbox</h1><p class="text-muted mb-0">Questions, replies and follow-ups with the {{ $shopSettings?->shop_name ?: 'shop' }} team.</p></div>
            <a href="{{ route('web.contact') }}" class="btn btn-success rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>New enquiry</a>
        </div>
        <div class="inbox-hero mb-4"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div><div class="small fw-bold text-uppercase mb-2" style="letter-spacing:.13em;color:#e9c98f">Store conversations</div><h2 class="h3 mb-1">A direct line to our team.</h2><p class="small mb-0 text-white-50">Our automatic acknowledgement confirms receipt. A team member will reply in the same conversation.</p></div><div class="text-end"><div class="display-6 fw-bold">{{ $unreadCount }}</div><div class="small text-white-50">Unread replies on this page</div></div></div></div>
        @if(session('success'))<div class="alert alert-success border-0 rounded-4">{{ session('success') }}</div>@endif
        <div class="inbox-card overflow-hidden">
            @forelse($messages as $message)
                <a class="inbox-row" href="{{ route('web.inbox.show', $message->id) }}">
                    <div class="min-w-0"><div class="d-flex flex-wrap align-items-center gap-2 mb-1"><span class="inbox-subject">{{ ucfirst(str_replace('_',' ',$message->topic)) }}</span><span class="badge text-bg-light">#{{ $message->id }}</span>@if($message->unread_replies_count > 0)<span class="inbox-pill inbox-unread">{{ $message->unread_replies_count }} new {{ $message->unread_replies_count === 1 ? 'reply' : 'replies' }}</span>@endif</div><div class="inbox-meta">{{ \Illuminate\Support\Str::limit($message->message, 110) }}</div><div class="inbox-meta mt-1">Updated {{ optional($message->updated_at)->format('d M Y, h:i A') }}</div></div>
                    <div class="text-end flex-shrink-0"><span class="inbox-pill {{ $message->status === 'resolved' ? 'text-bg-success' : ($message->status === 'in_progress' ? 'text-bg-warning' : 'text-bg-primary') }}">{{ ucfirst(str_replace('_',' ',$message->status)) }}</span><div class="small text-success fw-bold mt-2">Open conversation <i class="bi bi-arrow-right ms-1"></i></div></div>
                </a>
            @empty
                <div class="text-center py-5 px-3"><div class="display-5 text-success mb-3"><i class="bi bi-inbox"></i></div><h2 class="h5 fw-bold">Your inbox is empty</h2><p class="text-muted small mb-3">When you send an enquiry while signed in, the shop's replies will appear here.</p><a href="{{ route('web.contact') }}" class="btn btn-success rounded-pill px-4">Contact the shop</a></div>
            @endforelse
        </div>
        @if($messages->hasPages())<div class="mt-3">{{ $messages->links() }}</div>@endif
        <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-check me-1 text-success"></i>Your conversations are private to your signed-in customer account.</p>
    </div>
</section>
@endsection
